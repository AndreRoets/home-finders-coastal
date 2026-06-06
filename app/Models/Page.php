<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PageRedirect, $this>
     */
    public function redirects(): HasMany
    {
        return $this->hasMany(PageRedirect::class);
    }

    /**
     * The public URL path for this page, always with a leading slash.
     */
    public function path(): string
    {
        return '/'.ltrim($this->slug, '/');
    }

    /**
     * The robots meta directive (e.g. "index, follow" / "noindex, nofollow").
     */
    public function robotsDirective(): string
    {
        return implode(', ', [
            $this->robots_index ? 'index' : 'noindex',
            $this->robots_follow ? 'follow' : 'nofollow',
        ]);
    }
}
