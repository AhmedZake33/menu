<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    public function format(string $requestedFormat): string
    {
        return $requestedFormat === 'png' && extension_loaded('gd') ? 'png' : 'svg';
    }

    public function render(string $url, string $format = 'svg'): string
    {
        $format = $this->format($format);
        $qr = new QrCode(data: $url, size: 360, margin: 20);

        return ($format === 'png' ? new PngWriter : new SvgWriter)->write($qr)->getString();
    }
}
