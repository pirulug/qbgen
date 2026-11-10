<?php

declare(strict_types=1);

use Pirulug\QBgen\QRCode;
use Pirulug\QBgen\BarCode;

$autoload = __DIR__ . "/../vendor/autoload.php";
if (file_exists($autoload)) {
	require_once $autoload;
} else {
	require_once __DIR__ . "/../src/QRCode.php";
	require_once __DIR__ . "/../src/BarCode.php";
}

$action = $_GET["action"] ?? "";

if ($action === "preview" || $action === "download") {
	$kind    = $_GET["kind"] ?? "qr";
	$data    = $_GET["data"] ?? "https://ejemplo.com";
	$format  = strtolower($_GET["format"] ?? "png");
	$fg      = $_GET["fg"] ?? "000000";
	$bg      = $_GET["bg"] ?? "FFFFFF";

	if ($kind === "barcode") {
		$symbology = $_GET["symbology"] ?? "code128";
		$scale     = (float) ($_GET["scale"] ?? 2);
		$margin    = (int) ($_GET["margin"] ?? 15);

		$generator = BarCode::make($data, $symbology)
			->setScale($scale)
			->setMargin($margin)
			->setForegroundColor($fg)
			->setBackgroundColor($bg);
	} else {
		$level   = $_GET["level"] ?? "qrm";
		$size    = (int) ($_GET["size"] ?? 280);
		$margin  = (int) ($_GET["margin"] ?? 2);
		$density = (float) ($_GET["density"] ?? 1.0);

		$generator = QRCode::make($data)
			->setSize($size)
			->setMargin($margin)
			->setErrorCorrection($level)
			->setForegroundColor($fg)
			->setBackgroundColor($bg)
			->setDensity($density);
	}

	if ($action === "download") {
		$prefix   = ($kind === "barcode") ? "barcode-" : "qr-";
		$filename = $prefix . date("Ymd-His") . "." . ($format === "svg" ? "svg" : "png");
		header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
	}

	if ($format === "svg") {
		$generator->outputSvg();
	} else {
		$generator->output_image("png");
	}
	exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>QBgen - Generador de Codigos QR y Barras</title>
	<style>
		:root {
			--bg-page: #f8fafc;
			--bg-card: #ffffff;
			--bg-subtle: #f1f5f9;
			--border: #e2e8f0;
			--border-active: #cbd5e1;
			--text-primary: #0f172a;
			--text-secondary: #64748b;
			--accent: #2563eb;
			--accent-hover: #1d4ed8;
			--accent-light: #eff6ff;
			--accent-border: #bfdbfe;
			--font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			--font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
		}

		* {
			box-sizing: border-box;
			margin: 0;
			padding: 0;
		}

		body {
			background-color: var(--bg-page);
			color: var(--text-primary);
			font-family: var(--font-sans);
			min-height: 100vh;
			display: flex;
			flex-direction: column;
		}

		header {
			background-color: var(--bg-card);
			border-bottom: 1px solid var(--border);
			padding: 20px 32px;
		}

		.header-container {
			max-width: 1200px;
			margin: 0 auto;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.brand-title {
			font-size: 22px;
			font-weight: 700;
			letter-spacing: -0.02em;
			color: var(--text-primary);
		}

		.brand-subtitle {
			font-size: 13px;
			color: var(--text-secondary);
			margin-top: 4px;
		}

		.badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 9999px;
			font-size: 12px;
			font-weight: 600;
			background-color: var(--accent-light);
			color: var(--accent);
			border: 1px solid var(--accent-border);
		}

		main {
			flex: 1;
			max-width: 1200px;
			width: 100%;
			margin: 32px auto;
			padding: 0 24px;
			display: grid;
			grid-template-columns: 1.1fr 0.9fr;
			gap: 32px;
		}

		@media (max-width: 900px) {
			main {
				grid-template-columns: 1fr;
			}
		}

		.card {
			background-color: var(--bg-card);
			border: 1px solid var(--border);
			border-radius: 12px;
			padding: 24px;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
		}

		.tab-group {
			display: flex;
			border-bottom: 1px solid var(--border);
			margin-bottom: 24px;
			gap: 8px;
		}

		.tab-btn {
			padding: 10px 18px;
			background: none;
			border: none;
			border-bottom: 2px solid transparent;
			font-size: 14px;
			font-weight: 600;
			color: var(--text-secondary);
			cursor: pointer;
			transition: color 0.15s, border-color 0.15s;
		}

		.tab-btn:hover {
			color: var(--text-primary);
		}

		.tab-btn.active {
			color: var(--accent);
			border-bottom-color: var(--accent);
		}

		.form-group {
			margin-bottom: 18px;
		}

		label {
			display: block;
			font-size: 13px;
			font-weight: 600;
			color: var(--text-primary);
			margin-bottom: 6px;
		}

		input[type="text"],
		input[type="number"],
		textarea,
		select {
			width: 100%;
			padding: 10px 12px;
			background-color: var(--bg-card);
			border: 1px solid var(--border);
			border-radius: 6px;
			color: var(--text-primary);
			font-size: 14px;
			font-family: inherit;
			transition: border-color 0.15s, box-shadow 0.15s;
		}

		input[type="text"]:focus,
		input[type="number"]:focus,
		textarea:focus,
		select:focus {
			border-color: var(--accent);
			outline: none;
			box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
		}

		textarea {
			resize: vertical;
			min-height: 80px;
		}

		.grid-2 {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
		}

		.color-picker-group {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		input[type="color"] {
			-webkit-appearance: none;
			border: 1px solid var(--border);
			width: 40px;
			height: 40px;
			border-radius: 6px;
			cursor: pointer;
			background: none;
			padding: 2px;
		}

		input[type="color"]::-webkit-color-swatch-wrapper {
			padding: 0;
		}

		input[type="color"]::-webkit-color-swatch {
			border: none;
			border-radius: 4px;
		}

		.preview-section {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 32px 20px;
			background-color: var(--bg-subtle);
			border-radius: 8px;
			border: 1px solid var(--border);
			min-height: 300px;
		}

		.preview-image {
			max-width: 100%;
			height: auto;
			border-radius: 6px;
			background-color: #ffffff;
			padding: 12px;
			border: 1px solid var(--border);
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
		}

		.actions-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 12px;
			margin-top: 20px;
		}

		.btn {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 10px 16px;
			font-size: 13px;
			font-weight: 600;
			border-radius: 6px;
			cursor: pointer;
			border: none;
			transition: background-color 0.15s;
			text-decoration: none;
		}

		.btn-primary {
			background-color: var(--accent);
			color: #ffffff;
		}

		.btn-primary:hover {
			background-color: var(--accent-hover);
		}

		.btn-secondary {
			background-color: var(--bg-card);
			color: var(--text-primary);
			border: 1px solid var(--border);
		}

		.btn-secondary:hover {
			background-color: var(--bg-subtle);
		}

		.code-container {
			margin-top: 24px;
			position: relative;
		}

		.code-block {
			background-color: #0f172a;
			border: 1px solid #1e293b;
			border-radius: 6px;
			padding: 14px;
			font-family: var(--font-mono);
			font-size: 12px;
			color: #f8fafc;
			overflow-x: auto;
			white-space: pre;
			line-height: 1.5;
		}

		.copy-btn {
			position: absolute;
			top: 8px;
			right: 8px;
			padding: 4px 8px;
			font-size: 11px;
			background-color: #334155;
			color: #f8fafc;
			border: none;
			border-radius: 4px;
			cursor: pointer;
		}

		.copy-btn:hover {
			background-color: #475569;
		}

		footer {
			border-top: 1px solid var(--border);
			padding: 18px 24px;
			text-align: center;
			font-size: 12px;
			color: var(--text-secondary);
			background-color: var(--bg-card);
		}
	</style>
</head>
<body>
	<header>
		<div class="header-container">
			<div>
				<h1 class="brand-title">QBgen</h1>
				<p class="brand-subtitle">Generador de Codigos QR y Codigos de Barra en PHP 8.5+</p>
			</div>
			<div>
				<span class="badge">PHP 8.5+ GD / SVG</span>
			</div>
		</div>
	</header>

	<main>
		<section class="card">
			<div class="tab-group">
				<button id="tabQr" class="tab-btn active" type="button">Codigo QR (2D)</button>
				<button id="tabBarcode" class="tab-btn" type="button">Codigo de Barras (1D)</button>
			</div>

			<div class="form-group">
				<label for="data">Contenido o Texto</label>
				<textarea id="data" rows="3">https://antigravity.dev</textarea>
			</div>

			<div id="qrOptions">
				<div class="grid-2">
					<div class="form-group">
						<label for="level">Correccion de error</label>
						<select id="level">
							<option value="qrl">L - Bajo (7%)</option>
							<option value="qrm" selected>M - Medio (15%)</option>
							<option value="qrq">Q - Cuartil (25%)</option>
							<option value="qrh">H - Alto (30%)</option>
						</select>
					</div>
					<div class="form-group">
						<label for="size">Tamano total (px)</label>
						<input type="number" id="size" value="280" min="64" max="1024" step="8">
					</div>
				</div>

				<div class="form-group">
					<label for="density">Densidad de modulos (0.1 a 1.0)</label>
					<input type="number" id="density" value="1.0" min="0.3" max="1.0" step="0.05">
				</div>
			</div>

			<div id="barcodeOptions" style="display: none;">
				<div class="grid-2">
					<div class="form-group">
						<label for="symbology">Simbologia de barras</label>
						<select id="symbology">
							<option value="code128" selected>Code 128 (Universal)</option>
							<option value="code39">Code 39</option>
							<option value="ean13">EAN-13 (Comercial)</option>
							<option value="ean8">EAN-8</option>
							<option value="upca">UPC-A</option>
							<option value="upce">UPC-E</option>
							<option value="itf">ITF (Interleaved 2 of 5)</option>
							<option value="codabar">Codabar</option>
							<option value="code93">Code 93</option>
						</select>
					</div>
					<div class="form-group">
						<label for="scale">Factor de escala</label>
						<input type="number" id="scale" value="2" min="1" max="6" step="0.5">
					</div>
				</div>
			</div>

			<div class="grid-2">
				<div class="form-group">
					<label for="format">Formato de salida</label>
					<select id="format">
						<option value="png" selected>PNG (GD Bitmap)</option>
						<option value="svg">SVG (Vectorial)</option>
					</select>
				</div>
				<div class="form-group">
					<label for="margin">Margen perimetral (px)</label>
					<input type="number" id="margin" value="10" min="0" max="60" step="1">
				</div>
			</div>

			<div class="grid-2">
				<div class="form-group">
					<label>Color principal</label>
					<div class="color-picker-group">
						<input type="color" id="fgColorPicker" value="#000000">
						<input type="text" id="fgColorInput" value="000000">
					</div>
				</div>
				<div class="form-group">
					<label>Color de fondo</label>
					<div class="color-picker-group">
						<input type="color" id="bgColorPicker" value="#ffffff">
						<input type="text" id="bgColorInput" value="FFFFFF">
					</div>
				</div>
			</div>
		</section>

		<section class="card">
			<h2 style="font-size: 16px; font-weight: 600; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">Vista Previa y Exportacion</h2>

			<div class="preview-section">
				<img id="previewImage" class="preview-image" src="" alt="Vista previa del codigo">
			</div>

			<div class="actions-grid">
				<a id="btnDownloadPng" href="#" class="btn btn-primary">Descargar PNG</a>
				<a id="btnDownloadSvg" href="#" class="btn btn-secondary">Descargar SVG</a>
				<button id="btnCopyDataUri" type="button" class="btn btn-secondary">Copiar Data URI</button>
				<button id="btnCopyPhp" type="button" class="btn btn-secondary">Copiar Codigo PHP</button>
			</div>

			<div class="code-container">
				<button id="btnCopyPhpSnippet" class="copy-btn" type="button">Copiar</button>
				<pre class="code-block" id="phpSnippet"></pre>
			</div>
		</section>
	</main>

	<footer>
		QBgen &bull; Libreria PHP 8.5+ para Codigos QR y Codigos de Barra &bull; Paquete Composer: pirulug/qbgen
	</footer>

	<script>
		let activeKind = "qr";

		const tabQr = document.getElementById("tabQr");
		const tabBarcode = document.getElementById("tabBarcode");
		const qrOptions = document.getElementById("qrOptions");
		const barcodeOptions = document.getElementById("barcodeOptions");

		const elData = document.getElementById("data");
		const elLevel = document.getElementById("level");
		const elSize = document.getElementById("size");
		const elDensity = document.getElementById("density");
		const elSymbology = document.getElementById("symbology");
		const elScale = document.getElementById("scale");
		const elFormat = document.getElementById("format");
		const elMargin = document.getElementById("margin");

		const elFgPicker = document.getElementById("fgColorPicker");
		const elFgInput = document.getElementById("fgColorInput");
		const elBgPicker = document.getElementById("bgColorPicker");
		const elBgInput = document.getElementById("bgColorInput");

		const elPreview = document.getElementById("previewImage");
		const elBtnDownloadPng = document.getElementById("btnDownloadPng");
		const elBtnDownloadSvg = document.getElementById("btnDownloadSvg");
		const elBtnCopyDataUri = document.getElementById("btnCopyDataUri");
		const elBtnCopyPhp = document.getElementById("btnCopyPhp");
		const elBtnCopyPhpSnippet = document.getElementById("btnCopyPhpSnippet");
		const elPhpSnippet = document.getElementById("phpSnippet");

		function sanitizeHex(value) {
			return value.replace(/[^0-9A-Fa-f]/g, "").slice(0, 6);
		}

		tabQr.addEventListener("click", () => {
			activeKind = "qr";
			tabQr.classList.add("active");
			tabBarcode.classList.remove("active");
			qrOptions.style.display = "block";
			barcodeOptions.style.display = "none";
			if (elData.value === "1234567890") {
				elData.value = "https://antigravity.dev";
			}
			elMargin.value = "4";
			updatePreview();
		});

		tabBarcode.addEventListener("click", () => {
			activeKind = "barcode";
			tabBarcode.classList.add("active");
			tabQr.classList.remove("active");
			barcodeOptions.style.display = "block";
			qrOptions.style.display = "none";
			if (elData.value === "https://antigravity.dev") {
				elData.value = "1234567890";
			}
			elMargin.value = "15";
			updatePreview();
		});

		elFgPicker.addEventListener("input", (e) => {
			elFgInput.value = e.target.value.replace("#", "").toUpperCase();
			updatePreview();
		});

		elFgInput.addEventListener("input", (e) => {
			const clean = sanitizeHex(e.target.value);
			if (clean.length === 6) {
				elFgPicker.value = "#" + clean;
			}
			updatePreview();
		});

		elBgPicker.addEventListener("input", (e) => {
			elBgInput.value = e.target.value.replace("#", "").toUpperCase();
			updatePreview();
		});

		elBgInput.addEventListener("input", (e) => {
			const clean = sanitizeHex(e.target.value);
			if (clean.length === 6) {
				elBgPicker.value = "#" + clean;
			}
			updatePreview();
		});

		[elData, elLevel, elSize, elDensity, elSymbology, elScale, elFormat, elMargin].forEach((elem) => {
			elem.addEventListener("input", updatePreview);
			elem.addEventListener("change", updatePreview);
		});

		function buildQueryParams(formatOverride, action) {
			const params = new URLSearchParams();
			params.set("action", action || "preview");
			params.set("kind", activeKind);
			params.set("data", elData.value || (activeKind === "barcode" ? "12345678" : "https://ejemplo.com"));
			params.set("format", formatOverride || elFormat.value);
			params.set("margin", elMargin.value || "10");
			params.set("fg", sanitizeHex(elFgInput.value) || "000000");
			params.set("bg", sanitizeHex(elBgInput.value) || "FFFFFF");

			if (activeKind === "barcode") {
				params.set("symbology", elSymbology.value);
				params.set("scale", elScale.value || "2");
			} else {
				params.set("level", elLevel.value);
				params.set("size", elSize.value || "280");
				params.set("density", elDensity.value || "1.0");
			}

			return params.toString();
		}

		function updatePreview() {
			const previewQuery = buildQueryParams(elFormat.value, "preview");
			elPreview.src = "index.php?" + previewQuery;

			const pngDownloadQuery = buildQueryParams("png", "download");
			elBtnDownloadPng.href = "index.php?" + pngDownloadQuery;

			const svgDownloadQuery = buildQueryParams("svg", "download");
			elBtnDownloadSvg.href = "index.php?" + svgDownloadQuery;

			updateSnippet();
		}

		function generatePhpSnippet() {
			const data = elData.value || (activeKind === "barcode" ? "12345678" : "https://ejemplo.com");
			const safeData = data.replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
			const margin = elMargin.value || "10";
			const fg = sanitizeHex(elFgInput.value) || "000000";
			const bg = sanitizeHex(elBgInput.value) || "FFFFFF";

			if (activeKind === "barcode") {
				const symbology = elSymbology.value;
				const scale = elScale.value || "2";

				return "<?php\n\n"
					+ "require_once __DIR__ . \"/vendor/autoload.php\";\n\n"
					+ "use Pirulug\\QBgen\\BarCode;\n\n"
					+ "$barcode = BarCode::make(\"" + safeData + "\", \"" + symbology + "\")\n"
					+ "    ->setScale(" + scale + ")\n"
					+ "    ->setMargin(" + margin + ")\n"
					+ "    ->setForegroundColor(\"" + fg + "\")\n"
					+ "    ->setBackgroundColor(\"" + bg + "\");\n\n"
					+ "// Guardar en disco:\n"
					+ "$barcode->save(\"codigo_barras.png\");\n\n"
					+ "// O renderizar directamente en el navegador:\n"
					+ "// $barcode->output_image();\n"
					+ "// $barcode->outputSvg();\n";
			}

			const level = elLevel.value;
			const size = elSize.value || "280";
			const density = elDensity.value || "1.0";

			return "<?php\n\n"
				+ "require_once __DIR__ . \"/vendor/autoload.php\";\n\n"
				+ "use Pirulug\\QBgen\\QRCode;\n\n"
				+ "$qr = QRCode::make(\"" + safeData + "\")\n"
				+ "    ->setSize(" + size + ")\n"
				+ "    ->setMargin(" + margin + ")\n"
				+ "    ->setErrorCorrection(\"" + level + "\")\n"
				+ "    ->setForegroundColor(\"" + fg + "\")\n"
				+ "    ->setBackgroundColor(\"" + bg + "\")\n"
				+ "    ->setDensity(" + density + ");\n\n"
				+ "// Guardar en disco:\n"
				+ "$qr->save(\"codigo_qr.png\");\n\n"
				+ "// O renderizar directamente en el navegador:\n"
				+ "// $qr->output_image();\n"
				+ "// $qr->outputSvg();\n";
		}

		function updateSnippet() {
			elPhpSnippet.textContent = generatePhpSnippet();
		}

		function copyToClipboard(text, button, successText) {
			navigator.clipboard.writeText(text).then(() => {
				const originalText = button.textContent;
				button.textContent = successText;
				setTimeout(() => {
					button.textContent = originalText;
				}, 1800);
			});
		}

		elBtnCopyPhp.addEventListener("click", () => {
			copyToClipboard(generatePhpSnippet(), elBtnCopyPhp, "Copiado");
		});

		elBtnCopyPhpSnippet.addEventListener("click", () => {
			copyToClipboard(generatePhpSnippet(), elBtnCopyPhpSnippet, "Copiado");
		});

		elBtnCopyDataUri.addEventListener("click", () => {
			fetch("index.php?" + buildQueryParams(elFormat.value, "preview"))
				.then((res) => res.blob())
				.then((blob) => {
					const reader = new FileReader();
					reader.onloadend = () => {
						copyToClipboard(reader.result, elBtnCopyDataUri, "URI Copiado");
					};
					reader.readAsDataURL(blob);
				});
		});

		updatePreview();
	</script>
</body>
</html>
