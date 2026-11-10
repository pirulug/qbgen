# Registro de Cambios (Changelog)

Todos los cambios notables en el paquete `pirulug/qbgen` seran documentados en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/) y se adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [0.0.1] - 2026-11-09

### Anadido

* Lanzamiento inicial de la libreria `pirulug/qbgen`.
* Soporte para generacion de codigos QR bidimensionales (clase `Pirulug\QBgen\QRCode`):
  * Renderizado vectorial nativo SVG sin dependencias externas.
  * Renderizado en mapa de bits PNG, WebP y JPEG utilizando la extension PHP GD.
  * Niveles de correccion de error configurables: Low (`L`), Medium (`M`), Quartile (`Q`) y High (`H`).
  * Densidad de modulos configurable y soporte de fondo transparente.
  * Capacidad de superponer logotipos o iconos centrados en el codigo QR.
  * Metodos de exportacion: `renderSvg()`, `render_image()`, `outputSvg()`, `output_image()`, `save()`, `toBase64()`, `toDataUri()`, `getMatrix()` y `getMatrixSize()`.
* Soporte para generacion de codigos de barra lineales 1D (clase `Pirulug\QBgen\BarCode`):
  * Simbologias soportadas: `Code 128` (A/B/C/EAN-128), `EAN-13`, `EAN-8`, `UPC-A`, `UPC-E`, `Code 39` (estandar y ASCII extendido), `Code 93`, `ITF` (Interleaved 2 of 5), `Codabar` y `MSI`.
  * Renderizado vectorial SVG y mapa de bits GD.
  * Opciones de escala, margen y colores personalizables.
  * Metodos de exportacion: `render_svg()`, `render_image()`, `save()`, `toBase64()` y `toDataUri()`.
* Interfaz fluida basada en metodos encadenables mediante `QRCode::make(...)` y `BarCode::make(...)`.
* Registro automatico de alias globales `QRCode` y `BarCode` para compatibilidad retrocompatible.
* Suite de pruebas automatizadas con Pest PHP cubriendo 22 pruebas y 91 aserciones.
* Panel web interactivo en `demo/index.php` con interfaz de fondo blanco, selector de pestanas para QR y Barras, previsualizacion en vivo y generador de codigo PHP.
* Requisito minimo de plataforma fijado en PHP 8.5 o superior con tipado estricto `declare(strict_types=1)`.

