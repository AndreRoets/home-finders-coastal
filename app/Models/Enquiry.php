<?php

namespace App\Models;

use App\Services\Enquiries\Channel;
use App\Services\Enquiries\EnquiryForm;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A form submission from the public site, plus the visitor's first touch.
 *
 * The email / CoreX lead is how the enquiry is handled; this row is the
 * website's own record of it, kept so marketing can see which channel and
 * campaign produced it. The raw UTM / click-id / referrer values are stored
 * next to the classified {@see Channel} so a person can check the call.
 *
 * @property EnquiryForm $form
 * @property Channel $channel
 * @property Carbon|null $first_seen_at
 */
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    /**
     * The first-touch fields captured in the browser and stored verbatim.
     *
     * @var list<string>
     */
    public const ATTRIBUTION_FIELDS = [
        'page_url',
        'landing_page',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'first_seen_at',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'form',
        'name',
        'email',
        'phone',
        'message',
        'listing_id',
        'listing_reference',
        ...self::ATTRIBUTION_FIELDS,
        'channel',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'form' => EnquiryForm::class,
            'channel' => Channel::class,
            'first_seen_at' => 'datetime',
        ];
    }

    /**
     * Free-text search across what someone remembers about an enquiry.
     *
     * @param  Builder<Enquiry>  $query
     * @return Builder<Enquiry>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('message', 'like', $like)
                ->orWhere('listing_reference', 'like', $like)
                ->orWhere('utm_source', 'like', $like)
                ->orWhere('utm_campaign', 'like', $like);
        });
    }
}
