<?php

namespace Zugferd;

/**
 * Repräsentiert das Ergebnis einer E-Rechnungs-Validierung.
 */
class ValidationResult
{
    private bool $isValid;
    private string $formatDetected;
    /** @var string[] */
    private array $errors;

    /**
     * @param bool $isValid
     * @param string $formatDetected
     * @param string[] $errors
     */
    public function __construct(bool $isValid, string $formatDetected, array $errors = [])
    {
        $this->isValid = $isValid;
        $this->formatDetected = $formatDetected;
        $this->errors = $errors;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getFormat(): string
    {
        return $this->formatDetected;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'format_detected' => $this->formatDetected,
            'errors' => $this->errors,
        ];
    }
}
