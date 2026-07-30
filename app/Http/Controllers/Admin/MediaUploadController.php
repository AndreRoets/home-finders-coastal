<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    /**
     * Where admin-uploaded marketing images live on the public disk.
     */
    public const DIRECTORY = 'seo';

    /**
     * Store an admin-uploaded image (social share cards, marketing artwork) and
     * hand back its public URL, which the SEO editor drops straight into the
     * og:image / twitter:image field it was uploaded for.
     */
    public function store(StoreMediaUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $stem = Str::slug(pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = Str::limit($stem ?: 'image', 60, '').'-'.Str::lower(Str::random(8)).'.'.$extension;

        $file->storeAs(self::DIRECTORY, $filename, 'public');

        return response()->json([
            'url' => route('media.uploaded', $filename),
            'name' => $filename,
        ]);
    }
}
