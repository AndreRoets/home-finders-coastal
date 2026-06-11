<?php

namespace App\Support;

/**
 * Crops a uniform near-white border off an image — the letterboxing some
 * imported listing photos carry baked into the file. Only the solid frame
 * around the edges is removed (like ImageMagick's `-trim`), so interior white
 * (e.g. a white wall in the photo) is never touched.
 */
class WhiteBorderTrimmer
{
    /**
     * Trim the white frame and return re-encoded JPEG bytes. Returns the input
     * bytes unchanged when the data is not a decodable image or has no border
     * to trim.
     */
    public static function trim(string $bytes, int $threshold = 244): string
    {
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return $bytes;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $top = 0;
        while ($top < $height - 1 && self::isWhiteRow($image, $top, $width, $threshold)) {
            $top++;
        }

        $bottom = $height - 1;
        while ($bottom > $top && self::isWhiteRow($image, $bottom, $width, $threshold)) {
            $bottom--;
        }

        $left = 0;
        while ($left < $width - 1 && self::isWhiteColumn($image, $left, $height, $threshold)) {
            $left++;
        }

        $right = $width - 1;
        while ($right > $left && self::isWhiteColumn($image, $right, $height, $threshold)) {
            $right--;
        }

        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;

        if ($cropWidth > 0 && $cropHeight > 0 && ($cropWidth < $width || $cropHeight < $height)) {
            $cropped = imagecrop($image, ['x' => $left, 'y' => $top, 'width' => $cropWidth, 'height' => $cropHeight]);

            if ($cropped !== false) {
                imagedestroy($image);
                $image = $cropped;
            }
        }

        ob_start();
        imagejpeg($image, null, 85);
        $output = (string) ob_get_clean();
        imagedestroy($image);

        return $output;
    }

    /**
     * @param  \GdImage  $image
     */
    private static function isWhiteRow($image, int $y, int $width, int $threshold): bool
    {
        for ($x = 0; $x < $width; $x++) {
            if (! self::isWhitePixel($image, $x, $y, $threshold)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  \GdImage  $image
     */
    private static function isWhiteColumn($image, int $x, int $height, int $threshold): bool
    {
        for ($y = 0; $y < $height; $y++) {
            if (! self::isWhitePixel($image, $x, $y, $threshold)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  \GdImage  $image
     */
    private static function isWhitePixel($image, int $x, int $y, int $threshold): bool
    {
        $rgb = imagecolorat($image, $x, $y);
        $red = ($rgb >> 16) & 0xFF;
        $green = ($rgb >> 8) & 0xFF;
        $blue = $rgb & 0xFF;

        return $red >= $threshold && $green >= $threshold && $blue >= $threshold;
    }
}
