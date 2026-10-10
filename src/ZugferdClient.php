<?php

namespace Zugferd;

use Zugferd\Exception\AuthenticationException;
use Zugferd\Exception\QuotaExceededException;
use Zugferd\Exception\ValidationException;
use Zugferd\Exception\ApiException;
use Zugferd\Exception\ZugferdException;

/**
 * Offizieller PHP-Client für die E-Rechnung API.
 * Unterstützt ZUGFeRD 2.2 / Factur-X (PDF/A-3) und XRechnung nach EN 16931.
 */
class ZugferdClient
{
    public const DEFAULT_BASE_URL = 'https://e-rechnung-api.fly.dev';
    public const DEFAULT_TEST_KEY = 'zg_test_ouroboros_001';

    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    /**
     * @param string|null $apiKey Dein API-Key (standardmäßig der kostenlose Test-Key)
     * @param string $baseUrl Basis-URL der API
     * @param int $timeout Timeout in Sekunden
     */
    public function __construct(?string $apiKey = null, string $baseUrl = self::DEFAULT_BASE_URL, int $timeout = 30)
    {
        $this->apiKey = $apiKey ?? (getenv('ZUGFERD_API_KEY') ?: self::DEFAULT_TEST_KEY);
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Generiert ein druckfertiges ZUGFeRD 2.2 / Factur-X PDF/A-3 Dokument.
     *
     * @param array $invoiceData Rechnungsdaten gemäß EN 16931 Schema
     * @param string|null $outputPath Optionaler Dateipfad zum Speichern
     * @return string Rohe PDF-Bytes
     * @throws ZugferdException
     */
    public function generateInvoice(array $invoiceData, ?string $outputPath = null): string
    {
        $url = $this->baseUrl . '/v1/invoice/generate';
        $payload = json_encode($invoiceData, JSON_THROW_ON_ERROR);

        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey,
            'User-Agent: zugferd-php-client/0.1.0',
        ];

        $pdfBytes = $this->request('POST', $url, $headers, $payload);

        if ($outputPath !== null) {
            file_put_contents($outputPath, $pdfBytes);
        }

        return $pdfBytes;
    }

    /**
     * Bettet ZUGFeRD-XML verlustfrei in ein bestehendes Kunden-PDF ein.
     *
     * @param string $pdfFile Dateipfad oder rohe PDF-Bytes
     * @param array|string $invoiceData Rechnungsdaten als Array oder JSON-String
     * @param string|null $outputPath Optionaler Dateipfad zum Speichern
     * @return string Rohe PDF-Bytes
     * @throws ZugferdException
     */
    public function embedInvoice($pdfFile, $invoiceData, ?string $outputPath = null): string
    {
        $url = $this->baseUrl . '/v1/invoice/embed';
        $jsonData = is_array($invoiceData) ? json_encode($invoiceData, JSON_THROW_ON_ERROR) : (string)$invoiceData;

        $tempFile = null;
        if (is_file($pdfFile)) {
            $filePath = $pdfFile;
            $fileName = basename($pdfFile);
        } else {
            $tempFile = tempnam(sys_get_temp_dir(), 'zg_');
            file_put_contents($tempFile, $pdfFile);
            $filePath = $tempFile;
            $fileName = 'document.pdf';
        }

        try {
            $postFields = [
                'invoice_data' => $jsonData,
                'file' => new \CURLFile($filePath, 'application/pdf', $fileName),
            ];

            $headers = [
                'X-API-Key: ' . $this->apiKey,
                'User-Agent: zugferd-php-client/0.1.0',
            ];

            $pdfBytes = $this->request('POST', $url, $headers, $postFields, true);

            if ($outputPath !== null) {
                file_put_contents($outputPath, $pdfBytes);
            }

            return $pdfBytes;
        } finally {
            if ($tempFile !== null && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Erstellt eine reine EN 16931 XML-Datei (XRechnung) ohne PDF.
     *
     * @param array $invoiceData Rechnungsdaten
     * @param string|null $outputPath Optionaler Speicherpfad
     * @return string XML-String
     * @throws ZugferdException
     */
    public function generateXRechnung(array $invoiceData, ?string $outputPath = null): string
    {
        $url = $this->baseUrl . '/v1/invoice/xrechnung';
        $payload = json_encode($invoiceData, JSON_THROW_ON_ERROR);

        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey,
            'User-Agent: zugferd-php-client/0.1.0',
        ];

        $xml = $this->request('POST', $url, $headers, $payload);

        if ($outputPath !== null) {
            file_put_contents($outputPath, $xml);
        }

        return $xml;
    }

    /**
     * Validiert ein ZUGFeRD-PDF oder XML-Dokument auf EN 16931 Konformität.
     *
     * @param string $file Dateipfad oder rohe Datei-Bytes
     * @param string|null $filename Optionaler Dateiname (wichtig zur Formaterkennung bei Bytes)
     * @return ValidationResult
     * @throws ZugferdException
     */
    public function validate($file, ?string $filename = null): ValidationResult
    {
        $url = $this->baseUrl . '/v1/invoice/validate';

        $tempFile = null;
        if (is_file($file)) {
            $filePath = $file;
            $uploadName = $filename ?? basename($file);
        } else {
            $uploadName = $filename ?? 'invoice.pdf';
            $tempFile = tempnam(sys_get_temp_dir(), 'zg_val_');
            file_put_contents($tempFile, $file);
            $filePath = $tempFile;
        }

        try {
            $mime = strtolower(substr($uploadName, -4)) === '.xml' ? 'application/xml' : 'application/pdf';
            $postFields = [
                'file' => new \CURLFile($filePath, $mime, $uploadName),
            ];

            $headers = [
                'X-API-Key: ' . $this->apiKey,
                'User-Agent: zugferd-php-client/0.1.0',
            ];

            $response = $this->request('POST', $url, $headers, $postFields, true);
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return new ValidationResult(
                $data['is_valid'] ?? false,
                $data['format_detected'] ?? 'Unknown',
                $data['errors'] ?? []
            );
        } finally {
            if ($tempFile !== null && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * Fragt das verbleibende Dokumenten-Guthaben und den Status des Keys ab.
     *
     * @return BillingStatus
     * @throws ZugferdException
     */
    public function getBillingStatus(): BillingStatus
    {
        $url = $this->baseUrl . '/v1/invoice/billing/status';
        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'User-Agent: zugferd-php-client/0.1.0',
        ];

        $response = $this->request('GET', $url, $headers);
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return new BillingStatus(
            $data['api_key_preview'] ?? '',
            $data['credits_remaining'] ?? 0,
            $data['is_active'] ?? false
        );
    }

    /**
     * Führt den HTTP-Aufruf via cURL aus.
     */
    protected function request(string $method, string $url, array $headers = [], $body = null, bool $isMultipart = false): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new ApiException("cURL-Verbindungsfehler: " . $curlError, 0);
        }

        if ($httpCode === 200) {
            return $response;
        }

        if ($httpCode === 401) {
            throw new AuthenticationException("Ungültiger oder fehlender API-Key (HTTP 401).");
        }

        if ($httpCode === 402) {
            throw new QuotaExceededException("Dokumenten-Guthaben aufgebraucht (HTTP 402).");
        }

        if ($httpCode === 422) {
            $details = json_decode($response, true);
            $msg = is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $response;
            throw new ValidationException("Ungültige Rechnungsdaten (HTTP 422): " . $msg, $details);
        }

        throw new ApiException("API-Fehler (HTTP $httpCode): " . $response, $httpCode, $response);
    }
}
