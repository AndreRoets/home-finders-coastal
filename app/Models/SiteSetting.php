<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    /** How long a visitor's first touch is kept when no window is set. */
    public const DEFAULT_ATTRIBUTION_WINDOW_DAYS = 90;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The single settings row, created on first access.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
