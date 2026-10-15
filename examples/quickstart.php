<?php

// Simple autoloader for tests
spl_autoload_register(function ($class) {
    $prefix = 'Zugferd\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use Zugferd\ZugferdClient;
use Zugferd\ValidationResult;
use Zugferd\BillingStatus;

echo "--- Starte PHP SDK Live-Test gegen e-rechnung-cloud.de ---\n\n";

$client = new ZugferdClient();
echo "1. Client initialisiert mit Test-Key: " . substr(ZugferdClient::DEFAULT_TEST_KEY, 0, 7) . "...\n";

// 2. Rechnungsdaten
$sampleInvoice = [
    'invoice_number' => 'PHP-TEST-2026-001',
    'issue_date' => '2026-10-10',
    'currency' => 'EUR',
    'seller' => [
        'name' => 'PHP Solution GmbH',
        'street' => 'Coderweg 42',
        'zip_code' => '10115',
        'city' => 'Berlin',
        'country' => 'DE',
        'vat_id' => 'DE123456789',
        'iban' => 'DE12345678901234567890'
    ],
    'buyer' => [
        'name' => 'Empfänger AG',
        'street' => 'Zielallee 1',
        'zip_code' => '80331',
        'city' => 'München',
        'country' => 'DE'
    ],
    'line_items' => [
        [
            'description' => 'PHP SDK Live Testing',
            'quantity' => 1,
            'unit_price' => 79.00,
            'tax_rate_percent' => 19.0
        ]
    ]
];

// Test 2: generateInvoice
echo "2. Teste generateInvoice()...\n";
$pdfPath = __DIR__ . '/rechnung_php_test.pdf';
$pdfBytes = $client->generateInvoice($sampleInvoice, $pdfPath);

if (!empty($pdfBytes) && substr($pdfBytes, 0, 4) === '%PDF' && file_exists($pdfPath)) {
    echo "   [SUCCESS] PDF erfolgreich empfangen (" . strlen($pdfBytes) . " Bytes) und gespeichert.\n";
} else {
    echo "   [FAIL] Ungültige PDF-Antwort!\n";
    exit(1);
}

// Test 3: validate()
echo "3. Teste validate() mit der soeben erzeugten PDF...\n";
$report = $client->validate($pdfPath);
if ($report->isValid() && strpos($report->getFormat(), 'PDF') !== false) {
    echo "   [SUCCESS] PDF ist valide nach EN 16931! Format: " . $report->getFormat() . "\n";
} else {
    echo "   [FAIL] Validierung fehlgeschlagen: " . implode(', ', $report->getErrors()) . "\n";
    exit(1);
}

// Test 4: validate() mit ungültigem XML
echo "4. Teste validate() mit ungültigem XML...\n";
$badXml = '<root><invalid>test</invalid></root>';
$badReport = $client->validate($badXml, 'bad.xml');
if (!$badReport->isValid() && count($badReport->getErrors()) > 0) {
    echo "   [SUCCESS] Ungültiges XML korrekt abgelehnt (" . count($badReport->getErrors()) . " Fehler erkannt).\n";
} else {
    echo "   [FAIL] Ungültiges XML wurde fälschlicherweise als gültig deklariert!\n";
    exit(1);
}

// Test 5: generateXRechnung()
echo "5. Teste generateXRechnung()...\n";
$xmlStr = $client->generateXRechnung($sampleInvoice);
if (!empty($xmlStr) && strpos($xmlStr, 'CrossIndustryInvoice') !== false) {
    echo "   [SUCCESS] XRechnung XML erfolgreich empfangen (Länge: " . strlen($xmlStr) . " Zeichen).\n";
} else {
    echo "   [FAIL] XRechnung XML ungültig!\n";
    exit(1);
}

// Test 6: getBillingStatus()
echo "6. Teste getBillingStatus()...\n";
$status = $client->getBillingStatus();
if ($status instanceof BillingStatus && strpos($status->getApiKeyPreview(), 'zg_test_') === 0) {
    echo "   [SUCCESS] Status erfolgreich abgefragt: Key=" . $status->getApiKeyPreview() . ", Aktiv=" . ($status->isActive() ? 'Ja' : 'Nein') . "\n";
} else {
    echo "   [FAIL] BillingStatus ungültig!\n";
    exit(1);
}

// Aufräumen
@unlink($pdfPath);

echo "\n=============================================\n";
echo ">>> ALLE 6 TESTS ERFOLGREICH BESTANDEN! <<<\n";
echo "=============================================\n";
