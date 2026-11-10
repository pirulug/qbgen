<?php

declare(strict_types=1);

use Pirulug\QBgen\QRCode;

it("se puede instanciar mediante constructor o metodo estatico make", function () {
	$qr1 = new QRCode("https://antigravity.dev");
	$qr2 = QRCode::make("https://antigravity.dev");

	expect($qr1)->toBeInstanceOf(QRCode::class)
		->and($qr2)->toBeInstanceOf(QRCode::class);
});

it("genera una instancia valida de GdImage con dimensiones esperadas", function () {
	$qr = QRCode::make("https://antigravity.dev")
		->setSize(240);

	$image = $qr->render_image();

	expect($image)->toBeInstanceOf(\GdImage::class)
		->and(imagesx($image))->toBe(240)
		->and(imagesy($image))->toBe(240);
});

it("genera marcado vectorial SVG valido", function () {
	$qr = QRCode::make("https://antigravity.dev")
		->setSize(300)
		->setForegroundColor("003366")
		->setBackgroundColor("FFFFFF");

	$svg = $qr->renderSvg();

	expect($svg)->toBeString()
		->and($svg)->toContain("<svg")
		->and($svg)->toContain("</svg>")
		->and($svg)->toContain("width=\"300\"")
		->and($svg)->toContain("height=\"300\"")
		->and($svg)->toContain("#003366")
		->and($svg)->toContain("#FFFFFF");
});

it("genera Data URI en formato PNG y SVG", function () {
	$qr = QRCode::make("https://antigravity.dev");

	$dataUriPng = $qr->toDataUri("png");
	$dataUriSvg = $qr->toDataUri("svg");

	expect($dataUriPng)->toStartWith("data:image/png;base64,")
		->and($dataUriSvg)->toStartWith("data:image/svg+xml;utf8,");
});

it("genera codificacion Base64 de la imagen", function () {
	$qr = QRCode::make("https://antigravity.dev");

	$base64 = $qr->toBase64("png");

	expect($base64)->toBeString()
		->and(base64_decode($base64, true))->not->toBeFalse();
});

it("exporta una matriz binaria valida", function () {
	$qr = QRCode::make("https://antigravity.dev");

	$matrix = $qr->getMatrix();
	$size   = $qr->getMatrixSize();

	expect($matrix)->toBeArray()
		->and(count($matrix))->toBe($size)
		->and($size)->toBeGreaterThan(0)
		->and($matrix[0])->toBeArray()
		->and(count($matrix[0]))->toBe($size);
});

it("permite configurar distintos niveles de correccion de error", function () {
	$levels = ["L", "M", "Q", "H", "qrl", "qrm", "qrq", "qrh"];

	foreach ($levels as $level) {
		$qr = QRCode::make("Nivel de prueba")->setErrorCorrection($level);
		$image = $qr->render_image();

		expect($image)->toBeInstanceOf(\GdImage::class);
	}
});

it("soporta fondo transparente en GD y SVG", function () {
	$qr = QRCode::make("Prueba transparente")
		->setBackgroundColor("transparent");

	$image = $qr->render_image();
	$svg   = $qr->renderSvg();

	expect($image)->toBeInstanceOf(\GdImage::class)
		->and($svg)->not->toContain("<rect width=\"100%\" height=\"100%\"");
});

it("permite guardar el codigo QR en diferentes formatos de archivo", function () {
	$qr = QRCode::make("Prueba guardado")
		->setSize(200);

	$dir = __DIR__ . "/output_test";
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
	}

	$formats = ["png", "svg", "webp", "jpg"];

	foreach ($formats as $format) {
		$filePath = $dir . "/test_qr." . $format;
		$saved    = $qr->save($filePath);

		expect($saved)->toBeTrue()
			->and(file_exists($filePath))->toBeTrue()
			->and(filesize($filePath))->toBeGreaterThan(0);

		unlink($filePath);
	}

	rmdir($dir);
});

it("permite superponer un logotipo centrado en GD y SVG", function () {
	$logoPath = __DIR__ . "/temp_logo.png";
	$logoImg  = imagecreatetruecolor(30, 30);
	imagepng($logoImg, $logoPath);

	$qr = QRCode::make("Con logo")
		->setErrorCorrection("H")
		->setLogo($logoPath, 0.22);

	$image = $qr->render_image();
	$svg   = $qr->renderSvg();

	unlink($logoPath);

	expect($image)->toBeInstanceOf(\GdImage::class)
		->and($svg)->toContain("<image href=\"data:image/png;base64,");
});

it("mantiene compatibilidad con el alias global QRCode", function () {
	expect(class_exists("QRCode"))->toBeTrue();

	$qr = new \QRCode("https://antigravity.dev", [
		"s"  => "qrm",
		"sf" => 4
	]);

	expect($qr)->toBeInstanceOf(QRCode::class)
		->and($qr->render_image())->toBeInstanceOf(\GdImage::class);
});

it("se ejecuta en un entorno PHP mayor o igual a 8.5", function () {
	expect(version_compare(PHP_VERSION, "8.5.0", ">="))->toBeTrue();
});

