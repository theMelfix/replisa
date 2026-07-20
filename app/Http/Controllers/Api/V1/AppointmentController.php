<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Contact;
use App\Support\PlanLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppointmentController extends ApiController
{
    /**
     * `POST /api/v1/appointments` (task 4.3.3): crea un appuntamento a partire
     * dal numero del cliente. Il contatto viene creato se non esiste (nei limiti
     * del piano). Il reminder parte poi automaticamente dallo scheduler (E3.2).
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $tenant = $this->tenant($request);

        $phone = Contact::normalizePhone($request->validated('phone'));
        $contact = Contact::where('phone', $phone)->first();

        if (! $contact && ! PlanLimits::for($tenant)->canAddContacts()) {
            throw new HttpException(422, sprintf(
                'Hai raggiunto il limite di contatti del tuo piano (%s).',
                PlanLimits::for($tenant)->contactsLimit()
            ));
        }

        $contact ??= Contact::create([
            'phone' => $phone,
            'name' => $request->validated('name'),
        ]);

        $appointment = Appointment::create([
            'contact_id' => $contact->id,
            'scheduled_at' => Carbon::parse($request->validated('scheduled_at')),
            'status' => Appointment::STATUS_SCHEDULED,
        ]);

        return response()->json([
            'id' => $appointment->id,
            'status' => $appointment->status,
            'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
            'contact' => [
                'id' => $contact->id,
                'phone' => $contact->phone,
                'name' => $contact->name,
            ],
        ], 201);
    }
}
