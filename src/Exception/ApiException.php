<?php

namespace Zugferd\Exception;

/**
 * Wird geworfen, wenn die API einen Server- oder Netzwerkfehler meldet (HTTP 5xx).
 */
class ApiException extends ZugferdException
{
    /** @var string */
    protected string $responseBody;

    public function __construct(string $message, int $statusCode = 500, string $responseBody = "", ?\Throwable $previous = null)
    {
        parent::__construct("[$statusCode] $message", $statusCode, $previous);
        $this->responseBody = $responseBody;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
