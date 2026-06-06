<?php

namespace Tests\Feature\Public;

use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The contact form (POST /contact) emails the agency's public address (pulled
 * from CoreX), validates its input, and is guarded by a honeypot. This suite
 * controls its own /agency stub so it can assert on the resolved recipient.
 */
class ContactFormTest extends TestCase
{
    protected bool $fakeAgency = false;

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
            'message' => 'Please call me about the Uvongo listing.',
            'company' => '', // honeypot left empty
        ], $overrides);
    }

    protected function fakeAgencyEmail(?string $email): void
    {
        $contact = $email !== null ? ['email' => $email] : [];

        Http::fake([
            '*/agency*' => Http::response(['data' => ['name' => 'HFC', 'contact' => $contact]]),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);
    }

    public function test_it_emails_the_agency_address_and_flashes_success(): void
    {
        Mail::fake();
        $this->fakeAgencyEmail('info@homefinderscoastal.co.za');

        $this->post(route('contact.send'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail): bool {
            return $mail->hasTo('info@homefinderscoastal.co.za')
                && $mail->hasReplyTo('jane@example.com')
                && $mail->senderName === 'Jane Smith'
                && str_contains($mail->body, 'Uvongo');
        });
    }

    public function test_it_falls_back_to_the_configured_address_when_corex_has_no_email(): void
    {
        config(['mail.from.address' => 'fallback@hfc.test']);
        Mail::fake();
        $this->fakeAgencyEmail(null);

        $this->post(route('contact.send'), $this->payload())->assertRedirect();

        Mail::assertSent(ContactMessage::class, fn (ContactMessage $mail): bool => $mail->hasTo('fallback@hfc.test'));
    }

    public function test_it_validates_required_fields(): void
    {
        Mail::fake();
        $this->fakeAgencyEmail('info@hfc.test');

        $this->post(route('contact.send'), $this->payload(['name' => '', 'email' => 'not-an-email', 'message' => '']))
            ->assertSessionHasErrors(['name', 'email', 'message']);

        Mail::assertNothingSent();
    }

    public function test_the_honeypot_blocks_bots(): void
    {
        Mail::fake();
        $this->fakeAgencyEmail('info@hfc.test');

        $this->post(route('contact.send'), $this->payload(['company' => 'Spammy Co']))
            ->assertSessionHasErrors('company');

        Mail::assertNothingSent();
    }
}
