<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\ListingSeo;
use App\Models\Page;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * List every manageable page with its slug and indexing status, alongside
     * the live, CoreX-backed property listings whose SEO can be overridden.
     */
    public function index(Request $request, CorexClient $corex): Response
    {
        return Inertia::render('admin/pages/index', [
            'pages' => Page::query()
                ->orderByDesc('sitemap_priority')
                ->get()
                ->map(fn (Page $page): array => [
                    'id' => $page->id,
                    'name' => $page->name,
                    'slug' => $page->path(),
                    'is_active' => $page->is_active,
                    'robots_index' => $page->robots_index,
                    'meta_title' => $page->meta_title,
                    'edit_url' => route('admin.pages.edit', $page),
                    'view_url' => url($page->path()),
                ]),
            'properties' => $this->properties($request, $corex),
            'property_search' => (string) $request->query('property_search', ''),
        ]);
    }

    /**
     * The live property listings available for per-listing SEO editing,
     * optionally filtered by title via the `property_search` query param and
     * flagged when an admin-authored override already exists.
     *
     * @return LengthAwarePaginator<int, array{id: string, title: string, customised: bool, edit_url: string}>
     */
    protected function properties(Request $request, CorexClient $corex): LengthAwarePaginator
    {
        $search = trim((string) $request->query('property_search', ''));

        $customised = ListingSeo::query()->pluck('listing_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->flip();

        $items = Collection::make($corex->listings())
            ->map(static fn (array $listing): array => [
                'id' => (string) Arr::get($listing, 'id', ''),
                'title' => (string) (Arr::get($listing, 'title') ?: 'Untitled listing'),
                'slug' => ListingMapper::slug($listing),
            ])
            ->when($search !== '', fn (Collection $listings): Collection => $listings->filter(
                static fn (array $listing): bool => Str::contains(Str::lower($listing['title']), Str::lower($search)),
            ))
            ->values()
            ->map(fn (array $listing): array => [
                'id' => $listing['id'],
                'title' => $listing['title'],
                'customised' => $customised->has($listing['id']),
                'edit_url' => route('admin.properties.edit', $listing['id']),
            ]);

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /**
     * The full SEO / marketing editor for a single page.
     */
    public function edit(Page $page): Response
    {
        return Inertia::render('admin/pages/edit', [
            'page' => $page,
            'redirects' => $page->redirects()->latest()->get(['id', 'old_slug', 'status_code']),
        ]);
    }

    /**
     * Persist the page. When the slug changes we keep the previous slug as a
     * 301 redirect so existing inbound links and search rankings survive.
     */
    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();
        $previousSlug = $page->slug;
        $newSlug = $data['slug'];

        $page->update($data);

        if ($newSlug !== $previousSlug) {
            // A page can't redirect to itself, and the homepage slug ("/") is
            // never turned into a redirect.
            $page->redirects()->where('old_slug', $newSlug)->delete();

            if ($previousSlug !== '/' && $previousSlug !== '') {
                $page->redirects()->updateOrCreate(
                    ['old_slug' => $previousSlug],
                    ['status_code' => 301],
                );
            }
        }

        return back()->with('success', 'Page updated.');
    }
}
