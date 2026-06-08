<?php

namespace App\Services\WhatsApp;

use RuntimeException;
use Throwable;

/**
 * Errore restituito dalla Meta Cloud API (o di trasporto verso di essa).
 * Trasporta il codice errore Meta per permettere routing/decisioni a monte.
 *
 * Codici Meta comuni:
 *  - 190     token scaduto o invalido
 *  - 100     parametro non valido / numero non in allow-list (sandbox)
 *  - 130429  rate limit raggiunto
 *  - 131047  finestra 24h chiusa (serve un template)
 *  - 131026  messaggio non consegnabile (numero non WhatsApp)
 */
class WhatsAppApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $metaCode = null,
        public readonly ?int $httpStatus = null,
        public readonly array $metaError = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Costruisce l'eccezione dal blocco `error` della risposta Meta.
     *
     * @param  array<string, mixed>  $error
     */
    public static function fromMetaError(array $error, int $httpStatus): self
    {
        $code = isset($error['code']) ? (int) $error['code'] : null;
        $message = $error['message'] ?? 'Errore sconosciuto dalla Meta Cloud API';

        return new self(
            message: "Meta API [{$code}]: {$message}",
            metaCode: $code,
            httpStatus: $httpStatus,
            metaError: $error,
        );
    }
}
