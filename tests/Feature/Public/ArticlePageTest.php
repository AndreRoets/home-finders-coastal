<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The article page (/articles/{slug}) resolves the slug against the agency's
 * published articles, then renders the full article (title, by-line, body,
 * tags) and an author card linking back to the agent. It degrades to a 404 when
 * the slug matches nothing.
 */
class ArticlePageTest extends TestCase
{
    public function test_it_renders_the_article_with_author(): void
    {
        Http::fake([
            // Single-article fetch must be matched before the collection pattern.
            '*/articles/12' => Http::response(['data' => [
                'id' => 12, 'agent_id' => 7, 'title' => 'Pre-Approval vs Final Bond Approval',
                'slug' => 'pre-approval-vs-final-bond-approval', 'excerpt' => 'When buying a property…',
                'cover_image_url' => null, 'body' => "Line one.\nLine two.", 'link_url' => 'https://example.test/more',
                'tags' => ['BondApproval', 'HomeBuying'], 'read_minutes' => 1, 'word_count' => 162, 'date' => '2026-06-06',
            ]]),
            '*/articles*' => Http::response([
                'data' => [
                    ['id' => 12, 'agent_id' => 7, 'title' => 'Pre-Approval vs Final Bond Approval', 'slug' => 'pre-approval-vs-final-bond-approval'],
                    ['id' => 13, 'agent_id' => 7, 'title' => 'Other', 'slug' => 'other'],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/agents/7' => Http::response(['data' => [
                'id' => 7, 'name' => 'Thandi Mbeki', 'designation' => 'Principal Property Practitioner', 'cell' => '1',
            ]]),
        ]);

        $this->get(route('articles.show', 'pre-approval-vs-final-bond-approval'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('article')
                ->where('article.title', 'Pre-Approval vs Final Bond Approval')
                ->where('article.body', "Line one.\nLine two.")
                ->where('article.linkUrl', 'https://example.test/more')
                ->where('article.coverImage', null)
                ->where('article.tags', ['BondApproval', 'HomeBuying'])
                ->where('agent.name', 'Thandi Mbeki')
            );
    }

    public function test_it_renders_a_graceful_404_for_an_unknown_slug(): void
    {
        Http::fake([
            '*/articles*' => Http::response([
                'data' => [['id' => 12, 'agent_id' => 7, 'title' => 'A', 'slug' => 'a']],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('articles.show', 'does-not-exist'))
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('article')
                ->where('article', null)
                ->where('agent', null)
            );
    }

    public function test_it_falls_back_to_the_list_row_when_single_fetch_is_empty(): void
    {
        Http::fake([
            '*/articles/12' => Http::response(['message' => 'Not found.'], 404),
            '*/articles*' => Http::response([
                'data' => [
                    ['id' => 12, 'agent_id' => 7, 'title' => 'Listed Title', 'slug' => 'listed-title', 'body' => 'From the list.'],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/agents/7' => Http::response(['data' => ['id' => 7, 'name' => 'Thandi Mbeki', 'cell' => '1']]),
        ]);

        $this->get(route('articles.show', 'listed-title'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('article')
                ->where('article.title', 'Listed Title')
                ->where('article.body', 'From the list.')
                ->where('agent.name', 'Thandi Mbeki')
            );
    }
}
