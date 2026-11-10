# QBgen - Generador de Codigos QR y Codigos de Barra en PHP

Libreria PHP estructurada como paquete Composer para la generacion, procesamiento y renderizado de codigos QR bidimensionales y codigos de barras lineales 1D en formato vectorial nativo (SVG sin dependencias externas) y mapa de bits (PNG, WebP, JPEG utilizando GD).

Optimizada para PHP 8.5 o superior con suite completa de pruebas automatizadas mediante Pest PHP.

## Requisitos del Sistema

* PHP 8.5 o superior.
* Extension PHP GD habilitada (opcional, requerida para mapas de bits PNG, WebP o JPEG).
* Para renderizado vectorial SVG no se requiere GD ni dependencias externas.

## Instalacion con Composer

Instalar mediante Composer:

```bash
composer require pirulug/qbgen
```

O anadir directamente al archivo `composer.json`:

```json
{
    "require": {
        "pirulug/qbgen": "^1.0"
    }
}
```

## Uso de QRCode

```php
<?php

require_once __DIR__ . "/vendor/autoload.php";

use Pirulug\QBgen\QRCode;

$qr = QRCode::make("https://ejemplo.com")
    ->setSize(300)
    ->setMargin(2)
    ->setErrorCorrection("M")
    ->setForegroundColor("000000")
    ->setBackgroundColor("FFFFFF");

// Guardar como imagen PNG en disco
$qr->save(__DIR__ . "/codigo.png");

// O guardar como vector SVG
$qr->save(__DIR__ . "/codigo.svg");

// O generar Data URI listo para HTML
$dataUri = $qr->toDataUri();
```

## Uso de BarCode

La clase `BarCode` permite generar codigos de barra lineales unidimensionales (1D) en multiples formatos y simbologias estandar.

```php
<?php

require_once __DIR__ . "/vendor/autoload.php";

use Pirulug\QBgen\BarCode;

// Sintaxis fluida
$barcode = BarCode::make("750123456789", "code128")
    ->setScale(2)
    ->setMargin(15)
    ->setForegroundColor("000000")
    ->setBackgroundColor("FFFFFF");

// Guardar como imagen PNG
$barcode->save(__DIR__ . "/barcode.png");

// Guardar como vector SVG
$barcode->save(__DIR__ . "/barcode.svg");

// Obtener Data URI para incrustar en HTML
$dataUri = $barcode->toDataUri();
```

### Simbologias de Codigos de Barra Soportadas

* `code128`, `code128a`, `code128b`, `code128c`, `ean128`
* `ean13`, `ean8`, `ean13nopad`
* `upca`, `upce`
* `code39`, `code39ascii`
* `code93`, `code93ascii`
* `itf` (Interleaved 2 of 5)
* `codabar`, `msi`

## Compatibilidad Global y Espacios de Nombre Anteriores

Para proyectos heredados o scripts sin importacion de namespace, la libreria registra automaticamente alias a las clases globales `QRCode` y `BarCode`, ademas de mantener compatibilidad con `Pirulug\Qr`:

```php
$qr = new QRCode("https://ejemplo.com", [
    "s"  => "qrm",
    "sf" => 5
]);

$barcode = new BarCode();
$image = $barcode->render_image("code128", "12345678");
```

## Metodos Disponibles

### Metodos de QRCode

* `QRCode::make(string $data = "", array $options = []): self`
* `setData(string $data): self`
* `setSize(int $width, ?int $height = null): self`
* `setScale(float $scale): self`
* `setMargin(int $margin): self`
* `setErrorCorrection(string $level): self` (Niveles `"L"`, `"M"`, `"Q"`, `"H"`)
* `setForegroundColor(string $hex): self`
* `setBackgroundColor(string $hex): self` (Acepta hex o `"transparent"`)
* `setDensity(float $density): self`
* `setLogo(string $logoPath, float $scale = 0.22): self`
* `renderSvg(): string`
* `outputSvg(): void`
* `render_image(): \GdImage`
* `output_image(string $format = "png"): void`
* `save(string $filePath, ?string $format = null, int $quality = -1): bool`
* `toBase64(string $format = "png"): string`
* `toDataUri(string $format = "png"): string`
* `getMatrix(): array`
* `getMatrixSize(): int`

### Metodos de BarCode

* `BarCode::make(string $data = "", string $symbology = "code128", array $options = []): self`
* `setData(string $data): self`
* `setSymbology(string $symbology): self` (o `setType(string $symbology): self`)
* `setScale(float $scale): self`
* `setMargin(int $margin): self`
* `setForegroundColor(string $color): self`
* `setBackgroundColor(string $color): self` (Acepta hex o `"transparent"`)
* `setOptions(array $options): self`
* `render_svg(?string $symbology = null, ?string $data = null, array $options = []): string`
* `renderSvg(?string $symbology = null, ?string $data = null, array $options = []): string`
* `outputSvg(): void`
* `render_image(?string $symbology = null, ?string $data = null, array $options = []): \GdImage`
* `output_image(string $format = "png", ?string $symbology = null, ?string $data = null, array $options = []): void`
* `save(string $filePath, ?string $format = null, int $quality = -1): bool`
* `toBase64(string $format = "png"): string`
* `toDataUri(string $format = "png"): string`

## Ejecucion de Pruebas con Pest

El paquete incluye una suite completa de pruebas unitarias y de integracion para `QRCode` y `BarCode`.

Para ejecutar las pruebas:

```bash
composer test
```

O directamente:

```bash
./vendor/bin/pest
```

## Estructura del Paquete

```text
qr/
├── composer.json       # Manifiesto del paquete pirulug/qbgen y autoloading PSR-4
├── phpunit.xml         # Configuracion de Pest / PHPUnit
├── LICENSE             # Licencia MIT
├── README.md           # Documentacion del paquete
├── index.php           # Punto de entrada de la aplicacion
├── demo/
│   └── index.php       # Interfaz web interactiva para pruebas locales
├── src/
│   ├── QRCode.php      # Generador de codigos QR bidimensionales (Pirulug\QBgen\QRCode)
│   └── BarCode.php     # Generador de codigos de barra lineales 1D (Pirulug\QBgen\BarCode)
└── tests/
    ├── Pest.php        # Configuracion de Pest PHP
    ├── TestCase.php    # Clase base para pruebas
    ├── QRCodeTest.php  # Pruebas unitarias de QRCode
    └── BarCodeTest.php # Pruebas unitarias de BarCode
```
