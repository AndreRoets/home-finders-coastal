<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Controller;
use App\Support\WhiteBorderTrimmer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    /**
     * Serve a CoreX listing image with its uniform white border trimmed off.
     *
     * Some imported listing photos are letterboxed (white padding baked into
     * the file), which makes property cards look unevenly sized. We fetch the
     * source once, trim the border, cache the result, and serve it. The source
     * host is allow-listed to keep this from being an open image proxy.
     */
    public function trimmed(Request $request): Response|RedirectResponse
    {
        $url = (string) $request->query('u', '');

        if ($url === '' || ! $this->isAllowedHost($url)) {
            abort(404);
        }

        $path = 'trimmed/'.sha1($url).'.jpg';
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            try {
                $source = Http::timeout(10)->get($url)->throw()->body();
                $disk->put($path, WhiteBorderTrimmer::trim($source));
            } catch (\Throwable) {
                // Network/decoding failure — fall back to the original image so
                // the photo still renders, just untrimmed.
                return redirect()->away($url);
            }
        }

        return response()->file($disk->path($path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    /**
     * Serve an admin-uploaded marketing image (social share cards) from the
     * public disk. Served through the app rather than a `storage` symlink so
     * social crawlers can always reach it, whatever the host allows.
     */
    public function uploaded(string $file): Response
    {
        $path = MediaUploadController::DIRECTORY.'/'.$file;
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path), [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function isAllowedHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && in_array($host, config('services.corex.media_hosts', []), true);
    }
}
