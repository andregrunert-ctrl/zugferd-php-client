# zugferd-client (PHP)

[![Latest Version](https://img.shields.io/packagist/v/andregrunert-ctrl/zugferd-client.svg)](https://packagist.org/packages/andregrunert-ctrl/zugferd-client)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)](https://packagist.org/packages/andregrunert-ctrl/zugferd-client)
[![Standard](https://img.shields.io/badge/Standard-EN%2016931%20%7C%20ZUGFeRD%202.2-blue)](https://e-rechnung-api.fly.dev)
[![Format](https://img.shields.io/badge/Format-PDF%2FA--3%20%2B%20Factur--X-purple)](https://e-rechnung-api.fly.dev)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

Der offizielle, leichtgewichtige PHP-Client für die [E-Rechnung API](https://e-rechnung-api.fly.dev).

Erstelle, verarbeite und validiere rechtskonforme elektronische Rechnungen nach **EN 16931** (ZUGFeRD 2.2 / Factur-X & XRechnung) in WordPress, WooCommerce, Shopware, Laravel oder nativen PHP-Applikationen.

---

## 📦 Installation

```bash
composer require andregrunert-ctrl/zugferd-client
```

*(Erfordert PHP >= 7.4 oder PHP 8.x und hat keine schweren externen Abhängigkeiten, da nativer cURL-Support genutzt wird).*

---

## ⚡ Schnellstart

Dank des integrierten öffentlichen Test-Keys kannst du sofort ohne API-Key oder Registrierung starten:

```php
<?php

require_once 'vendor/autoload.php';

use Zugferd\ZugferdClient;

// Initialisierung (nutzt automatisch den kostenlosen Test-Key)
$client = new ZugferdClient();

// Rechnungsdaten gemäß EN 16931
$invoiceData = [
    'invoice_number' => 'RE-2026-001',
    'issue_date' => '2026-10-10',
    'currency' => 'EUR',
    'seller' => [
        'name' => 'Muster GmbH',
        'street' => 'Hauptstraße 1',
        'zip_code' => '10115',
        'city' => 'Berlin',
        'country' => 'DE',
        'vat_id' => 'DE123456789',
        'iban' => 'DE12345678901234567890'
    ],
    'buyer' => [
        'name' => 'Kunden AG',
        'street' => 'Nebenstraße 2',
        'zip_code' => '80331',
        'city' => 'München',
        'country' => 'DE'
    ],
    'line_items' => [
        [
            'description' => 'Web-Entwicklung & E-Rechnung Integration',
            'quantity' => 10,
            'unit_price' => 120.00,
            'tax_rate_percent' => 19
        ]
    ]
];

// 1. ZUGFeRD PDF/A-3 Rechnung generieren und speichern
$pdfBytes = $client->generateInvoice($invoiceData, 'rechnung.pdf');
echo "✅ ZUGFeRD-Rechnung erfolgreich gespeichert!\n";

// 2. Rechnung validieren
$report = $client->validate('rechnung.pdf');
if ($report->isValid()) {
    echo "✅ Konform nach EN 16931 (" . $report->getFormat() . ")\n";
} else {
    echo "❌ Fehler: " . implode(', ', $report->getErrors()) . "\n";
}
```

---

## 🛠️ Methoden-Übersicht

### 1. `generateInvoice(array $data, ?string $outputPath = null): string`
Generiert ein vollständiges, druckfertiges deutsches PDF/A-3 mit eingebetteter `factur-x.xml`.

### 2. `embedInvoice($pdfFile, $data, ?string $outputPath = null): string`
Bettet das XML verlustfrei in ein bestehendes Kunden-PDF ein (z. B. aus Dompdf, TCPDF oder mPDF).

```php
$client->embedInvoice('mein_shop_beleg.pdf', $invoiceData, 'zugferd_fertig.pdf');
```

### 3. `generateXRechnung(array $data, ?string $outputPath = null): string`
Erstellt reines XML ohne PDF (Pflicht für deutsche Behörden / B2G).

### 4. `validate($file, ?string $filename = null): ValidationResult`
Prüft jede PDF- oder XML-Rechnung auf Einhaltung der europäischen Norm EN 16931 und offizielle XSD-Schemata.

---

## 🔑 Eigener API-Key & Produktion

Für produktive Rechnungen ohne Test-Wasserzeichen übergibst du deinen persönlichen Key oder setzt die Umgebungsvariable `ZUGFERD_API_KEY`:

```php
$client = new ZugferdClient('zg_live_mein_api_key');
```

---

## 🌐 Nützliche Links

* **Live-Web-Validator:** [https://e-rechnung-api.fly.dev/validator](https://e-rechnung-api.fly.dev/validator) (Kostenlos Dateien im Browser prüfen)
* **Interaktive API-Dokumentation (Swagger):** [https://e-rechnung-api.fly.dev/docs](https://e-rechnung-api.fly.dev/docs)
* **Preise & Tarife:** [https://e-rechnung-api.fly.dev/#pricing](https://e-rechnung-api.fly.dev/#pricing)
* **PHP & Laravel Tutorial:** [https://e-rechnung-api.fly.dev/tutorials/php-zugferd](https://e-rechnung-api.fly.dev/tutorials/php-zugferd)
* **WooCommerce Guide:** [https://e-rechnung-api.fly.dev/tutorials/woocommerce-e-rechnung](https://e-rechnung-api.fly.dev/tutorials/woocommerce-e-rechnung)

---

## 📄 Lizenz

MIT License - Siehe [LICENSE](LICENSE) für Details.

---

## ⚖️ Markenhinweis & Disclaimer

ZUGFeRD ist eine eingetragene Marke der **AWV e.V.** (*Arbeitsgemeinschaft für wirtschaftliche Verwaltung e.V.*) / FeRD. Dieses Projekt (`zugferd-client`) ist ein unabhängiges Open-Source-Projekt und steht in keiner geschäftlichen oder offiziellen Verbindung zur AWV e.V. oder dem Forum elektronische Rechnung Deutschland. Die Nennung der Marke erfolgt ausschließlich zur beschreibenden Angabe der Formatkompatibilität im Sinne von § 23 Abs. 1 Nr. 3 MarkenG.
