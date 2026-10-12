<?php

namespace App\Support;

use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;

/** Barcode / QR images as data URIs, ready for <img src> in dompdf views. */
class Barcodes
{
    public static function code128(string $code, int $barWidth = 2, int $height = 60): ?string
    {
        try {
            $png = (new DNS1D())->getBarcodePNG($code, 'C128', $barWidth, $height);
            return $png ? 'data:image/png;base64,' . $png : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public static function qr(string $payload, int $size = 5): ?string
    {
        try {
            $png = (new DNS2D())->getBarcodePNG($payload, 'QRCODE', $size, $size);
            return $png ? 'data:image/png;base64,' . $png : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
