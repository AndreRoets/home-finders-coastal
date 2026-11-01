<?php

namespace Tests\Feature\Admin;

use App\Models\Enquiry;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Enquiries\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The Enquiries screen: channel chart and paid-campaign breakdown over a date
 * range, monthly summary, the filterable list, its CSV export and the detail
 * view — plus the attribution settings on the Marketing screen.
 */
class EnquiryPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-01 12:00:00'));
    }

    protected function enquiryOn(string $date): Enquiry
    {
        return Enquiry::factory()->create(['created_at' => Carbon::parse($date)]);
    }

    public function test_guests_cannot_see_enquiries(): void
    {
        $this->get('/admin/enquiries')->assertRedirect('/login');
        $this->get('/admin/enquiries/export')->assertRedirect('/login');
    }

    public function test_the_chart_shows_every_channel_for_the_date_range_with_a_total(): void
    {
        $this->actingAs(User::factory()->create());

        Enquiry::factory()->paidSearch()->count(2)->create(['created_at' => now()->subDays(3)]);
        Enquiry::factory()->organicSocial()->create(['created_at' => now()->subDays(10)]);
        Enquiry::factory()->create(['created_at' => now()->subDays(20)]);
        // Outside the default 30 days.
        Enquiry::factory()->paidSearch()->create(['created_at' => now()->subDays(45)]);

        $this->get('/admin/enquiries')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/enquiries/index')
                ->where('filters.range', '30')
                ->has('channels', 8)
                ->where('channels.0', ['value' => 'paid_search', 'label' => 'Paid Search', 'count' => 2])
                ->where('channels.3', ['value' => 'organic_social', 'label' => 'Organic Social', 'count' => 1])
                ->where('channels.6', ['value' => 'direct', 'label' => 'Direct', 'count' => 1])
                ->where('channels.7.count', 0)
                ->where('meta.total', 4));

        $this->get('/admin/enquiries?range=7')
            ->assertInertia(fn (Assert $page) => $page->where('channels.0.count', 2)->where('meta.total', 2));

        $this->get('/admin/enquiries?range=90')
            ->assertInertia(fn (Assert $page) => $page->where('channels.0.count', 3)->where('meta.total', 5));
    }

    public function test_a_custom_range_is_inclusive_and_an_invalid_one_falls_back_to_30_days(): void
    {
        $this->actingAs(User::factory()->create());

        $this->enquiryOn('2026-08-01 00:05:00');
        $this->enquiryOn('2026-08-31 23:55:00');
        $this->enquiryOn('2026-09-01 00:05:00');

        $this->get('/admin/enquiries?range=custom&from=2026-08-01&to=2026-08-31')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.range', 'custom')
                ->where('meta.total', 2));

        $this->get('/admin/enquiries?range=custom&from=2026-09-30&to=2026-08-01')
            ->assertInertia(fn (Assert $page) => $page->where('filters.range', '30')->where('meta.total', 0));
    }

    public function test_paid_enquiries_are_broken_down_by_campaign(): void
    {
        $this->actingAs(User::factory()->create());

        Enquiry::factory()->paidSearch('winter-sale')->count(3)->create();
        Enquiry::factory()->paidSearch('brand')->create();
        Enquiry::factory()->create(['channel' => Channel::PaidSocial, 'fbclid' => 'x']);
        Enquiry::factory()->organicSocial()->create(['utm_campaign' => 'not-paid']);

        $this->get('/admin/enquiries')
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns', 3)
                ->where('campaigns.0', ['channel' => 'Paid Search', 'campaign' => 'winter-sale', 'count' => 3])
                ->where('campaigns', fn ($campaigns) => collect($campaigns)->contains(['channel' => 'Paid Social', 'campaign' => '(not set)', 'count' => 1])));
    }

    public function test_the_monthly_summary_counts_each_channel_per_month_including_empty_months(): void
    {
        $this->actingAs(User::factory()->create());

        Enquiry::factory()->paidSearch()->create(['created_at' => Carbon::parse('2026-08-10')]);
        Enquiry::factory()->paidSearch()->create(['created_at' => Carbon::parse('2026-08-20')]);
        Enquiry::factory()->create(['created_at' => Carbon::parse('2026-10-05')]);

        $this->get('/admin/enquiries?range=custom&from=2026-08-01&to=2026-10-31')
            ->assertInertia(fn (Assert $page) => $page
                ->has('monthly', 3)
                ->where('monthly.0.month', '2026-08')
                ->where('monthly.0.counts.paid_search', 2)
                ->where('monthly.0.total', 2)
                ->where('monthly.1.month', '2026-09')
                ->where('monthly.1.total', 0)
                ->where('monthly.2.counts.direct', 1));
    }

    public function test_the_list_filters_by_channel_campaign_form_and_search(): void
    {
        $this->actingAs(User::factory()->create());

        Enquiry::factory()->paidSearch('winter-sale')->create(['name' => 'Ad Visitor']);
        Enquiry::factory()->paidSearch('brand')->create();
        Enquiry::factory()->organicSocial()->propertyEnquiry()->create(['name' => 'Facebook Visitor']);
        Enquiry::factory()->create(['name' => 'Direct Visitor']);

        $this->get('/admin/enquiries?channel=paid_search')
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.total', 2)
                // The chart still shows the whole range, not the filtered list.
                ->where('channels.6.count', 1));

        $this->get('/admin/enquiries?campaign=winter-sale')
            ->assertInertia(fn (Assert $page) => $page->where('meta.total', 1)->where('enquiries.0.name', 'Ad Visitor'));

        $this->get('/admin/enquiries?campaign='.urlencode('(not set)'))
            ->assertInertia(fn (Assert $page) => $page->where('meta.total', 2));

        $this->get('/admin/enquiries?form=property_enquiry')
            ->assertInertia(fn (Assert $page) => $page->where('meta.total', 1)->where('enquiries.0.name', 'Facebook Visitor'));

        $this->get('/admin/enquiries?q=Direct')
            ->assertInertia(fn (Assert $page) => $page->where('meta.total', 1));

        $this->get('/admin/enquiries?channel=nonsense')
            ->assertInertia(fn (Assert $page) => $page->where('filters.channel', null)->where('meta.total', 4));
    }

    public function test_list_rows_carry_source_medium_and_campaign(): void
    {
        $this->actingAs(User::factory()->create());

        $enquiry = Enquiry::factory()->paidSearch('winter-sale')->create();

        $this->get('/admin/enquiries')
            ->assertInertia(fn (Assert $page) => $page
                ->where('enquiries.0.id', $enquiry->id)
                ->where('enquiries.0.channel_label', 'Paid Search')
                ->where('enquiries.0.utm_source', 'google')
                ->where('enquiries.0.utm_medium', 'cpc')
                ->where('enquiries.0.utm_campaign', 'winter-sale')
                ->where('enquiries.0.gclid', true)
                ->has('options.campaigns', 2));
    }

    public function test_the_csv_export_is_the_filtered_list_with_raw_values(): void
    {
        $this->actingAs(User::factory()->create());

        Enquiry::factory()->paidSearch('winter-sale')->create(['name' => 'Ad Visitor']);
        Enquiry::factory()->create(['name' => '=HYPERLINK("http://evil")']);

        $csv = $this->get('/admin/enquiries/export?channel=paid_search')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));

        $this->assertCount(2, $rows);
        $this->assertSame('channel', $rows[0][7]);
        $this->assertSame('Ad Visitor', $rows[1][3]);
        $this->assertSame('Paid Search', $rows[1][7]);
        $this->assertSame('winter-sale', $rows[1][10]);
        $this->assertSame('abc123', $rows[1][13]);

        $all = $this->get('/admin/enquiries/export?channel=direct')->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $all);
    }

    public function test_the_detail_view_shows_every_raw_value(): void
    {
        $this->actingAs(User::factory()->create());

        $enquiry = Enquiry::factory()->paidSearch()->propertyEnquiry()->create(['utm_term' => 'uvongo houses']);

        $this->get("/admin/enquiries/{$enquiry->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/enquiries/show')
                ->where('enquiry.channel_label', 'Paid Search')
                ->where('enquiry.gclid', 'abc123')
                ->where('enquiry.utm_term', 'uvongo houses')
                ->where('enquiry.listing_reference', 'HFC0042')
                ->where('enquiry.landing_page', $enquiry->landing_page));
    }

    public function test_a_stored_channel_is_never_relabelled_by_later_rule_changes(): void
    {
        $this->actingAs(User::factory()->create());

        $enquiry = Enquiry::factory()->organicSocial()->create();
        SiteSetting::current()->update(['channel_social_sources' => 'mastodon.social']);

        $this->get("/admin/enquiries/{$enquiry->id}")
            ->assertInertia(fn (Assert $page) => $page->where('enquiry.channel_label', 'Organic Social'));
    }

    public function test_the_marketing_screen_edits_the_attribution_settings(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/marketing')
            ->assertInertia(fn (Assert $page) => $page
                ->where('attributionDefaults.attribution_window_days', 90)
                ->where('attributionDefaults.channel_paid_mediums', fn (string $value) => str_contains($value, "cpc\nppc")));

        $this->put('/admin/marketing', [
            'attribution_window_days' => 60,
            'channel_search_sources' => "google\nbing",
            'channel_social_sources' => "facebook\ninstagram",
            'channel_paid_mediums' => "cpc\nppc\npaid*",
            'channel_email_mediums' => 'email',
            'channel_social_mediums' => '*social*',
        ])->assertSessionHasNoErrors();

        $settings = SiteSetting::current();
        $this->assertSame(60, (int) $settings->attribution_window_days);
        $this->assertSame("google\nbing", $settings->channel_search_sources);

        $this->put('/admin/marketing', ['attribution_window_days' => 0])->assertSessionHasErrors('attribution_window_days');
    }
}
