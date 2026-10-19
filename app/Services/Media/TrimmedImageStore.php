<?php

namespace App\Services\Media;

use App\Support\WhiteBorderTrimmer;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * On-disk cache of CoreX listing photos with their white border trimmed, plus
 * downscaled variants of each so thumbnails and cards don't download the
 * full-size file.
 *
 * Layout on the `local` disk:
 *   trimmed/{sha1(url)}.jpg        the trimmed original
 *   trimmed/w{width}/{sha1}.jpg    a downscaled variant (never upscaled)
 */
class TrimmedImageStore
{
    /**
     * Variant widths that may be requested. Anything else is snapped to the
     * nearest allowed width so the number of cached files stays bounded.
     *
     * @var array<int, int>
     */
    public const WIDTHS = [480, 960, 1600];

    protected const VARIANT_QUALITY = 82;

    public function isAllowedHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && in_array($host, config('services.corex.media_hosts', []), true);
    }

    /**
     * Snap a requested width to the smallest allowed width that covers it,
     * capped at the largest. 0 (or less) means "the trimmed original".
     */
    public static function snapWidth(int $requested): int
    {
        if ($requested <= 0) {
            return 0;
        }

        foreach (self::WIDTHS as $width) {
            if ($requested <= $width) {
                return $width;
            }
        }

        return self::WIDTHS[array_key_last(self::WIDTHS)];
    }

    public function hasOriginal(string $url): bool
    {
        return $this->disk()->exists($this->originalPath($url));
    }

    /**
     * Relative path of the trimmed original, downloading and trimming it first
     * when it is not cached yet. Null when the source cannot be fetched.
     */
    public function original(string $url): ?string
    {
        $path = $this->originalPath($url);

        if ($this->disk()->exists($path)) {
            return $path;
        }

        try {
            $source = Http::timeout(10)->get($url)->throw()->body();

            $this->write($path, WhiteBorderTrimmer::trim($source));
        } catch (Throwable) {
            return null;
        }

        return $path;
    }

    /**
     * Relative path of the image at (at most) the given width. Falls back to the
     * trimmed original when it is already that small or cannot be resized, and
     * is null only when the source itself is unavailable.
     */
    public function variant(string $url, int $width): ?string
    {
        $original = $this->original($url);

        if ($original === null || $width <= 0) {
            return $original;
        }

        $path = 'trimmed/w'.$width.'/'.sha1($url).'.jpg';

        if ($this->disk()->exists($path)) {
            return $path;
        }

        try {
            $bytes = $this->disk()->get($original);
            $size = is_string($bytes) ? getimagesizefromstring($bytes) : false;

            if ($size === false || $size[0] <= $width) {
                return $original;
            }

            $image = imagecreatefromstring($bytes);
            $scaled = $image === false ? false : imagescale($image, $width, -1, IMG_BICUBIC);

            if ($image !== false) {
                imagedestroy($image);
            }

            if ($scaled === false) {
                return $original;
            }

            ob_start();
            imagejpeg($scaled, null, self::VARIANT_QUALITY);
            $encoded = (string) ob_get_clean();
            imagedestroy($scaled);

            $this->write($path, $encoded);
        } catch (Throwable) {
            return $original;
        }

        return $path;
    }

    public function absolutePath(string $path): string
    {
        return $this->disk()->path($path);
    }

    protected function originalPath(string $url): string
    {
        return 'trimmed/'.sha1($url).'.jpg';
    }

    /**
     * Write via a temp file and rename, so a concurrent reader never sees a
     * half-written image.
     */
    protected function write(string $path, string $bytes): void
    {
        $temporary = $path.'.'.bin2hex(random_bytes(4)).'.tmp';

        $this->disk()->put($temporary, $bytes);
        $this->disk()->move($temporary, $path);
    }

    protected function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }
}
