<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The agent profile page surfaces the richer CoreX profile fields: the "Get to
 * Know Me" about text, the agent's social links (normalised to absolute URLs)
 * and the grid of their published articles. Each must degrade gracefully when
 * CoreX omits it.
 */
class AgentProfileTest extends TestCase
{
    public function test_it_exposes_about_socials_and_articles(): void
    {
        Http::fake([
            '*/agents/7' => Http::response(['data' => [
                'id' => 7,
                'name' => 'Thandi Mbeki',
                'cell' => '+27 82 000 0000',
                'about' => 'KZN South Coast specialist.',
                'socials' => [
                    'facebook' => 'https://facebook.com/thandi',
                    'instagram' => 'thandi.sells', // bare handle → normalised
                ],
            ]]),
            '*/articles*' => Http::response([
                'data' => [
                    [
                        'id' => 12, 'agent_id' => 7, 'title' => 'Pre-Approval vs Final Bond Approval',
                        'slug' => 'pre-approval-vs-final-bond-approval', 'excerpt' => 'When buying…',
                        'cover_image_url' => 'https://corex.test/cover.jpg', 'body' => 'Full text…',
                        'tags' => ['BondApproval'], 'read_minutes' => 1, 'word_count' => 162, 'date' => '2026-06-06',
                    ],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/listings*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/testimonials*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('agents.show', 7))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agent')
                ->where('agent.about', 'KZN South Coast specialist.')
                ->where('agent.socials.facebook', 'https://facebook.com/thandi')
                // Bare handle is expanded to a full profile URL.
                ->where('agent.socials.instagram', 'https://instagram.com/thandi.sells')
                ->has('articles', 1)
                ->where('articles.0.title', 'Pre-Approval vs Final Bond Approval')
                ->where('articles.0.slug', 'pre-approval-vs-final-bond-approval')
                ->where('articles.0.readMinutes', 1)
                ->where('articles.0.wordCount', 162)
            );
    }

    public function test_it_nulls_about_and_empties_socials_when_absent(): void
    {
        Http::fake([
            '*/agents/7' => Http::response(['data' => [
                'id' => 7, 'name' => 'Quiet Agent', 'cell' => '1',
            ]]),
            '*/articles*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/listings*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/testimonials*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('agents.show', 7))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agent')
                ->where('agent.about', null)
                ->where('agent.socials', [])
                ->has('articles', 0)
            );
    }
}
