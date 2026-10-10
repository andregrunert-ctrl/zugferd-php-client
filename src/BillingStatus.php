<?php

namespace Zugferd;

/**
 * Status und verbleibendes Dokumenten-Guthaben eines API-Keys.
 */
class BillingStatus
{
    private string $apiKeyPreview;
    private int $creditsRemaining;
    private bool $isActive;

    public function __construct(string $apiKeyPreview, int $creditsRemaining, bool $isActive)
    {
        $this->apiKeyPreview = $apiKeyPreview;
        $this->creditsRemaining = $creditsRemaining;
        $this->isActive = $isActive;
    }

    public function getApiKeyPreview(): string
    {
        return $this->apiKeyPreview;
    }

    public function getCreditsRemaining(): int
    {
        return $this->creditsRemaining;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'api_key_preview' => $this->apiKeyPreview,
            'credits_remaining' => $this->creditsRemaining,
            'is_active' => $this->isActive,
        ];
    }
}
