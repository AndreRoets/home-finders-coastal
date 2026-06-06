<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The home page testimonials section is driven by GET /testimonials. Ratings
 * render as stars only when present, and an attributed testimonial carries the
 * agent it links to.
 */
class HomeTestimonialsTest extends TestCase
{
    public function test_home_page_exposes_mapped_testimonials(): void
    {
        Http::fake([
            '*/listings*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/agents*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/testimonials*' => Http::response([
                'data' => [
                    [
                        'id' => 12,
                        'author' => 'Andre R.',
                        'rating' => 5,
                        'body' => 'Sold in a week.',
                        'date' => '2026-06-06',
                        'agent_id' => 7,
                        'agent' => ['id' => 7, 'name' => 'Thandi Mbeki'],
                    ],
                    [
                        'id' => 13,
                        'author' => 'No Rating',
                        'rating' => null,
                        'body' => 'Great service.',
                        'agent' => null,
                    ],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('home')
                ->has('testimonials', 2)
                ->where('testimonials.0.body', 'Sold in a week.')
                ->where('testimonials.0.rating', 5)
                ->where('testimonials.0.agent.id', 7)
                ->where('testimonials.0.agent.name', 'Thandi Mbeki')
                // Null rating must stay null so the stars are hidden.
                ->where('testimonials.1.rating', null)
                ->where('testimonials.1.agent', null)
            );
    }

    public function test_home_page_renders_with_no_testimonials(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('home')
                ->has('testimonials', 0)
            );
    }

    public function test_home_page_caps_testimonials_at_twelve(): void
    {
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $rows[] = ['id' => $i, 'author' => "Client {$i}", 'body' => 'Lovely.'];
        }

        Http::fake([
            '*/listings*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/agents*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/testimonials*' => Http::response(['data' => $rows, 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('testimonials', 12));
    }
}
