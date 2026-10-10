<?php

namespace Zugferd\Exception;

/**
 * Wird geworfen, wenn die Rechnungsdaten fehlerhaft sind oder Rechenfehler enthalten (HTTP 422).
 */
class ValidationException extends ZugferdException
{
    /** @var mixed */
    protected $details;

    public function __construct(string $message = "", $details = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    public function getDetails()
    {
        return $this->details;
    }
}
