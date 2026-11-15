<?php

namespace Tests\Unit;

use App\Support\FaviconMaker;
use PHPUnit\Framework\TestCase;

class FaviconMakerTest extends TestCase
{
    private function wideLogo(): string
    {
        $im = imagecreatetruecolor(300, 100);
        imagefill($im, 0, 0, imagecolorallocate($im, 20, 43, 69));
        ob_start();
        imagepng($im);

        return (string) ob_get_clean();
    }

    public function test_makes_square_png_keeping_proportions(): void
    {
        if (! FaviconMaker::available()) {
            $this->markTestSkipped('Ekstensi GD tidak tersedia.');
        }
        $png = FaviconMaker::make($this->wideLogo(), 64);
        $info = getimagesizefromstring((string) $png);

        $this->assertSame([64, 64], [$info[0], $info[1]]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function test_returns_null_for_invalid_input(): void
    {
        if (! FaviconMaker::available()) {
            $this->markTestSkipped('Ekstensi GD tidak tersedia.');
        }
        $this->assertNull(FaviconMaker::make('bukan gambar', 64));
        $this->assertNull(FaviconMaker::make($this->wideLogo(), 9999));
    }
}
