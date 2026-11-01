<?php

namespace App\Services\Enquiries;

/**
 * The marketing channel an enquiry is credited to — GA4's default channel
 * group names, so panel numbers line up with GA4 reports.
 *
 * The backing value is what the enquiries table stores; add a case rather
 * than renaming one, or existing rows stop matching.
 */
enum Channel: string
{
    case PaidSearch = 'paid_search';
    case PaidSocial = 'paid_social';
    case OrganicSearch = 'organic_search';
    case OrganicSocial = 'organic_social';
    case Email = 'email';
    case Referral = 'referral';
    case Direct = 'direct';
    case Unassigned = 'unassigned';

    public function label(): string
    {
        return match ($this) {
            self::PaidSearch => 'Paid Search',
            self::PaidSocial => 'Paid Social',
            self::OrganicSearch => 'Organic Search',
            self::OrganicSocial => 'Organic Social',
            self::Email => 'Email',
            self::Referral => 'Referral',
            self::Direct => 'Direct',
            self::Unassigned => 'Unassigned',
        };
    }

    /**
     * Whether the channel is bought media — the ones broken down by campaign.
     */
    public function isPaid(): bool
    {
        return $this === self::PaidSearch || $this === self::PaidSocial;
    }

    /**
     * Every channel as a value/label pair, in display order.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $channel): array => ['value' => $channel->value, 'label' => $channel->label()],
            self::cases(),
        );
    }
}
