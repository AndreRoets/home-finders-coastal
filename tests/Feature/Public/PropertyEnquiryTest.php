<?php

namespace Tests\Feature\Public;

use App\Mail\PropertyEnquiry;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A property enquiry (POST /property/{id}/enquire) emails the listing's agent(s)
 * with the visitor as reply-to, pushes the lead to CoreX so it lands in the
 * agency's CRM, validates its input and is guarded by a honeypot.
 */
class PropertyEnquiryTest extends TestCase
{
    /**
     * Fake a single listing with an attributed agent (email on file) plus the
     * CoreX leads endpoint. A catch-all keeps any incidental call (e.g. agency)
     * from hitting the network.
     */
    protected function fakeListing(): void
    {
        Http::fake([
            '*/listings/42' => Http::response(['data' => [
                'id' => 42,
                'reference' => 'HFC0042',
                'title' => 'Sea View Villa',
                'listing_type' => 'sale',
                'status' => 'for_sale',
                'price_display' => 'R 5,000,000',
                'suburb' => 'Clifton',
                'agents' => [[
                    'is_primary' => true,
                    'id' => 7,
                    'name' => 'Thandi Mbeki',
                    'email' => 'thandi@example.test',
                    'photo_url' => 'https://corex.test/a.jpg',
                ]],
            ]]),
            '*/leads' => Http::response(['data' => ['id' => 1001]], 201),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+27 82 123 4567',
            'message' => 'I’m interested in this property, please contact me.',
            'company' => '', // honeypot left empty
        ], $overrides);
    }

    public function test_it_emails_the_agent_and_flashes_success(): void
    {
        Mail::fake();
        $this->fakeListing();

        $this->post(route('property.enquire', 42), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(PropertyEnquiry::class, function (PropertyEnquiry $mail): bool {
            return $mail->hasTo('thandi@example.test')
                && $mail->hasReplyTo('jane@example.com')
                && $mail->senderName === 'Jane Smith'
                && $mail->propertyTitle === 'Sea View Villa'
                && $mail->propertyReference === 'HFC0042';
        });
    }

    public function test_it_pushes_the_lead_to_corex(): void
    {
        Mail::fake();
        $this->fakeListing();

        $this->post(route('property.enquire', 42), $this->payload())->assertRedirect();

        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/leads')
                && $request['name'] === 'Jane Smith'
                && $request['email'] === 'jane@example.com'
                && $request['listing_id'] === 42
                && $request['listing_reference'] === 'HFC0042'
                && $request['agent_ids'] === [7]
                && $request['source'] === 'website';
        });
    }

    public function test_it_validates_required_fields(): void
    {
        Mail::fake();
        $this->fakeListing();

        $this->post(route('property.enquire', 42), $this->payload(['name' => '', 'email' => 'nope', 'message' => '']))
            ->assertSessionHasErrors(['name', 'email', 'message']);

        Mail::assertNothingSent();
    }

    public function test_the_honeypot_blocks_bots(): void
    {
        Mail::fake();
        $this->fakeListing();

        $this->post(route('property.enquire', 42), $this->payload(['company' => 'Spammy Co']))
            ->assertSessionHasErrors('company');

        Mail::assertNothingSent();
    }

    public function test_it_404s_for_an_unknown_property(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['data' => []])]);

        $this->post(route('property.enquire', 999), $this->payload())->assertNotFound();

        Mail::assertNothingSent();
    }
}
