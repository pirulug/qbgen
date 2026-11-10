<?php

declare(strict_types=1);

namespace Pirulug\QBgen;

class BarCode {
	private string $data;
	private string $symbology;
	private array $options;

	public function __construct(string $data = "", string $symbology = "code128", array $options = []) {
		$defaults = [
			"sf" => 1,
			"p"  => 10,
			"bc" => "FFFFFF",
			"cm" => "000000"
		];
		$this->data      = $data;
		$this->symbology = $symbology;
		$this->options   = array_merge($defaults, $options);
	}

	public static function make(string $data = "", string $symbology = "code128", array $options = []): self {
		return new self($data, $symbology, $options);
	}

	public function setData(string $data): self {
		$this->data = $data;
		return $this;
	}

	public function setSymbology(string $symbology): self {
		$this->symbology = $symbology;
		return $this;
	}

	public function setType(string $symbology): self {
		return $this->setSymbology($symbology);
	}

	public function setScale(float $scale): self {
		$this->options["sf"] = $scale;
		$this->options["sx"] = $scale;
		$this->options["sy"] = $scale;
		return $this;
	}

	public function setMargin(int $margin): self {
		$this->options["p"]  = $margin;
		$this->options["pv"] = $margin;
		$this->options["ph"] = $margin;
		$this->options["pt"] = $margin;
		$this->options["pb"] = $margin;
		$this->options["pl"] = $margin;
		$this->options["pr"] = $margin;
		return $this;
	}

	public function setForegroundColor(string $color): self {
		$clean = ltrim($color, "#");
		$this->options["cm"] = $clean;
		return $this;
	}

	public function setBackgroundColor(string $color): self {
		$clean = ltrim($color, "#");
		$this->options["bc"] = (strtolower($clean) === "transparent" || strtolower($clean) === "none") ? "transparent" : $clean;
		return $this;
	}

	public function setOptions(array $options): self {
		$this->options = array_merge($this->options, $options);
		return $this;
	}

	public function renderSvg(?string $symbology = null, ?string $data = null, array $options = []): string {
		return $this->render_svg($symbology, $data, $options);
	}

	public function outputSvg(): void {
		header("Content-Type: image/svg+xml; charset=utf-8");
		echo $this->render_svg();
	}

	public function toBase64(string $format = "png"): string {
		$format = strtolower($format);
		if ($format === "svg") {
			return base64_encode($this->render_svg());
		}

		$image = $this->render_image();
		ob_start();
		if ($format === "webp" && function_exists("imagewebp")) {
			imagewebp($image);
		} elseif ($format === "jpeg" || $format === "jpg") {
			imagejpeg($image);
		} else {
			imagepng($image);
		}
		$binary = ob_get_clean();

		return base64_encode($binary ?: "");
	}

	public function toDataUri(string $format = "png"): string {
		$format = strtolower($format);
		if ($format === "svg") {
			return "data:image/svg+xml;utf8," . rawurlencode($this->render_svg());
		}

		$mime = match ($format) {
			"webp" => "image/webp",
			"jpeg", "jpg" => "image/jpeg",
			default => "image/png"
		};

		return "data:" . $mime . ";base64," . $this->toBase64($format);
	}

	public function save(string $filePath, ?string $format = null, int $quality = -1): bool {
		$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
		$targetFormat = $format ? strtolower($format) : ($ext ?: "png");

		$dir = dirname($filePath);
		if (!is_dir($dir) && !empty($dir)) {
			mkdir($dir, 0755, true);
		}

		if ($targetFormat === "svg") {
			return file_put_contents($filePath, $this->render_svg()) !== false;
		}

		$image = $this->render_image();
		$result = false;

		if ($targetFormat === "webp" && function_exists("imagewebp")) {
			$result = imagewebp($image, $filePath, $quality >= 0 ? $quality : 100);
		} elseif ($targetFormat === "jpeg" || $targetFormat === "jpg") {
			$result = imagejpeg($image, $filePath, $quality >= 0 ? $quality : 100);
		} else {
			$result = imagepng($image, $filePath, $quality >= 0 ? min(9, $quality) : -1);
		}

		return $result;
	}


  public function output_image(string $format = "png", ?string $symbology = null, ?string $data = null, array $options = []): void {
		$symbology = $symbology ?? $this->symbology;
		$data      = $data ?? $this->data;
		$options   = array_merge($this->options, $options);
    switch (strtolower(preg_replace("/[^a-z0-9]/", "", $format))) {
      case "png":
        header("Content-Type: image/png");
        $image = $this->render_image($symbology, $data, $options);
        imagepng($image);
        break;
      case "gif":
        header("Content-Type: image/gif");
        $image = $this->render_image($symbology, $data, $options);
        imagegif($image);
        break;
      case "jpg":
      case "jpe":
      case "jpeg":
        header("Content-Type: image/jpeg");
        $image = $this->render_image($symbology, $data, $options);
        imagejpeg($image);
        break;
      case "svg":
        header("Content-Type: image/svg+xml");
        echo $this->render_svg($symbology, $data, $options);
        break;
    }
  }

  public function render_image(?string $symbology = null, ?string $data = null, array $options = []): \GdImage {
		$symbology = $symbology ?? $this->symbology;
		$data      = $data ?? $this->data;
		$options   = array_merge($this->options, $options);
    [$code, $widths, $width, $height, $x, $y, $w, $h] = $this->encode_and_calculate_size($symbology, $data, $options);

    $image = imagecreatetruecolor($width, $height);
    imagesavealpha($image, true);

    $bgcolor = $this->allocate_color($image, $options["bc"] ?? "FFF");
    imagefill($image, 0, 0, $bgcolor);

    $colors = [
      $options["cs"] ?? "",
      $options["cm"] ?? "000",
      $options["c2"] ?? "F00",
      $options["c3"] ?? "FF0",
      $options["c4"] ?? "0F0",
      $options["c5"] ?? "0FF",
      $options["c6"] ?? "00F",
      $options["c7"] ?? "F0F",
      $options["c8"] ?? "FFF",
      $options["c9"] ?? "000",
    ];

    foreach ($colors as $i => $color) {
      $colors[$i] = $this->allocate_color($image, $color);
    }

    $this->dispatch_render_image($image, $code, $x, $y, $w, $h, $colors, $widths, $options);

    return $image;
  }

  public function render_svg(?string $symbology = null, ?string $data = null, array $options = []): string {
		$symbology = $symbology ?? $this->symbology;
		$data      = $data ?? $this->data;
		$options   = array_merge($this->options, $options);
    [$code, $widths, $width, $height, $x, $y, $w, $h] = $this->encode_and_calculate_size($symbology, $data, $options);

    $svg  = "<svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.1\"";
    $svg .= " width=\"" . $width . "\" height=\"" . $height . "\"";
    $svg .= " viewBox=\"0 0 " . $width . " " . $height . "\"><g>";

    $raw_bg  = $options["bc"] ?? "white";
    $bgcolor = $this->format_svg_color($raw_bg);
    if ($bgcolor && $bgcolor !== "none" && $bgcolor !== "transparent") {
      $svg .= "<rect x=\"0\" y=\"0\"";
      $svg .= " width=\"" . $width . "\" height=\"" . $height . "\"";
      $svg .= " fill=\"" . htmlspecialchars($bgcolor) . "\"/>";
    }

    $colors = [
      $this->format_svg_color($options["cs"] ?? ""),
      $this->format_svg_color($options["cm"] ?? "black"),
      $this->format_svg_color($options["c2"] ?? "#FF0000"),
      $this->format_svg_color($options["c3"] ?? "#FFFF00"),
      $this->format_svg_color($options["c4"] ?? "#00FF00"),
      $this->format_svg_color($options["c5"] ?? "#00FFFF"),
      $this->format_svg_color($options["c6"] ?? "#0000FF"),
      $this->format_svg_color($options["c7"] ?? "#FF00FF"),
      $this->format_svg_color($options["c8"] ?? "white"),
      $this->format_svg_color($options["c9"] ?? "black"),
    ];

    $svg .= $this->dispatch_render_svg($code, $x, $y, $w, $h, $colors, $widths, $options);
    $svg .= "</g></svg>";

    return $svg;
  }

  private function encode_and_calculate_size(string $symbology, string $data, array $options): array {
    $code   = $this->dispatch_encode($symbology, $data, $options);
    $widths = [
      (int) ($options["wq"] ?? 1),
      (int) ($options["wm"] ?? 1),
      (int) ($options["ww"] ?? 3),
      (int) ($options["wn"] ?? 1),
      (int) ($options["w4"] ?? 1),
      (int) ($options["w5"] ?? 1),
      (int) ($options["w6"] ?? 1),
      (int) ($options["w7"] ?? 1),
      (int) ($options["w8"] ?? 1),
      (int) ($options["w9"] ?? 1),
    ];

    $size   = $this->dispatch_calculate_size($code, $widths);
    $scale  = (float) ($options["sf"] ?? 1);
    $scalex = (float) ($options["sx"] ?? $scale);
    $scaley = (float) ($options["sy"] ?? $scale);

    $padding = (int) ($options["p"] ?? 10);
    $vert    = (int) ($options["pv"] ?? $padding);
    $horiz   = (int) ($options["ph"] ?? $padding);
    $top     = (int) ($options["pt"] ?? $vert);
    $left    = (int) ($options["pl"] ?? $horiz);
    $right   = (int) ($options["pr"] ?? $horiz);
    $bottom  = (int) ($options["pb"] ?? $vert);

    $dwidth  = ceil($size[0] * $scalex) + $left + $right;
    $dheight = ceil($size[1] * $scaley) + $top + $bottom;

    $iwidth  = (int) ($options["w"] ?? $dwidth);
    $iheight = (int) ($options["h"] ?? $dheight);
    $swidth  = $iwidth - $left - $right;
    $sheight = $iheight - $top - $bottom;

    return [
      $code, $widths, $iwidth, $iheight,
      $left, $top, $swidth, $sheight
    ];
  }

  private function allocate_color(\GdImage $image, string $color): int|false {
    $color = preg_replace("/[^0-9A-Fa-f]/", "", $color);
    switch (strlen($color)) {
      case 1:
        $v = hexdec($color) * 17;
        return imagecolorallocate($image, $v, $v, $v);
      case 2:
        $v = hexdec($color);
        return imagecolorallocate($image, $v, $v, $v);
      case 3:
        $r = hexdec(substr($color, 0, 1)) * 17;
        $g = hexdec(substr($color, 1, 1)) * 17;
        $b = hexdec(substr($color, 2, 1)) * 17;
        return imagecolorallocate($image, $r, $g, $b);
      case 4:
        $a = hexdec(substr($color, 0, 1)) * 17;
        $r = hexdec(substr($color, 1, 1)) * 17;
        $g = hexdec(substr($color, 2, 1)) * 17;
        $b = hexdec(substr($color, 3, 1)) * 17;
        $a = (int) round((255 - $a) * 127 / 255);
        return imagecolorallocatealpha($image, $r, $g, $b, $a);
      case 6:
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        return imagecolorallocate($image, $r, $g, $b);
      case 8:
        $a = hexdec(substr($color, 0, 2));
        $r = hexdec(substr($color, 2, 2));
        $g = hexdec(substr($color, 4, 2));
        $b = hexdec(substr($color, 6, 2));
        $a = (int) round((255 - $a) * 127 / 255);
        return imagecolorallocatealpha($image, $r, $g, $b, $a);
      default:
        return imagecolorallocatealpha($image, 0, 0, 0, 127);
    }
  }

  private function format_svg_color(string $color): string {
    $color = trim($color);
    if ($color === "" || $color === "transparent" || $color === "none") {
      return $color;
    }
    if (preg_match("/^[0-9A-Fa-f]{3,8}\$/", $color)) {
      return "#" . $color;
    }
    return $color;
  }

  private function dispatch_encode(string $symbology, string $data, array $options): ?array {
    switch (strtolower(preg_replace("/[^a-z0-9]/", "", $symbology))) {
      case "upca":
        return $this->upc_a_encode($data);
      case "upce":
        return $this->upc_e_encode($data);
      case "ean13nopad":
        return $this->ean_13_encode($data, " ");
      case "ean13pad":
      case "ean13":
        return $this->ean_13_encode($data, ">");
      case "ean8":
        return $this->ean_8_encode($data);
      case "code39":
        return $this->code_39_encode($data);
      case "code39ascii":
        return $this->code_39_ascii_encode($data);
      case "code93":
        return $this->code_93_encode($data);
      case "code93ascii":
        return $this->code_93_ascii_encode($data);
      case "code128":
        return $this->code_128_encode($data, 0, false);
      case "code128a":
        return $this->code_128_encode($data, 1, false);
      case "code128b":
        return $this->code_128_encode($data, 2, false);
      case "code128c":
        return $this->code_128_encode($data, 3, false);
      case "code128ac":
        return $this->code_128_encode($data, -1, false);
      case "code128bc":
        return $this->code_128_encode($data, -2, false);
      case "ean128":
        return $this->code_128_encode($data, 0, true);
      case "ean128a":
        return $this->code_128_encode($data, 1, true);
      case "ean128b":
        return $this->code_128_encode($data, 2, true);
      case "ean128c":
        return $this->code_128_encode($data, 3, true);
      case "ean128ac":
        return $this->code_128_encode($data, -1, true);
      case "ean128bc":
        return $this->code_128_encode($data, -2, true);
      case "codabar":
        return $this->codabar_encode($data);
      case "itf":
      case "itf14":
        return $this->itf_encode($data);
    }
    return null;
  }

  private function dispatch_calculate_size(?array $code, array $widths): array {
    if ($code && isset($code["g"]) && $code["g"] === "l") {
      return $this->linear_calculate_size($code, $widths);
    }
    return [0, 0];
  }

  private function dispatch_render_image(\GdImage $image, ?array $code, float $x, float $y, float $w, float $h, array $colors, array $widths, array $options): void {
    if ($code && isset($code["g"]) && $code["g"] === "l") {
      $this->linear_render_image($image, $code, $x, $y, $w, $h, $colors, $widths, $options);
    }
  }

  private function dispatch_render_svg(?array $code, float $x, float $y, float $w, float $h, array $colors, array $widths, array $options): string {
    if ($code && isset($code["g"]) && $code["g"] === "l") {
      return $this->linear_render_svg($code, $x, $y, $w, $h, $colors, $widths, $options);
    }
    return "";
  }

  private function linear_calculate_size(array $code, array $widths): array {
    $width = 0;
    foreach ($code["b"] as $block) {
      foreach ($block["m"] as $module) {
        $width += $module[1] * $widths[$module[2]];
      }
    }
    return [$width, 80];
  }

  private function linear_render_image(\GdImage $image, array $code, float $x, float $y, float $w, float $h, array $colors, array $widths, array $options): void {
    $textheight = (int) ($options["th"] ?? 10);
    $textsize   = (int) ($options["ts"] ?? 1);
    $textcolor  = $this->allocate_color($image, $options["tc"] ?? "000");

    $width = 0;
    foreach ($code["b"] as $block) {
      foreach ($block["m"] as $module) {
        $width += $module[1] * $widths[$module[2]];
      }
    }

    if ($width > 0) {
      $scale = $w / $width;
      $scale = ($scale > 1) ? floor($scale) : 1;
      $x     = floor($x + ($w - $width * $scale) / 2);
    } else {
      $scale = 1;
      $x     = floor($x + $w / 2);
    }

    foreach ($code["b"] as $block) {
      if (isset($block["l"])) {
        $label = $block["l"][0];
        $ly    = (float) ($block["l"][1] ?? 1);
        $lx    = (float) ($block["l"][2] ?? 0.5);
        $my    = round($y + min($h, $h + ($ly - 1) * $textheight));
        $ly    = ($y + $h + $ly * $textheight);
        $ly    = round($ly - imagefontheight($textsize));
      } else {
        $label = null;
        $my    = $y + $h;
      }
      $mx = $x;
      foreach ($block["m"] as $module) {
        $mc = $colors[$module[0]];
        $mw = $mx + $module[1] * $widths[$module[2]] * $scale;
        imagefilledrectangle($image, (int) $mx, (int) $y, (int) ($mw - 1), (int) ($my - 1), $mc);
        $mx = $mw;
      }
      if ($label !== null) {
        $lx = ($x + ($mx - $x) * $lx);
        $lw = imagefontwidth($textsize) * strlen((string) $label);
        $lx = round($lx - $lw / 2);
        imagestring($image, $textsize, (int) $lx, (int) $ly, (string) $label, $textcolor);
      }
      $x = $mx;
    }
  }

  private function linear_render_svg(array $code, float $x, float $y, float $w, float $h, array $colors, array $widths, array $options): string {
    $textheight = (int) ($options["th"] ?? 10);
    $textfont   = $options["tf"] ?? "monospace";
    $textsize   = (int) ($options["ts"] ?? 10);
    $textcolor  = $this->format_svg_color($options["tc"] ?? "black");

    $width = 0;
    foreach ($code["b"] as $block) {
      foreach ($block["m"] as $module) {
        $width += $module[1] * $widths[$module[2]];
      }
    }

    if ($width > 0) {
      $scale = $w / $width;
      if ($scale > 1) {
        $scale = floor($scale);
        $x     = floor($x + ($w - $width * $scale) / 2);
      }
    } else {
      $scale = 1;
      $x     = floor($x + $w / 2);
    }

    $tx = "translate(" . $x . " " . $y . ")";
    if ($scale != 1) {
      $tx .= " scale(" . $scale . " 1)";
    }

    $svg = "<g transform=\"" . htmlspecialchars($tx) . "\">";
    $x   = 0;
    foreach ($code["b"] as $block) {
      if (isset($block["l"])) {
        $label = $block["l"][0];
        $ly    = (float) ($block["l"][1] ?? 1);
        $lx    = (float) ($block["l"][2] ?? 0.5);
        $mh    = min($h, $h + ($ly - 1) * $textheight);
        $ly    = $h + $ly * $textheight;
      } else {
        $label = null;
        $mh    = $h;
      }
      $svg .= "<g>";
      $mx = $x;
      foreach ($block["m"] as $module) {
        $mc = htmlspecialchars($colors[$module[0]]);
        $mw = $module[1] * $widths[$module[2]];
        if ($mc) {
          $svg .= "<rect";
          $svg .= " x=\"" . $mx . "\" y=\"0\"";
          $svg .= " width=\"" . $mw . "\"";
          $svg .= " height=\"" . $mh . "\"";
          $svg .= " fill=\"" . $mc . "\"/>";
        }
        $mx += $mw;
      }
      if ($label !== null) {
        $lx = ($x + ($mx - $x) * $lx);
        $svg .= "<text";
        $svg .= " x=\"" . $lx . "\" y=\"" . $ly . "\"";
        $svg .= " text-anchor=\"middle\"";
        $svg .= " font-family=\"" . htmlspecialchars($textfont) . "\"";
        $svg .= " font-size=\"" . htmlspecialchars((string) $textsize) . "\"";
        $svg .= " fill=\"" . htmlspecialchars($textcolor) . "\">";
        $svg .= htmlspecialchars((string) $label);
        $svg .= "</text>";
      }
      $svg .= "</g>";
      $x = $mx;
    }
    return $svg . "</g>";
  }

  private function upc_a_encode(string $data): array {
    $data   = $this->upc_a_normalize($data);
    $blocks = [];
    $digit  = substr($data, 0, 1);

    $blocks[] = [
      "m" => [[0, 9, 0]],
      "l" => [$digit, 0, 1 / 3]
    ];
    $blocks[] = [
      "m" => [
        [1, 1, 1],
        [0, 1, 1],
        [1, 1, 1],
      ]
    ];
    $blocks[] = [
      "m" => [
        [0, $this->upc_alphabet[$digit][0], 1],
        [1, $this->upc_alphabet[$digit][1], 1],
        [0, $this->upc_alphabet[$digit][2], 1],
        [1, $this->upc_alphabet[$digit][3], 1],
      ]
    ];

    for ($i = 1; $i < 6; $i++) {
      $digit    = substr($data, $i, 1);
      $blocks[] = [
        "m" => [
          [0, $this->upc_alphabet[$digit][0], 1],
          [1, $this->upc_alphabet[$digit][1], 1],
          [0, $this->upc_alphabet[$digit][2], 1],
          [1, $this->upc_alphabet[$digit][3], 1],
        ],
        "l" => [$digit, 0.5, (6 - $i) / 6]
      ];
    }

    $blocks[] = [
      "m" => [
        [0, 1, 1], [1, 1, 1], [0, 1, 1], [1, 1, 1], [0, 1, 1],
      ]
    ];

    for ($i = 6; $i < 11; $i++) {
      $digit    = substr($data, $i, 1);
      $blocks[] = [
        "m" => [
          [1, $this->upc_alphabet[$digit][0], 1],
          [0, $this->upc_alphabet[$digit][1], 1],
          [1, $this->upc_alphabet[$digit][2], 1],
          [0, $this->upc_alphabet[$digit][3], 1],
        ],
        "l" => [$digit, 0.5, (11 - $i) / 6]
      ];
    }

    $digit    = substr($data, 11, 1);
    $blocks[] = [
      "m" => [
        [1, $this->upc_alphabet[$digit][0], 1],
        [0, $this->upc_alphabet[$digit][1], 1],
        [1, $this->upc_alphabet[$digit][2], 1],
        [0, $this->upc_alphabet[$digit][3], 1],
      ]
    ];

    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 1], [1, 1, 1],
      ]
    ];
    $blocks[] = [
      "m" => [[0, 9, 0]],
      "l" => [$digit, 0, 2 / 3]
    ];

    return ["g" => "l", "b" => $blocks];
  }

  private function upc_e_encode(string $data): array {
    $data     = $this->upc_e_normalize($data);
    $blocks   = [];
    $blocks[] = ["m" => [[0, 9, 0]]];
    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 1], [1, 1, 1],
      ]
    ];

    $system = (int) substr($data, 0, 1) & 1;
    $check  = substr($data, 7, 1);
    $pbits  = $this->upc_parity[$check];

    for ($i = 1; $i < 7; $i++) {
      $digit    = substr($data, $i, 1);
      $pbit     = $pbits[$i - 1] ^ $system;
      $blocks[] = [
        "m" => [
          [0, $this->upc_alphabet[$digit][$pbit ? 3 : 0], 1],
          [1, $this->upc_alphabet[$digit][$pbit ? 2 : 1], 1],
          [0, $this->upc_alphabet[$digit][$pbit ? 1 : 2], 1],
          [1, $this->upc_alphabet[$digit][$pbit ? 0 : 3], 1],
        ],
        "l" => [$digit, 0.5, (7 - $i) / 7]
      ];
    }

    $blocks[] = [
      "m" => [
        [0, 1, 1], [1, 1, 1], [0, 1, 1],
        [1, 1, 1], [0, 1, 1], [1, 1, 1],
      ]
    ];
    $blocks[] = ["m" => [[0, 9, 0]]];

    return ["g" => "l", "b" => $blocks];
  }

  private function ean_13_encode(string $data, string $pad): array {
    $data   = $this->ean_13_normalize($data);
    $blocks = [];
    $system = substr($data, 0, 1);
    $pbits  = (int) $system ? $this->upc_parity[$system] : [1, 1, 1, 1, 1, 1];

    $blocks[] = [
      "m" => [[0, 9, 0]],
      "l" => [$system, 0.5, 1 / 3]
    ];
    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 1], [1, 1, 1],
      ]
    ];

    for ($i = 1; $i < 7; $i++) {
      $digit    = substr($data, $i, 1);
      $pbit     = $pbits[$i - 1];
      $blocks[] = [
        "m" => [
          [0, $this->upc_alphabet[$digit][$pbit ? 0 : 3], 1],
          [1, $this->upc_alphabet[$digit][$pbit ? 1 : 2], 1],
          [0, $this->upc_alphabet[$digit][$pbit ? 2 : 1], 1],
          [1, $this->upc_alphabet[$digit][$pbit ? 3 : 0], 1],
        ],
        "l" => [$digit, 0.5, (7 - $i) / 7]
      ];
    }

    $blocks[] = [
      "m" => [
        [0, 1, 1], [1, 1, 1], [0, 1, 1],
        [1, 1, 1], [0, 1, 1],
      ]
    ];

    for ($i = 7; $i < 13; $i++) {
      $digit    = substr($data, $i, 1);
      $blocks[] = [
        "m" => [
          [1, $this->upc_alphabet[$digit][0], 1],
          [0, $this->upc_alphabet[$digit][1], 1],
          [1, $this->upc_alphabet[$digit][2], 1],
          [0, $this->upc_alphabet[$digit][3], 1],
        ],
        "l" => [$digit, 0.5, (13 - $i) / 7]
      ];
    }

    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 1], [1, 1, 1],
      ]
    ];
    $blocks[] = [
      "m" => [[0, 9, 0]],
      "l" => [$pad, 0.5, 2 / 3]
    ];

    return ["g" => "l", "b" => $blocks];
  }

  private function ean_8_encode(string $data): array {
    $data   = $this->ean_8_normalize($data);
    $blocks = [];

    $blocks[] = [
      "m" => [[0, 9, 0]],
      "l" => ["<", 0.5, 1 / 3]
    ];
    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 1], [1, 1, 1],
      ]
    ];

    for ($i = 0; $i < 4; $i++) {
      $digit    = substr($data, $i, 1);
      $blocks[] = [
        "m" => [
          [0, $this->upc_alphabet[$digit][0], 1],
          [1, $this->upc_alphabet[$digit][1], 1],
          [0, $this->upc_alphabet[$digit][2], 1],
          [1, $this->upc_alphabet[$digit][3], 1],
        ],
        "l" => [$digit, 0.5, (4 - $i) / 5]
      ];
    }

    $blocks[] = [
      "m" => [
        [0, 1, 1], [1, 1, 1], [0, 1, 1], [1, 1, 1], [0, 1, 1],
      ]
    ];

    for ($i = 4; $i < 8; $i++) {
      $digit    = substr($data, $i, 1);
      $blocks[] = [
        "m" => [
          [1, $this->upc_alphabet[$digit][0], 1],
          [0, $this->upc_alphabet[$digit][1], 1],
          [1, $this->upc_alphabet[$digit][2], 1],
          [0, $this->upc_alphabet[$digit][3], 1],
        ],
        "l" => [$digit, 0.5, (8 - $i) / 5]
      ];
    }

    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 1], [1, 1, 1],
      ]
    ];
    $blocks[] = [
      "m" => [[0, 9, 0]],
      "l" => [">", 0.5, 2 / 3]
    ];

    return ["g" => "l", "b" => $blocks];
  }

  private function upc_a_normalize(string $data): string {
    $data = preg_replace("/[^0-9*]/", "", $data);
    if (strlen($data) < 5) {
      $data = str_repeat("0", 12);
    } else if (strlen($data) < 12) {
      $system   = substr($data, 0, 1);
      $edata    = substr($data, 1, -2);
      $epattern = (int) substr($data, -2, 1);
      $check    = substr($data, -1);
      if ($epattern < 3) {
        $left  = $system . substr($edata, 0, 2) . $epattern;
        $right = substr($edata, 2) . $check;
      } else if ($epattern < strlen($edata)) {
        $left  = $system . substr($edata, 0, $epattern);
        $right = substr($edata, $epattern) . $check;
      } else {
        $left  = $system . $edata;
        $right = $epattern . $check;
      }
      $center = str_repeat("0", 12 - strlen($left . $right));
      $data   = $left . $center . $right;
    } else if (strlen($data) > 12) {
      $left  = substr($data, 0, 6);
      $right = substr($data, -6);
      $data  = $left . $right;
    }

    while (($o = strrpos($data, "*")) !== false) {
      $checksum = 0;
      for ($i = 0; $i < 12; $i++) {
        $digit = (int) substr($data, $i, 1);
        $checksum += (($i % 2) ? 1 : 3) * $digit;
      }
      $checksum *= (($o % 2) ? 9 : 3);
      $left   = substr($data, 0, $o);
      $center = substr((string) $checksum, -1);
      $right  = substr($data, $o + 1);
      $data   = $left . $center . $right;
    }
    return $data;
  }

  private function upc_e_normalize(string $data): string {
    $data = preg_replace("/[^0-9*]/", "", $data);
    if (preg_match("/^([01])([0-9]{6})([0-9])\$/", $data, $m)) {
      return $data;
    }
    if (preg_match("/^([01])([0-9]{6})([*])\$/", $data, $m)) {
      $data = $this->upc_a_normalize($data);
      return $m[1] . $m[2] . substr($data, -1);
    }
    $data = $this->upc_a_normalize($data);
    if (preg_match("/^([01])([0-9]{2})([0-2])0000([0-9]{3})([0-9])\$/", $data, $m)) {
      return $m[1] . $m[2] . $m[4] . $m[3] . $m[5];
    }
    if (preg_match("/^([01])([0-9]{3})00000([0-9]{2})([0-9])\$/", $data, $m)) {
      return $m[1] . $m[2] . $m[3] . "3" . $m[4];
    }
    if (preg_match("/^([01])([0-9]{4})00000([0-9])([0-9])\$/", $data, $m)) {
      return $m[1] . $m[2] . $m[3] . "4" . $m[4];
    }
    if (preg_match("/^([01])([0-9]{5})0000([5-9])([0-9])\$/", $data, $m)) {
      return $m[1] . $m[2] . $m[3] . $m[4];
    }
    return str_repeat("0", 8);
  }

  private function ean_13_normalize(string $data): string {
    $data = preg_replace("/[^0-9*]/", "", $data);
    if (strlen($data) < 13) {
      return "0" . $this->upc_a_normalize($data);
    } else if (strlen($data) > 13) {
      $left  = substr($data, 0, 7);
      $right = substr($data, -6);
      $data  = $left . $right;
    }

    while (($o = strrpos($data, "*")) !== false) {
      $checksum = 0;
      for ($i = 0; $i < 13; $i++) {
        $digit = (int) substr($data, $i, 1);
        $checksum += (($i % 2) ? 3 : 1) * $digit;
      }
      $checksum *= (($o % 2) ? 3 : 9);
      $left   = substr($data, 0, $o);
      $center = substr((string) $checksum, -1);
      $right  = substr($data, $o + 1);
      $data   = $left . $center . $right;
    }
    return $data;
  }

  private function ean_8_normalize(string $data): string {
    $data = preg_replace("/[^0-9*]/", "", $data);
    if (strlen($data) < 8) {
      $midpoint = (int) floor(strlen($data) / 2);
      $left     = substr($data, 0, $midpoint);
      $center   = str_repeat("0", 8 - strlen($data));
      $right    = substr($data, $midpoint);
      $data     = $left . $center . $right;
    } else if (strlen($data) > 8) {
      $left  = substr($data, 0, 4);
      $right = substr($data, -4);
      $data  = $left . $right;
    }

    while (($o = strrpos($data, "*")) !== false) {
      $checksum = 0;
      for ($i = 0; $i < 8; $i++) {
        $digit = (int) substr($data, $i, 1);
        $checksum += (($i % 2) ? 1 : 3) * $digit;
      }
      $checksum *= (($o % 2) ? 9 : 3);
      $left   = substr($data, 0, $o);
      $center = substr((string) $checksum, -1);
      $right  = substr($data, $o + 1);
      $data   = $left . $center . $right;
    }
    return $data;
  }

  private array $upc_alphabet = [
    "0" => [3, 2, 1, 1], "1" => [2, 2, 2, 1], "2" => [2, 1, 2, 2],
    "3" => [1, 4, 1, 1], "4" => [1, 1, 3, 2], "5" => [1, 2, 3, 1],
    "6" => [1, 1, 1, 4], "7" => [1, 3, 1, 2], "8" => [1, 2, 1, 3],
    "9" => [3, 1, 1, 2],
  ];

  private array $upc_parity = [
    "0" => [1, 1, 1, 0, 0, 0], "1" => [1, 1, 0, 1, 0, 0],
    "2" => [1, 1, 0, 0, 1, 0], "3" => [1, 1, 0, 0, 0, 1],
    "4" => [1, 0, 1, 1, 0, 0], "5" => [1, 0, 0, 1, 1, 0],
    "6" => [1, 0, 0, 0, 1, 1], "7" => [1, 0, 1, 0, 1, 0],
    "8" => [1, 0, 1, 0, 0, 1], "9" => [1, 0, 0, 1, 0, 1],
  ];

  private function code_39_encode(string $data): array {
    $data   = strtoupper(preg_replace("/[^0-9A-Za-z%\$\/+ .-]/", "", $data));
    $blocks = [];

    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 2], [1, 1, 1],
        [0, 1, 1], [1, 1, 2], [0, 1, 1],
        [1, 1, 2], [0, 1, 1], [1, 1, 1],
      ],
      "l" => ["*"]
    ];

    for ($i = 0, $n = strlen($data); $i < $n; $i++) {
      $blocks[] = ["m" => [[0, 1, 3]]];
      $char     = substr($data, $i, 1);
      $block    = $this->code_39_alphabet[$char];
      $blocks[] = [
        "m" => [
          [1, 1, $block[0]], [0, 1, $block[1]], [1, 1, $block[2]],
          [0, 1, $block[3]], [1, 1, $block[4]], [0, 1, $block[5]],
          [1, 1, $block[6]], [0, 1, $block[7]], [1, 1, $block[8]],
        ],
        "l" => [$char]
      ];
    }

    $blocks[] = ["m" => [[0, 1, 3]]];
    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 2], [1, 1, 1],
        [0, 1, 1], [1, 1, 2], [0, 1, 1],
        [1, 1, 2], [0, 1, 1], [1, 1, 1],
      ],
      "l" => ["*"]
    ];

    return ["g" => "l", "b" => $blocks];
  }

  private function code_39_ascii_encode(string $data): array {
    $modules   = [];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 2];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 2];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 2];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];

    $label = "";
    for ($i = 0, $n = strlen($data); $i < $n; $i++) {
      $char = substr($data, $i, 1);
      $ch   = ord($char);
      if ($ch < 128) {
        if ($ch < 32 || $ch >= 127) {
          $label .= " ";
        } else {
          $label .= $char;
        }
        $ch_str = $this->code_39_asciibet[$ch];
        for ($j = 0, $m = strlen($ch_str); $j < $m; $j++) {
          $c         = substr($ch_str, $j, 1);
          $b         = $this->code_39_alphabet[$c];
          $modules[] = [0, 1, 3];
          $modules[] = [1, 1, $b[0]];
          $modules[] = [0, 1, $b[1]];
          $modules[] = [1, 1, $b[2]];
          $modules[] = [0, 1, $b[3]];
          $modules[] = [1, 1, $b[4]];
          $modules[] = [0, 1, $b[5]];
          $modules[] = [1, 1, $b[6]];
          $modules[] = [0, 1, $b[7]];
          $modules[] = [1, 1, $b[8]];
        }
      }
    }

    $modules[] = [0, 1, 3];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 2];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 2];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 2];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];

    $blocks = [["m" => $modules, "l" => [$label]]];
    return ["g" => "l", "b" => $blocks];
  }

  private function code_93_encode(string $data): array {
    $data      = strtoupper(preg_replace("/[^0-9A-Za-z%+\/\$ .-]/", "", $data));
    $modules   = [];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 4, 1];
    $modules[] = [0, 1, 1];

    $values = [];
    for ($i = 0, $n = strlen($data); $i < $n; $i++) {
      $char      = substr($data, $i, 1);
      $block     = $this->code_93_alphabet[$char];
      $modules[] = [1, $block[0], 1];
      $modules[] = [0, $block[1], 1];
      $modules[] = [1, $block[2], 1];
      $modules[] = [0, $block[3], 1];
      $modules[] = [1, $block[4], 1];
      $modules[] = [0, $block[5], 1];
      $values[]  = $block[6];
    }

    for ($i = 0; $i < 2; $i++) {
      $index    = count($values);
      $weight   = 0;
      $checksum = 0;
      while ($index) {
        $index--;
        $weight++;
        $checksum += $weight * $values[$index];
        $checksum %= 47;
        $weight   %= ($i ? 15 : 20);
      }
      $values[] = $checksum;
    }

    $alphabet = array_values($this->code_93_alphabet);
    for ($i = count($values) - 2, $n = count($values); $i < $n; $i++) {
      $block     = $alphabet[$values[$i]];
      $modules[] = [1, $block[0], 1];
      $modules[] = [0, $block[1], 1];
      $modules[] = [1, $block[2], 1];
      $modules[] = [0, $block[3], 1];
      $modules[] = [1, $block[4], 1];
      $modules[] = [0, $block[5], 1];
    }

    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 4, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];

    $blocks = [["m" => $modules, "l" => [$data]]];
    return ["g" => "l", "b" => $blocks];
  }

  private function code_93_ascii_encode(string $data): array {
    $modules   = [];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 4, 1];
    $modules[] = [0, 1, 1];

    $label  = "";
    $values = [];
    for ($i = 0, $n = strlen($data); $i < $n; $i++) {
      $char = substr($data, $i, 1);
      $ch   = ord($char);
      if ($ch < 128) {
        if ($ch < 32 || $ch >= 127) {
          $label .= " ";
        } else {
          $label .= $char;
        }
        $ch_str = $this->code_93_asciibet[$ch];
        for ($j = 0, $m = strlen($ch_str); $j < $m; $j++) {
          $c         = substr($ch_str, $j, 1);
          $b         = $this->code_93_alphabet[$c];
          $modules[] = [1, $b[0], 1];
          $modules[] = [0, $b[1], 1];
          $modules[] = [1, $b[2], 1];
          $modules[] = [0, $b[3], 1];
          $modules[] = [1, $b[4], 1];
          $modules[] = [0, $b[5], 1];
          $values[]  = $b[6];
        }
      }
    }

    for ($i = 0; $i < 2; $i++) {
      $index    = count($values);
      $weight   = 0;
      $checksum = 0;
      while ($index) {
        $index--;
        $weight++;
        $checksum += $weight * $values[$index];
        $checksum %= 47;
        $weight   %= ($i ? 15 : 20);
      }
      $values[] = $checksum;
    }

    $alphabet = array_values($this->code_93_alphabet);
    for ($i = count($values) - 2, $n = count($values); $i < $n; $i++) {
      $block     = $alphabet[$values[$i]];
      $modules[] = [1, $block[0], 1];
      $modules[] = [0, $block[1], 1];
      $modules[] = [1, $block[2], 1];
      $modules[] = [0, $block[3], 1];
      $modules[] = [1, $block[4], 1];
      $modules[] = [0, $block[5], 1];
    }

    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 4, 1];
    $modules[] = [0, 1, 1];
    $modules[] = [1, 1, 1];

    $blocks = [["m" => $modules, "l" => [$label]]];
    return ["g" => "l", "b" => $blocks];
  }

  private array $code_39_alphabet = [
    "1" => [2, 1, 1, 2, 1, 1, 1, 1, 2], "2" => [1, 1, 2, 2, 1, 1, 1, 1, 2],
    "3" => [2, 1, 2, 2, 1, 1, 1, 1, 1], "4" => [1, 1, 1, 2, 2, 1, 1, 1, 2],
    "5" => [2, 1, 1, 2, 2, 1, 1, 1, 1], "6" => [1, 1, 2, 2, 2, 1, 1, 1, 1],
    "7" => [1, 1, 1, 2, 1, 1, 2, 1, 2], "8" => [2, 1, 1, 2, 1, 1, 2, 1, 1],
    "9" => [1, 1, 2, 2, 1, 1, 2, 1, 1], "0" => [1, 1, 1, 2, 2, 1, 2, 1, 1],
    "A" => [2, 1, 1, 1, 1, 2, 1, 1, 2], "B" => [1, 1, 2, 1, 1, 2, 1, 1, 2],
    "C" => [2, 1, 2, 1, 1, 2, 1, 1, 1], "D" => [1, 1, 1, 1, 2, 2, 1, 1, 2],
    "E" => [2, 1, 1, 1, 2, 2, 1, 1, 1], "F" => [1, 1, 2, 1, 2, 2, 1, 1, 1],
    "G" => [1, 1, 1, 1, 1, 2, 2, 1, 2], "H" => [2, 1, 1, 1, 1, 2, 2, 1, 1],
    "I" => [1, 1, 2, 1, 1, 2, 2, 1, 1], "J" => [1, 1, 1, 1, 2, 2, 2, 1, 1],
    "K" => [2, 1, 1, 1, 1, 1, 1, 2, 2], "L" => [1, 1, 2, 1, 1, 1, 1, 2, 2],
    "M" => [2, 1, 2, 1, 1, 1, 1, 2, 1], "N" => [1, 1, 1, 1, 2, 1, 1, 2, 2],
    "O" => [2, 1, 1, 1, 2, 1, 1, 2, 1], "P" => [1, 1, 2, 1, 2, 1, 1, 2, 1],
    "Q" => [1, 1, 1, 1, 1, 1, 2, 2, 2], "R" => [2, 1, 1, 1, 1, 1, 2, 2, 1],
    "S" => [1, 1, 2, 1, 1, 1, 2, 2, 1], "T" => [1, 1, 1, 1, 2, 1, 2, 2, 1],
    "U" => [2, 2, 1, 1, 1, 1, 1, 1, 2], "V" => [1, 2, 2, 1, 1, 1, 1, 1, 2],
    "W" => [2, 2, 2, 1, 1, 1, 1, 1, 1], "X" => [1, 2, 1, 1, 2, 1, 1, 1, 2],
    "Y" => [2, 2, 1, 1, 2, 1, 1, 1, 1], "Z" => [1, 2, 2, 1, 2, 1, 1, 1, 1],
    "-" => [1, 2, 1, 1, 1, 1, 2, 1, 2], "." => [2, 2, 1, 1, 1, 1, 2, 1, 1],
    " " => [1, 2, 2, 1, 1, 1, 2, 1, 1], "*" => [1, 2, 1, 1, 2, 1, 2, 1, 1],
    "+" => [1, 2, 1, 1, 1, 2, 1, 2, 1], "/" => [1, 2, 1, 2, 1, 1, 1, 2, 1],
    "\$" => [1, 2, 1, 2, 1, 2, 1, 1, 1], "%" => [1, 1, 1, 2, 1, 2, 1, 2, 1],
  ];

  private array $code_39_asciibet = [
    "%U", "\$A", "\$B", "\$C", "\$D", "\$E", "\$F", "\$G",
    "\$H", "\$I", "\$J", "\$K", "\$L", "\$M", "\$N", "\$O",
    "\$P", "\$Q", "\$R", "\$S", "\$T", "\$U", "\$V", "\$W",
    "\$X", "\$Y", "\$Z", "%A", "%B", "%C", "%D", "%E",
    " ", "/A", "/B", "/C", "/D", "/E", "/F", "/G",
    "/H", "/I", "/J", "/K", "/L", "-", ".", "/O",
    "0", "1", "2", "3", "4", "5", "6", "7",
    "8", "9", "/Z", "%F", "%G", "%H", "%I", "%J",
    "%V", "A", "B", "C", "D", "E", "F", "G",
    "H", "I", "J", "K", "L", "M", "N", "O",
    "P", "Q", "R", "S", "T", "U", "V", "W",
    "X", "Y", "Z", "%K", "%L", "%M", "%N", "%O",
    "%W", "+A", "+B", "+C", "+D", "+E", "+F", "+G",
    "+H", "+I", "+J", "+K", "+L", "+M", "+N", "+O",
    "+P", "+Q", "+R", "+S", "+T", "+U", "+V", "+W",
    "+X", "+Y", "+Z", "%P", "%Q", "%R", "%S", "%T",
  ];

  private array $code_93_alphabet = [
    "0" => [1, 3, 1, 1, 1, 2, 0], "1" => [1, 1, 1, 2, 1, 3, 1],
    "2" => [1, 1, 1, 3, 1, 2, 2], "3" => [1, 1, 1, 4, 1, 1, 3],
    "4" => [1, 2, 1, 1, 1, 3, 4], "5" => [1, 2, 1, 2, 1, 2, 5],
    "6" => [1, 2, 1, 3, 1, 1, 6], "7" => [1, 1, 1, 1, 1, 4, 7],
    "8" => [1, 3, 1, 2, 1, 1, 8], "9" => [1, 4, 1, 1, 1, 1, 9],
    "A" => [2, 1, 1, 1, 1, 3, 10], "B" => [2, 1, 1, 2, 1, 2, 11],
    "C" => [2, 1, 1, 3, 1, 1, 12], "D" => [2, 2, 1, 1, 1, 2, 13],
    "E" => [2, 2, 1, 2, 1, 1, 14], "F" => [2, 3, 1, 1, 1, 1, 15],
    "G" => [1, 1, 2, 1, 1, 3, 16], "H" => [1, 1, 2, 2, 1, 2, 17],
    "I" => [1, 1, 2, 3, 1, 1, 18], "J" => [1, 2, 2, 1, 1, 2, 19],
    "K" => [1, 3, 2, 1, 1, 1, 20], "L" => [1, 1, 1, 1, 2, 3, 21],
    "M" => [1, 1, 1, 2, 2, 2, 22], "N" => [1, 1, 1, 3, 2, 1, 23],
    "O" => [1, 2, 1, 1, 2, 2, 24], "P" => [1, 3, 1, 1, 2, 1, 25],
    "Q" => [2, 1, 2, 1, 1, 2, 26], "R" => [2, 1, 2, 2, 1, 1, 27],
    "S" => [2, 1, 1, 1, 2, 2, 28], "T" => [2, 1, 1, 2, 2, 1, 29],
    "U" => [2, 2, 1, 1, 2, 1, 30], "V" => [2, 2, 2, 1, 1, 1, 31],
    "W" => [1, 1, 2, 1, 2, 2, 32], "X" => [1, 1, 2, 2, 2, 1, 33],
    "Y" => [1, 2, 2, 1, 2, 1, 34], "Z" => [1, 2, 3, 1, 1, 1, 35],
    "-" => [1, 2, 1, 1, 3, 1, 36], "." => [3, 1, 1, 1, 1, 2, 37],
    " " => [3, 1, 1, 2, 1, 1, 38], "\$" => [3, 2, 1, 1, 1, 1, 39],
    "/" => [1, 1, 2, 1, 3, 1, 40], "+" => [1, 1, 3, 1, 2, 1, 41],
    "%" => [2, 1, 1, 1, 3, 1, 42], "#" => [1, 2, 1, 2, 2, 1, 43],
    "&" => [3, 1, 2, 1, 1, 1, 44], "|" => [3, 1, 1, 1, 2, 1, 45],
    "=" => [1, 2, 2, 2, 1, 1, 46], "*" => [1, 1, 1, 1, 4, 1, 0],
  ];

  private array $code_93_asciibet = [
    "&U", "#A", "#B", "#C", "#D", "#E", "#F", "#G",
    "#H", "#I", "#J", "#K", "#L", "#M", "#N", "#O",
    "#P", "#Q", "#R", "#S", "#T", "#U", "#V", "#W",
    "#X", "#Y", "#Z", "&A", "&B", "&C", "&D", "&E",
    " ", "|A", "|B", "|C", "\$", "%", "|F", "|G",
    "|H", "|I", "|J", "+", "|L", "-", ".", "/",
    "0", "1", "2", "3", "4", "5", "6", "7",
    "8", "9", "|Z", "&F", "&G", "&H", "&I", "&J",
    "&V", "A", "B", "C", "D", "E", "F", "G",
    "H", "I", "J", "K", "L", "M", "N", "O",
    "P", "Q", "R", "S", "T", "U", "V", "W",
    "X", "Y", "Z", "&K", "&L", "&M", "&N", "&O",
    "&W", "=A", "=B", "=C", "=D", "=E", "=F", "=G",
    "=H", "=I", "=J", "=K", "=L", "=M", "=N", "=O",
    "=P", "=Q", "=R", "=S", "=T", "=U", "=V", "=W",
    "=X", "=Y", "=Z", "&P", "&Q", "&R", "&S", "&T",
  ];

  private function code_128_encode(string $data, int $dstate, bool $fnc1): array {
    $data  = preg_replace("/[\x80-\xFF]/", "", $data);
    $label = preg_replace("/[\x00-\x1F\x7F]/", " ", $data);
    $chars = $this->code_128_normalize($data, $dstate, $fnc1);

    $checksum = $chars[0] % 103;
    for ($i = 1, $n = count($chars); $i < $n; $i++) {
      $checksum += $i * $chars[$i];
      $checksum %= 103;
    }

    $chars[] = $checksum;
    $chars[] = 106;

    $modules   = [];
    $modules[] = [0, 10, 0];

    foreach ($chars as $char) {
      $block = $this->code_128_alphabet[$char];
      foreach ($block as $i => $module) {
        $modules[] = [($i & 1) ^ 1, $module, 1];
      }
    }

    $modules[] = [0, 10, 0];
    $blocks    = [["m" => $modules, "l" => [$label]]];

    return ["g" => "l", "b" => $blocks];
  }

  private function code_128_normalize(string $data, int $dstate, bool $fnc1): array {
    $detectcba = "/(^[0-9]{4,}|^[0-9]{2}\$)|([x60-x7F])|([x00-x1F])/";
    $detectc   = "/(^[0-9]{6,}|^[0-9]{4,}\$)/";
    $detectba  = "/([\x60-\x7F])|([\x00-\x1F])/";
    $consumec  = "/(^[0-9]{2})/";

    $state   = (($dstate > 0 && $dstate < 4) ? $dstate : 0);
    $abstate = ((abs($dstate) === 2) ? 2 : 1);
    $chars   = [102 + ($state ?: $abstate)];

    if ($fnc1)
    $chars[] = 102;

    while (strlen($data)) {
      switch ($state) {
        case 0:
          if (preg_match($detectcba, $data, $m)) {
          if (!empty($m[1])) {
            $state = 3;
          } else if (!empty($m[2])) {
            $state = 2;
          } else {
            $state = 1;
          }
        } else {
          $state = $abstate;
        }
          $chars = [102 + $state];
          if ($fnc1)
          $chars[] = 102;
          break;
        case 1:
          if ($dstate <= 0 && preg_match($detectc, $data, $m)) {
          if (strlen($m[0]) % 2) {
            $data    = substr($data, 1);
            $chars[] = 16 + (int) substr($m[0], 0, 1);
          }
          $state   = 3;
          $chars[] = 99;
        } else {
          $ch   = ord(substr($data, 0, 1));
          $data = substr($data, 1);
          if ($ch < 32) {
            $chars[] = $ch + 64;
          } else if ($ch < 96) {
            $chars[] = $ch - 32;
          } else {
            if (preg_match($detectba, $data, $m)) {
              if (!empty($m[1])) {
                $state   = 2;
                $chars[] = 100;
              } else {
                $chars[] = 98;
              }
            } else {
              $chars[] = 98;
            }
            $chars[] = $ch - 32;
          }
        }
          break;
        case 2:
          if ($dstate <= 0 && preg_match($detectc, $data, $m)) {
          if (strlen($m[0]) % 2) {
            $data    = substr($data, 1);
            $chars[] = 16 + (int) substr($m[0], 0, 1);
          }
          $state   = 3;
          $chars[] = 99;
        } else {
          $ch   = ord(substr($data, 0, 1));
          $data = substr($data, 1);
          if ($ch >= 32) {
            $chars[] = $ch - 32;
          } else {
            if (preg_match($detectba, $data, $m)) {
              if (!empty($m[2])) {
                $state   = 1;
                $chars[] = 101;
              } else {
                $chars[] = 98;
              }
            } else {
              $chars[] = 98;
            }
            $chars[] = $ch + 64;
          }
        }
          break;
        case 3:
          if (preg_match($consumec, $data, $m)) {
          $data    = substr($data, 2);
          $chars[] = (int) $m[0];
        } else {
          if (preg_match($detectba, $data, $m)) {
            if (!empty($m[1])) {
              $state = 2;
            } else {
              $state = 1;
            }
          } else {
            $state = $abstate;
          }
          $chars[] = 102 - $state;
        }
          break;
      }
    }
    return $chars;
  }

  private array $code_128_alphabet = [
    [2, 1, 2, 2, 2, 2], [2, 2, 2, 1, 2, 2], [2, 2, 2, 2, 2, 1], [1, 2, 1, 2, 2, 3],
    [1, 2, 1, 3, 2, 2], [1, 3, 1, 2, 2, 2], [1, 2, 2, 2, 1, 3], [1, 2, 2, 3, 1, 2],
    [1, 3, 2, 2, 1, 2], [2, 2, 1, 2, 1, 3], [2, 2, 1, 3, 1, 2], [2, 3, 1, 2, 1, 2],
    [1, 1, 2, 2, 3, 2], [1, 2, 2, 1, 3, 2], [1, 2, 2, 2, 3, 1], [1, 1, 3, 2, 2, 2],
    [1, 2, 3, 1, 2, 2], [1, 2, 3, 2, 2, 1], [2, 2, 3, 2, 1, 1], [2, 2, 1, 1, 3, 2],
    [2, 2, 1, 2, 3, 1], [2, 1, 3, 2, 1, 2], [2, 2, 3, 1, 1, 2], [3, 1, 2, 1, 3, 1],
    [3, 1, 1, 2, 2, 2], [3, 2, 1, 1, 2, 2], [3, 2, 1, 2, 2, 1], [3, 1, 2, 2, 1, 2],
    [3, 2, 2, 1, 1, 2], [3, 2, 2, 2, 1, 1], [2, 1, 2, 1, 2, 3], [2, 1, 2, 3, 2, 1],
    [2, 3, 2, 1, 2, 1], [1, 1, 1, 3, 2, 3], [1, 3, 1, 1, 2, 3], [1, 3, 1, 3, 2, 1],
    [1, 1, 2, 3, 1, 3], [1, 3, 2, 1, 1, 3], [1, 3, 2, 3, 1, 1], [2, 1, 1, 3, 1, 3],
    [2, 3, 1, 1, 1, 3], [2, 3, 1, 3, 1, 1], [1, 1, 2, 1, 3, 3], [1, 1, 2, 3, 3, 1],
    [1, 3, 2, 1, 3, 1], [1, 1, 3, 1, 2, 3], [1, 1, 3, 3, 2, 1], [1, 3, 3, 1, 2, 1],
    [3, 1, 3, 1, 2, 1], [2, 1, 1, 3, 3, 1], [2, 3, 1, 1, 3, 1], [2, 1, 3, 1, 1, 3],
    [2, 1, 3, 3, 1, 1], [2, 1, 3, 1, 3, 1], [3, 1, 1, 1, 2, 3], [3, 1, 1, 3, 2, 1],
    [3, 3, 1, 1, 2, 1], [3, 1, 2, 1, 1, 3], [3, 1, 2, 3, 1, 1], [3, 3, 2, 1, 1, 1],
    [3, 1, 4, 1, 1, 1], [2, 2, 1, 4, 1, 1], [4, 3, 1, 1, 1, 1], [1, 1, 1, 2, 2, 4],
    [1, 1, 1, 4, 2, 2], [1, 2, 1, 1, 2, 4], [1, 2, 1, 4, 2, 1], [1, 4, 1, 1, 2, 2],
    [1, 4, 1, 2, 2, 1], [1, 1, 2, 2, 1, 4], [1, 1, 2, 4, 1, 2], [1, 2, 2, 1, 1, 4],
    [1, 2, 2, 4, 1, 1], [1, 4, 2, 1, 1, 2], [1, 4, 2, 2, 1, 1], [2, 4, 1, 2, 1, 1],
    [2, 2, 1, 1, 1, 4], [4, 1, 3, 1, 1, 1], [2, 4, 1, 1, 1, 2], [1, 3, 4, 1, 1, 1],
    [1, 1, 1, 2, 4, 2], [1, 2, 1, 1, 4, 2], [1, 2, 1, 2, 4, 1], [1, 1, 4, 2, 1, 2],
    [1, 2, 4, 1, 1, 2], [1, 2, 4, 2, 1, 1], [4, 1, 1, 2, 1, 2], [4, 2, 1, 1, 1, 2],
    [4, 2, 1, 2, 1, 1], [2, 1, 2, 1, 4, 1], [2, 1, 4, 1, 2, 1], [4, 1, 2, 1, 2, 1],
    [1, 1, 1, 1, 4, 3], [1, 1, 1, 3, 4, 1], [1, 3, 1, 1, 4, 1], [1, 1, 4, 1, 1, 3],
    [1, 1, 4, 3, 1, 1], [4, 1, 1, 1, 1, 3], [4, 1, 1, 3, 1, 1], [1, 1, 3, 1, 4, 1],
    [1, 1, 4, 1, 3, 1], [3, 1, 1, 1, 4, 1], [4, 1, 1, 1, 3, 1], [2, 1, 1, 4, 1, 2],
    [2, 1, 1, 2, 1, 4], [2, 1, 1, 2, 3, 2], [2, 3, 3, 1, 1, 1, 2]
  ];

  private function codabar_encode(string $data): array {
    $data   = strtoupper(preg_replace("/[^0-9ABCDENTabcdent*\.\/:+\$-]/", "", $data));
    $blocks = [];

    for ($i = 0, $n = strlen($data); $i < $n; $i++) {
      if ($blocks) {
        $blocks[] = ["m" => [[0, 1, 3]]];
      }
      $char     = substr($data, $i, 1);
      $block    = $this->codabar_alphabet[$char];
      $blocks[] = [
        "m" => [
          [1, 1, $block[0]], [0, 1, $block[1]],
          [1, 1, $block[2]], [0, 1, $block[3]],
          [1, 1, $block[4]], [0, 1, $block[5]],
          [1, 1, $block[6]],
        ],
        "l" => [$char]
      ];
    }
    return ["g" => "l", "b" => $blocks];
  }

  private array $codabar_alphabet = [
    "0" => [1, 1, 1, 1, 1, 2, 2], "1" => [1, 1, 1, 1, 2, 2, 1],
    "4" => [1, 1, 2, 1, 1, 2, 1], "5" => [2, 1, 1, 1, 1, 2, 1],
    "2" => [1, 1, 1, 2, 1, 1, 2], "-" => [1, 1, 1, 2, 2, 1, 1],
    "\$" => [1, 1, 2, 2, 1, 1, 1], "9" => [2, 1, 1, 2, 1, 1, 1],
    "6" => [1, 2, 1, 1, 1, 1, 2], "7" => [1, 2, 1, 1, 2, 1, 1],
    "8" => [1, 2, 2, 1, 1, 1, 1], "3" => [2, 2, 1, 1, 1, 1, 1],
    "C" => [1, 1, 1, 2, 1, 2, 2], "D" => [1, 1, 1, 2, 2, 2, 1],
    "A" => [1, 1, 2, 2, 1, 2, 1], "B" => [1, 2, 1, 2, 1, 1, 2],
    "*" => [1, 1, 1, 2, 1, 2, 2], "E" => [1, 1, 1, 2, 2, 2, 1],
    "T" => [1, 1, 2, 2, 1, 2, 1], "N" => [1, 2, 1, 2, 1, 1, 2],
    "." => [2, 1, 2, 1, 2, 1, 1], "/" => [2, 1, 2, 1, 1, 1, 2],
    ":" => [2, 1, 1, 1, 2, 1, 2], "+" => [1, 1, 2, 1, 2, 1, 2],
  ];

  private function itf_encode(string $data): array {
    $data = preg_replace("/[^0-9]/", "", $data);
    if (strlen($data) % 2) {
      $data = "0" . $data;
    }

    $blocks   = [];
    $blocks[] = ["m" => [[0, 10, 0]]];
    $blocks[] = [
      "m" => [
        [1, 1, 1], [0, 1, 1], [1, 1, 1], [0, 1, 1],
      ]
    ];

    for ($i = 0, $n = strlen($data); $i < $n; $i += 2) {
      $c1       = substr($data, $i, 1);
      $c2       = substr($data, $i + 1, 1);
      $b1       = $this->itf_alphabet[$c1];
      $b2       = $this->itf_alphabet[$c2];
      $blocks[] = [
        "m" => [
          [1, 1, $b1[0]], [0, 1, $b2[0]],
          [1, 1, $b1[1]], [0, 1, $b2[1]],
          [1, 1, $b1[2]], [0, 1, $b2[2]],
          [1, 1, $b1[3]], [0, 1, $b2[3]],
          [1, 1, $b1[4]], [0, 1, $b2[4]],
        ],
        "l" => [$c1 . $c2]
      ];
    }

    $blocks[] = [
      "m" => [
        [1, 1, 2], [0, 1, 1], [1, 1, 1],
      ]
    ];
    $blocks[] = ["m" => [[0, 10, 0]]];

    return ["g" => "l", "b" => $blocks];
  }

  private array $itf_alphabet = [
    "0" => [1, 1, 2, 2, 1], "1" => [2, 1, 1, 1, 2],
    "2" => [1, 2, 1, 1, 2], "3" => [2, 2, 1, 1, 1],
    "4" => [1, 1, 2, 1, 2], "5" => [2, 1, 2, 1, 1],
    "6" => [1, 2, 2, 1, 1], "7" => [1, 1, 1, 2, 2],
    "8" => [2, 1, 1, 2, 1], "9" => [1, 2, 1, 2, 1],
  ];
}

if (!class_exists("BarCode", false)) {
	class_alias(BarCode::class, "BarCode");
}
if (!class_exists("Pirulug\\Qr\\BarCode", false)) {
	class_alias(BarCode::class, "Pirulug\\Qr\\BarCode");
}
