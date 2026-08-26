<?php

namespace Database\Factories;

use App\Models\ListingStat;
use App\Services\Stats\ListingStatEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingStat>
 */
class ListingStatFactory extends Factory
{
    protected $model = ListingStat::class;

    /**
     * A counter for today with everything still outstanding.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => (string) fake()->unique()->numberBetween(1, 100000),
            'reference' => 'HFC'.fake()->unique()->numberBetween(1000, 9999),
            'event' => ListingStatEvent::DetailView,
            'date' => now()->toDateString(),
            'count' => fake()->numberBetween(1, 50),
            'pushed_count' => 0,
            'pushed_at' => null,
        ];
    }

    /**
     * A counter CoreX has already been told about in full.
     */
    public function pushed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'pushed_count' => $attributes['count'],
            'pushed_at' => now(),
        ]);
    }

    /**
     * A counter for a specific listing, event and day.
     */
    public function forListing(string $listingId, ListingStatEvent $event, ?string $date = null): static
    {
        return $this->state(fn (): array => [
            'listing_id' => $listingId,
            'event' => $event,
            'date' => $date ?? now()->toDateString(),
        ]);
    }
}
