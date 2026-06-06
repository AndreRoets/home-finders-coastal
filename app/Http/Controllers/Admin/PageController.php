<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * List every manageable page with its slug and indexing status.
     */
    public function index(): Response
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
        ]);
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
