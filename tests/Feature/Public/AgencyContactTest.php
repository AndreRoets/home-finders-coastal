<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The agency's public contact details, social links and opening hours are
 * loaded from GET /agency into shared Inertia props so the footer/header render
 * them on every page. Absent fields must never appear, and a fetch error must
 * degrade to a null agency rather than break the page.
 */
class AgencyContactTest extends TestCase
{
    /** This suite asserts on agency data, so it manages its own /agency stub. */
    protected bool $fakeAgency = false;

    public function test_it_shares_mapped_agency_details(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => [
                'name' => 'Home Finders Coastal',
                'contact' => [
                    'email' => 'info@example.co.za',
                    'phone' => '039 000 0000',
                    'address' => '12 Marina Drive, Uvongo, 4275',
                ],
                'social' => [
                    'facebook' => 'homefinderscoastal', // bare handle
                    'instagram' => 'https://instagram.com/hfc',
                ],
                'open_hours' => [
                    ['days' => 'Monday – Friday', 'hours' => '08:00 – 17:00'],
                    ['days' => 'Sunday', 'hours' => 'Closed'],
                ],
            ]]),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('agency.contact.email', 'info@example.co.za')
                ->where('agency.contact.phone', '039 000 0000')
                ->where('agency.contact.phoneHref', '0390000000')
                ->where('agency.contact.address', '12 Marina Drive, Uvongo, 4275')
                // Bare handle expands; full URL passes through.
                ->where('agency.socials.facebook', 'https://facebook.com/homefinderscoastal')
                ->where('agency.socials.instagram', 'https://instagram.com/hfc')
                ->has('agency.openHours', 2)
                ->where('agency.openHours.1.hours', 'Closed')
            );
    }

    public function test_it_omits_absent_contact_fields(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => [
                'name' => 'Sparse Agency',
                'contact' => ['email' => 'only@example.co.za'],
            ]]),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('agency.contact', ['email' => 'only@example.co.za'])
                ->where('agency.socials', [])
                ->has('agency.openHours', 0)
                ->where('agency.branding', null)
            );
    }

    public function test_the_contact_page_is_backed_by_agency_details(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => [
                'name' => 'Home Finders Coastal',
                'contact' => [
                    'email' => 'info@example.co.za',
                    'phone' => '039 000 0000',
                    'address' => '12 Marina Drive, Uvongo, 4275',
                ],
                'open_hours' => [
                    ['days' => 'Monday – Friday', 'hours' => '08:00 – 17:00'],
                ],
            ]]),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('contact')
                // The page reads contact/phone/email/hours from the shared agency prop.
                ->where('agency.contact.email', 'info@example.co.za')
                ->where('agency.contact.phoneHref', '0390000000')
                ->where('agency.contact.address', '12 Marina Drive, Uvongo, 4275')
                ->has('agency.openHours', 1)
            );
    }

    public function test_agency_is_null_when_corex_is_unavailable(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'This agency website is not currently live.'], 403),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('agency', null));
    }
}
