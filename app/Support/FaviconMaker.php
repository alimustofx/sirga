<?php

namespace App\Support;

/** Membuat ikon persegi (PNG) dari logo sekolah memakai GD: proporsi terjaga, diberi ruang kosong. */
final class FaviconMaker
{
    public static function available(): bool
    {
        return function_exists('imagecreatefromstring') && function_exists('imagepng');
    }

    /** @param bool $opaque true = latar putih (apple-touch-icon); false = transparan (favicon) */
    public static function make(string $binary, int $size = 64, bool $opaque = false): ?string
    {
        if (! self::available() || $size < 16 || $size > 512) {
            return null;
        }
        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            return null;
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        if ($w < 1 || $h < 1) {
            return null;
        }

        $canvas = imagecreatetruecolor($size, $size);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        $bg = $opaque ? imagecolorallocatealpha($canvas, 255, 255, 255, 0) : imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $bg);
        imagealphablending($canvas, true);

        $inner = (int) round($size * 0.9); // sisakan tepi tipis
        $scale = min($inner / $w, $inner / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        imagecopyresampled($canvas, $src, (int) (($size - $nw) / 2), (int) (($size - $nh) / 2), 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();

        return $png === false ? null : $png;
    }
}
