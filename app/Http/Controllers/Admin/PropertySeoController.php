<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateListingSeoRequest;
use App\Models\ListingSeo;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use App\Support\DynamicSeo;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PropertySeoController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

    /**
     * Per-listing SEO editor. Shows the live listing's auto-generated meta as
     * the fallback for each field, alongside any saved override.
     */
    public function edit(string $id): Response
    {
        $listing = $this->corex->listing($id);

        if ($listing === []) {
            throw new NotFoundHttpException('Listing not found.');
        }

        $property = ListingMapper::detail($listing);
        $canonical = route('property.show', $property['slug']);
        $defaults = DynamicSeo::listingDefaults($property, $canonical);

        $override = ListingSeo::query()->where('listing_id', $id)->first();

        return Inertia::render('admin/properties/edit', [
            'property' => [
                'id' => (string) $id,
                'title' => $property['title'],
                'slug' => $property['slug'],
                'image' => $property['image'] ?? null,
                'view_url' => $canonical,
                'defaults' => [
                    'meta_title' => $defaults['meta_title'] ?? '',
                    'meta_description' => $defaults['meta_description'] ?? '',
                    'canonical_url' => $defaults['canonical_url'] ?? '',
                ],
            ],
            'seo' => [
                'meta_title' => $override->meta_title ?? '',
                'meta_description' => $override->meta_description ?? '',
                'meta_keywords' => $override->meta_keywords ?? '',
                'canonical_url' => $override->canonical_url ?? '',
                'is_done' => (bool) ($override->is_done ?? false),
            ],
        ]);
    }

    /**
     * Save (or clear) the per-listing SEO override.
     */
    public function update(UpdateListingSeoRequest $request, string $id): RedirectResponse
    {
        ListingSeo::query()->updateOrCreate(
            ['listing_id' => $id],
            $request->validated(),
        );

        return back()->with('success', 'Property SEO updated.');
    }
}
