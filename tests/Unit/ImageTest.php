<?php

use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    public function testThumbnailUrlEmptyPathReturnsEmpty()
    {
        $this->assertSame('', thumbnailUrl(''));
    }

    public function testThumbnailUrlFallsBackToOriginal()
    {
        $name = 'missing_' . uniqid() . '.jpg';
        $url = thumbnailUrl('uploads/' . $name, 'sm');
        $this->assertStringContainsString('uploads/' . $name, $url);
    }

    public function testCreateThumbnailRejectsMissingFile()
    {
        $name = 'missing_' . uniqid() . '.jpg';
        $this->assertFalse(createImageThumbnail('uploads/' . $name, 128, 128, 'sm'));
    }
}