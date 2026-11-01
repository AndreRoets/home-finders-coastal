<?php

namespace Tests\Feature\Public;

use App\Mail\ContactMessage;
use App\Mail\PropertyEnquiry;
use App\Models\Enquiry;
use App\Models\SiteSetting;
use App\Services\Enquiries\Channel;
use App\Services\Enquiries\EnquiryForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every public form saves an enquiry with the visitor's first touch (posted by
 * resources/js/lib/attribution.ts as `attribution`) and the channel it is
 * credited to, and only flashes `form_submitted` — which fires GA4's
 * form_submit — once that save has succeeded.
 */
class EnquiryAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::fake([
            '*/listings/42' => Http::response(['data' => [
                'id' => 42,
                'reference' => 'HFC0042',
                'title' => 'Sea View Villa',
                'listing_type' => 'sale',
                'status' => 'for_sale',
                'agents' => [['is_primary' => true, 'id' => 7, 'name' => 'Thandi Mbeki', 'email' => 'thandi@example.test']],
            ]]),
            '*/leads' => Http::response(['data' => ['id' => 1001]], 201),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);
    }

    /**
     * The visits the brief asks to verify: an ad click with gclid + UTMs, a
     * Facebook referral and a direct visit.
     *
     * @return array<string, array{0: array<string, string>, 1: Channel}>
     */
    public static function visits(): array
    {
        return [
            'google ad (gclid + utm)' => [[
                'landing_page' => 'https://hfc.test/for-sale?utm_source=google&utm_medium=cpc&utm_campaign=winter-sale&utm_term=uvongo+houses&utm_content=ad-a&gclid=Cj0KCQ',
                'utm_source' => 'google',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'winter-sale',
                'utm_term' => 'uvongo houses',
                'utm_content' => 'ad-a',
                'gclid' => 'Cj0KCQ',
                'first_seen_at' => '2026-10-30T08:00:00.000Z',
            ], Channel::PaidSearch],
            'facebook referrer' => [[
                'landing_page' => 'https://hfc.test/',
                'referrer' => 'https://l.facebook.com/',
                'first_seen_at' => '2026-10-30T08:00:00.000Z',
            ], Channel::OrganicSocial],
            'direct visit' => [[
                'landing_page' => 'https://hfc.test/',
                'first_seen_at' => '2026-10-30T08:00:00.000Z',
            ], Channel::Direct],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fields(array $attribution = []): array
    {
        return [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+27 82 123 4567',
            'message' => 'Please call me.',
            'company' => '',
            'attribution' => ['page_url' => 'https://hfc.test/contact', ...$attribution],
        ];
    }

    /**
     * @param  array<string, string>  $attribution
     */
    #[DataProvider('visits')]
    public function test_the_contact_form_saves_the_enquiry_with_its_channel_and_raw_values(array $attribution, Channel $channel): void
    {
        $this->post(route('contact.send'), $this->fields($attribution))
            ->assertRedirect()
            ->assertSessionHas('form_submitted', 'contact');

        $enquiry = Enquiry::query()->sole();

        $this->assertSame(EnquiryForm::Contact, $enquiry->form);
        $this->assertSame($channel, $enquiry->channel);
        $this->assertSame('Jane Smith', $enquiry->name);
        $this->assertSame('https://hfc.test/contact', $enquiry->page_url);
        $this->assertSame('2026-10-30 08:00:00', $enquiry->first_seen_at->utc()->toDateTimeString());

        foreach ($attribution as $key => $value) {
            if ($key !== 'first_seen_at') {
                $this->assertSame($value, $enquiry->{$key}, "Raw {$key} was not stored verbatim.");
            }
        }

        Mail::assertSent(ContactMessage::class);
    }

    /**
     * @param  array<string, string>  $attribution
     */
    #[DataProvider('visits')]
    public function test_the_property_enquiry_form_saves_the_enquiry_with_its_channel_and_listing(array $attribution, Channel $channel): void
    {
        $this->post(route('property.enquire', 42), $this->fields($attribution))
            ->assertRedirect()
            ->assertSessionHas('form_submitted', 'property_enquiry');

        $enquiry = Enquiry::query()->sole();

        $this->assertSame(EnquiryForm::PropertyEnquiry, $enquiry->form);
        $this->assertSame($channel, $enquiry->channel);
        $this->assertSame('42', $enquiry->listing_id);
        $this->assertSame('HFC0042', $enquiry->listing_reference);
        $this->assertSame($attribution['landing_page'], $enquiry->landing_page);

        Mail::assertSent(PropertyEnquiry::class);
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/leads'));
    }

    public function test_a_submission_without_attribution_is_still_saved_as_direct(): void
    {
        $fields = $this->fields();
        unset($fields['attribution']);

        $this->post(route('contact.send'), $fields, ['Referer' => 'https://hfc.test/contact'])
            ->assertSessionHas('form_submitted', 'contact');

        $enquiry = Enquiry::query()->sole();
        $this->assertSame(Channel::Direct, $enquiry->channel);
        $this->assertSame('https://hfc.test/contact', $enquiry->page_url);
        $this->assertNotNull($enquiry->first_seen_at);
    }

    public function test_a_referrer_from_our_own_site_is_ignored(): void
    {
        $this->post(route('contact.send'), $this->fields(['referrer' => 'http://localhost/for-sale']));

        $enquiry = Enquiry::query()->sole();
        $this->assertNull($enquiry->referrer);
        $this->assertSame(Channel::Direct, $enquiry->channel);
    }

    public function test_over_long_values_are_truncated_rather_than_rejected(): void
    {
        $this->post(route('contact.send'), $this->fields(['utm_campaign' => str_repeat('a', 400)]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('form_submitted');

        $this->assertSame(255, strlen(Enquiry::query()->sole()->utm_campaign));
    }

    public function test_a_future_first_seen_time_falls_back_to_now(): void
    {
        $this->post(route('contact.send'), $this->fields(['first_seen_at' => '2999-01-01T00:00:00Z']));

        $this->assertTrue(Enquiry::query()->sole()->first_seen_at->isToday());
    }

    public function test_the_channel_lists_in_settings_drive_the_classification(): void
    {
        SiteSetting::current()->update(['channel_social_sources' => "mastodon.social\n"]);

        $this->post(route('contact.send'), $this->fields(['referrer' => 'https://mastodon.social/@hfc']));
        $this->post(route('contact.send'), $this->fields(['referrer' => 'https://l.facebook.com/']));

        $this->assertSame(
            [Channel::OrganicSocial, Channel::Referral],
            Enquiry::query()->orderBy('id')->pluck('channel')->all(),
        );
    }

    public function test_when_the_save_fails_the_enquiry_still_goes_out_but_ga4_is_not_told(): void
    {
        Schema::drop('enquiries');

        $this->post(route('contact.send'), $this->fields(['gclid' => 'abc']))
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionMissing('form_submitted');

        Mail::assertSent(ContactMessage::class);
    }

    public function test_a_validation_failure_saves_nothing(): void
    {
        $this->post(route('contact.send'), ['name' => ''] + $this->fields())
            ->assertSessionHasErrors();

        $this->post(route('contact.send'), ['company' => 'bot'] + $this->fields())
            ->assertSessionHasErrors('company');

        $this->assertSame(0, Enquiry::query()->count());
    }

    public function test_the_attribution_window_is_shared_with_the_browser(): void
    {
        SiteSetting::current()->update(['attribution_window_days' => 30]);

        $this->get(route('contact'))
            ->assertInertia(fn ($page) => $page->where('attribution.windowDays', 30));
    }
}
