<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Controller;
use App\Services\Media\TrimmedImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function __construct(protected TrimmedImageStore $images) {}

    /**
     * Serve a CoreX listing image with its uniform white border trimmed off.
     *
     * Some imported listing photos are letterboxed (white padding baked into
     * the file), which makes property cards look unevenly sized. We fetch the
     * source once, trim the border, cache the result, and serve it. The source
     * host is allow-listed to keep this from being an open image proxy.
     *
     * An optional `w` query parameter asks for a downscaled copy (snapped to
     * {@see TrimmedImageStore::WIDTHS}) so thumbnails stay light; without it the
     * full trimmed original is served.
     *
     * This route is registered outside the `web` middleware group (no session,
     * cookies or Inertia shared props) so the response carries no Set-Cookie and
     * can be cached by nginx and the browser.
     */
    public function trimmed(Request $request): Response|RedirectResponse
    {
        $url = (string) $request->query('u', '');

        if ($url === '' || ! $this->images->isAllowedHost($url)) {
            abort(404);
        }

        $width = TrimmedImageStore::snapWidth((int) $request->query('w', 0));
        $path = $this->images->variant($url, $width);

        if ($path === null) {
            // Network/decoding failure — fall back to the original image so the
            // photo still renders, just untrimmed. Not cacheable: the source may
            // recover.
            return redirect()->away($url);
        }

        return response()->file($this->images->absolutePath($path), [
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
}
