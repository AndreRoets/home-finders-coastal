<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * Whether to register a default empty stub for the CoreX agency endpoint.
     *
     * The agency profile is loaded into shared Inertia props on every page, so
     * every route-rendering test would otherwise make a real /agency request.
     * The default stub is scoped to the agency endpoint only so it never shadows
     * a test's own listings/agents/etc. stubs. Tests that assert on agency data
     * set this to false and register their own agency fake.
     */
    protected bool $fakeAgency = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->fakeAgency) {
            Http::fake(['*/agency*' => Http::response(['data' => []])]);
        }
    }
}
