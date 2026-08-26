<?php

namespace App\Models;

use App\Services\Stats\ListingStatEvent;
use Database\Factories\ListingStatFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One day's counter for a single CoreX listing and engagement event.
 *
 * @property string $listing_id CoreX listing id (this database stores no listings)
 * @property string|null $reference CoreX agency reference, for matching on either key
 * @property ListingStatEvent $event
 * @property Carbon $date
 * @property int $count Lifetime total for this listing/event/day
 * @property int $pushed_count How much of $count CoreX has already been sent
 */
class ListingStat extends Model
{
    /** @use HasFactory<ListingStatFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'listing_id',
        'reference',
        'event',
        'date',
        'count',
        'pushed_count',
        'pushed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => ListingStatEvent::class,
            'count' => 'integer',
            'pushed_count' => 'integer',
            'pushed_at' => 'datetime',
        ];
    }

    /**
     * The day a counter belongs to, always stored as a bare `Y-m-d` string.
     *
     * A plain `date` cast would serialise through the connection's datetime
     * format and write "2026-08-26 00:00:00", which then fails to match the
     * "2026-08-26" the recorder looks the row up by — and, because bulk
     * impression writes go through the query builder and skip casts, the two
     * paths would disagree on the same day. Pinning the stored format keeps
     * every write comparable while reads still hand back a date object.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: static fn (string $value): Carbon => Carbon::parse($value)->startOfDay(),
            set: static fn (DateTimeInterface|string $value): string => Carbon::parse($value)->toDateString(),
        );
    }

    /**
     * Rows carrying counts CoreX has not been told about yet.
     *
     * @param  Builder<ListingStat>  $query
     * @return Builder<ListingStat>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereColumn('count', '>', 'pushed_count');
    }

    /**
     * The outstanding delta for this row — what the next push owes CoreX.
     */
    public function delta(): int
    {
        return max(0, $this->count - $this->pushed_count);
    }
}
