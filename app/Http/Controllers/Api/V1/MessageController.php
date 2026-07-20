<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\WhatsApp\WhatsAppApiException;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\PlanLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MessageController extends ApiController
{
    /**
     * `GET /api/v1/messages` (task 4.3.4): cronologia messaggi del tenant,
     * paginata e filtrabile. Le query sono già isolate dal TenantScope.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'direction' => ['sometimes', 'in:inbound,outbound'],
            'status' => ['sometimes', 'string', 'max:20'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $messages = Message::query()
            ->with('contact')
            ->when($filters['direction'] ?? null, fn ($q, $d) => $q->where('direction', $d))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($filters['per_page'] ?? 25);

        return MessageResource::collection($messages);
    }

    /**
     * `POST /api/v1/messages/send` (task 4.3.2): invia un messaggio singolo.
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        if (! $tenant->hasWhatsAppConfigured()) {
            throw new HttpException(422, 'Collega prima un numero WhatsApp all\'account.');
        }

        // Enforcement limiti di piano (E4.2.6): l'API non deve poter aggirare il
        // limite contatti. Risolviamo qui il contatto (invece di lasciarlo al
        // firstOrCreate del servizio) così un numero nuovo oltre il limite è bloccato.
        $contact = $this->resolveContact($request, $tenant);

        $service = WhatsAppService::for($tenant);

        try {
            $message = $request->validated('type') === Message::TYPE_TEMPLATE
                ? $service->sendTemplate(
                    $contact,
                    $request->validated('template'),
                    $request->validated('language', 'it'),
                    $this->templateComponents($request->validated('params', [])),
                )
                : $service->sendText($contact, $request->validated('text'));
        } catch (WhatsAppApiException $e) {
            // Errore lato Meta (fuori finestra 24h, template non approvato,
            // numero invalido): il messaggio è già loggato come `failed`.
            return response()->json([
                'message' => $e->getMessage(),
                'meta_code' => $e->metaCode,
            ], 502);
        }

        return MessageResource::make($message->load('contact'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Trova o crea il contatto destinatario, rispettando il limite contatti
     * del piano per i numeri nuovi.
     */
    private function resolveContact(Request $request, Tenant $tenant): Contact
    {
        $phone = Contact::normalizePhone($request->validated('to'));
        $contact = Contact::where('phone', $phone)->first();

        if ($contact) {
            return $contact;
        }

        if (! PlanLimits::for($tenant)->canAddContacts()) {
            throw new HttpException(422, sprintf(
                'Hai raggiunto il limite di contatti del tuo piano (%s).',
                PlanLimits::for($tenant)->contactsLimit()
            ));
        }

        return Contact::create(['phone' => $phone]);
    }

    /**
     * Costruisce i componenti body del template dai parametri posizionali.
     *
     * @param  array<int, string>  $params
     * @return array<int, array<string, mixed>>
     */
    private function templateComponents(array $params): array
    {
        if ($params === []) {
            return [];
        }

        return [[
            'type' => 'body',
            'parameters' => array_map(
                fn (string $text) => ['type' => 'text', 'text' => $text],
                array_values($params),
            ),
        ]];
    }
}
