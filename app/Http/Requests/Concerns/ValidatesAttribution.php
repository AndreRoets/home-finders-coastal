<?php

namespace App\Http\Requests\Concerns;

/**
 * Validation for the `attribution` object every public form posts alongside
 * its fields — the visitor's first touch, captured in the browser by
 * resources/js/lib/attribution.ts. All optional: a visitor whose browser
 * blocks storage still gets their enquiry through.
 */
trait ValidatesAttribution
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function attributionRules(): array
    {
        // Deliberately loose: an over-long value is truncated when saved rather
        // than failing a field the visitor cannot see or fix.
        $url = ['nullable', 'string'];
        $tag = ['nullable', 'string'];

        return [
            'attribution' => ['sometimes', 'nullable', 'array'],
            'attribution.page_url' => $url,
            'attribution.landing_page' => $url,
            'attribution.referrer' => $url,
            'attribution.utm_source' => $tag,
            'attribution.utm_medium' => $tag,
            'attribution.utm_campaign' => $tag,
            'attribution.utm_term' => $tag,
            'attribution.utm_content' => $tag,
            'attribution.gclid' => $tag,
            'attribution.fbclid' => $tag,
            'attribution.first_seen_at' => $tag,
        ];
    }
}
