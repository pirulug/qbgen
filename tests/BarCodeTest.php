<?php

declare(strict_types=1);

use Pirulug\QBgen\BarCode;

it("se puede instanciar BarCode mediante constructor o metodo estatico make", function () {
	$barcode1 = new BarCode("12345678", "code128");
	$barcode2 = BarCode::make("12345678", "code128");

	expect($barcode1)->toBeInstanceOf(BarCode::class)
		->and($barcode2)->toBeInstanceOf(BarCode::class);
});

it("genera una imagen GdImage valida con BarCode", function () {
	$barcode = BarCode::make("12345678", "code128")
		->setScale(2)
		->setMargin(15);

	$image = $barcode->render_image();

	expect($image)->toBeInstanceOf(\GdImage::class)
		->and(imagesx($image))->toBeGreaterThan(0)
		->and(imagesy($image))->toBeGreaterThan(0);
});

it("genera marcado vectorial SVG valido con BarCode", function () {
	$barcode = BarCode::make("CODE128-TEST", "code128")
		->setForegroundColor("003366")
		->setBackgroundColor("FFFFFF");

	$svg = $barcode->renderSvg();

	expect($svg)->toBeString()
		->and($svg)->toContain("<svg")
		->and($svg)->toContain("</svg>")
		->and($svg)->toContain("viewBox");
});

it("genera Data URI en formato PNG y SVG para BarCode", function () {
	$barcode = BarCode::make("750123456789", "code128");

	$dataUriPng = $barcode->toDataUri("png");
	$dataUriSvg = $barcode->toDataUri("svg");

	expect($dataUriPng)->toStartWith("data:image/png;base64,")
		->and($dataUriSvg)->toStartWith("data:image/svg+xml;utf8,");
});

it("genera codificacion Base64 valida para BarCode", function () {
	$barcode = BarCode::make("1234567890", "code128");

	$base64 = $barcode->toBase64("png");

	expect($base64)->toBeString()
		->and(base64_decode($base64, true))->not->toBeFalse();
});

it("soporta multiples simbologias de codigos de barra", function () {
	$symbologies = [
		"code128" => "ABC-123",
		"code39"  => "TEST39",
		"ean13"   => "978020137962",
		"upca"    => "01234567890",
		"itf"     => "12345678"
	];

	foreach ($symbologies as $symbology => $data) {
		$barcode = BarCode::make($data, $symbology);
		$image   = $barcode->render_image();
		$svg     = $barcode->renderSvg();

		expect($image)->toBeInstanceOf(\GdImage::class)
			->and($svg)->toContain("<svg");
	}
});

it("soporta fondo transparente en BarCode", function () {
	$barcode = BarCode::make("TRANS-TEST", "code128")
		->setBackgroundColor("transparent");

	$image = $barcode->render_image();
	$svg   = $barcode->renderSvg();

	expect($image)->toBeInstanceOf(\GdImage::class)
		->and($svg)->not->toContain("<rect x=\"0\" y=\"0\"");
});

it("permite guardar el codigo de barras en disco", function () {
	$barcode = BarCode::make("SAVE-TEST", "code128");

	$dir = __DIR__ . "/barcode_output_test";
	if (!is_dir($dir)) {
		mkdir($dir, 0755, true);
	}

	$formats = ["png", "svg", "webp", "jpg"];

	foreach ($formats as $format) {
		$filePath = $dir . "/test_barcode." . $format;
		$saved    = $barcode->save($filePath);

		expect($saved)->toBeTrue()
			->and(file_exists($filePath))->toBeTrue()
			->and(filesize($filePath))->toBeGreaterThan(0);

		unlink($filePath);
	}

	rmdir($dir);
});

it("mantiene compatibilidad con llamadas clasicas por argumentos en BarCode", function () {
	$barcode = new BarCode();

	$image = $barcode->render_image("code128", "LEGACY-123");
	$svg   = $barcode->render_svg("code128", "LEGACY-123");

	expect($image)->toBeInstanceOf(\GdImage::class)
		->and($svg)->toContain("<svg");
});

it("mantiene compatibilidad con el alias global BarCode", function () {
	expect(class_exists("BarCode"))->toBeTrue();

	$barcode = new \BarCode("GLOBAL-ALIAS", "code128");

	expect($barcode)->toBeInstanceOf(BarCode::class)
		->and($barcode->render_image())->toBeInstanceOf(\GdImage::class);
});

