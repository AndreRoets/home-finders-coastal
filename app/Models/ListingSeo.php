<?php

namespace App\Models;

use App\Support\DynamicSeo;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-authored SEO overrides for a single CoreX-backed property listing.
 * Blank columns fall back to the auto-generated meta in {@see DynamicSeo}.
 */
class ListingSeo extends Model
{
    protected $table = 'listing_seo';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * Non-empty override values keyed by SEO attribute, ready to merge over the
     * auto-generated defaults.
     *
     * @return array<string, string>
     */
    public function overrides(): array
    {
        return array_filter([
            'meta_title' => (string) $this->meta_title,
            'meta_description' => (string) $this->meta_description,
            'canonical_url' => (string) $this->canonical_url,
        ], static fn (string $value): bool => trim($value) !== '');
    }
}
