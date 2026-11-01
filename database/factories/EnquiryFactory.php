<?php

namespace Database\Factories;

use App\Models\Enquiry;
use App\Services\Enquiries\Channel;
use App\Services\Enquiries\EnquiryForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    protected $model = Enquiry::class;

    /**
     * A direct-visit contact-page enquiry.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form' => EnquiryForm::Contact,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->sentence(),
            'page_url' => 'https://www.homefinderscoastal.co.za/contact',
            'landing_page' => 'https://www.homefinderscoastal.co.za/',
            'referrer' => null,
            'first_seen_at' => now(),
            'channel' => Channel::Direct,
        ];
    }

    /**
     * Arrived through a Google Ads click on the given campaign.
     */
    public function paidSearch(string $campaign = 'winter-sale'): static
    {
        return $this->state(fn (): array => [
            'landing_page' => 'https://www.homefinderscoastal.co.za/for-sale?gclid=abc&utm_campaign='.$campaign,
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => $campaign,
            'gclid' => 'abc123',
            'channel' => Channel::PaidSearch,
        ]);
    }

    /**
     * Arrived from an organic Facebook post.
     */
    public function organicSocial(): static
    {
        return $this->state(fn (): array => [
            'referrer' => 'https://l.facebook.com/',
            'channel' => Channel::OrganicSocial,
        ]);
    }

    /**
     * Sent from a property's sidebar enquiry form.
     */
    public function propertyEnquiry(): static
    {
        return $this->state(fn (): array => [
            'form' => EnquiryForm::PropertyEnquiry,
            'listing_id' => '42',
            'listing_reference' => 'HFC0042',
            'page_url' => 'https://www.homefinderscoastal.co.za/property/hfc0042',
        ]);
    }
}
