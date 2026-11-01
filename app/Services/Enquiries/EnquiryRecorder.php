<?php

namespace App\Services\Enquiries;

use App\Models\Enquiry;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Saves a public form submission as an {@see Enquiry}, with the visitor's
 * first touch (captured in the browser, see resources/js/lib/attribution.ts)
 * and the channel it is credited to.
 *
 * Fails soft: a failed save is logged and returns null, so the visitor's
 * enquiry still reaches the agent. The caller only reports a GA4
 * `form_submit` when a row was actually saved.
 */
class EnquiryRecorder
{
    /** Longest UTM tag / click id kept (the column width). */
    protected const TAG_LIMIT = 255;

    /** Longest URL kept — enough for any real landing page. */
    protected const URL_LIMIT = 2000;

    /**
     * @param  array{name: string, email: string, phone?: ?string, message: string, listing_id?: ?string, listing_reference?: ?string}  $fields
     * @param  array<string, mixed>  $attribution  The validated `attribution` payload.
     */
    public function record(EnquiryForm $form, array $fields, array $attribution, Request $request): ?Enquiry
    {
        $touch = $this->touch($attribution, $request);

        try {
            $classifier = ChannelClassifier::fromSettings(SiteSetting::current());

            return Enquiry::query()->create([
                'form' => $form,
                'name' => $fields['name'],
                'email' => $fields['email'],
                'phone' => $fields['phone'] ?? null,
                'message' => $fields['message'],
                'listing_id' => $fields['listing_id'] ?? null,
                'listing_reference' => $fields['listing_reference'] ?? null,
                ...$touch,
                'channel' => $classifier->classify($touch),
            ]);
        } catch (Throwable $e) {
            Log::error('Enquiry could not be saved', [
                'form' => $form->value,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * The first-touch columns: what the browser sent, with a referrer from our
     * own site dropped (it says nothing about how the visitor found us) and a
     * missing first touch falling back to this request.
     *
     * @param  array<string, mixed>  $attribution
     * @return array<string, string|Carbon|null>
     */
    protected function touch(array $attribution, Request $request): array
    {
        $value = static fn (string $key, int $limit = self::TAG_LIMIT): ?string => filled($attribution[$key] ?? null)
            ? Str::limit(trim((string) $attribution[$key]), $limit, '')
            : null;

        $referrer = $value('referrer', self::URL_LIMIT);

        if ($referrer !== null && ChannelClassifier::host($referrer) === Str::lower(Str::after($request->getHost(), 'www.'))) {
            $referrer = null;
        }

        $pageUrl = $value('page_url', self::URL_LIMIT)
            ?? (filled($request->headers->get('referer')) ? Str::limit((string) $request->headers->get('referer'), self::URL_LIMIT, '') : null);

        return [
            'page_url' => $pageUrl,
            'landing_page' => $value('landing_page', self::URL_LIMIT) ?? $pageUrl,
            'referrer' => $referrer,
            'utm_source' => $value('utm_source'),
            'utm_medium' => $value('utm_medium'),
            'utm_campaign' => $value('utm_campaign'),
            'utm_term' => $value('utm_term'),
            'utm_content' => $value('utm_content'),
            'gclid' => $value('gclid'),
            'fbclid' => $value('fbclid'),
            'first_seen_at' => $this->firstSeenAt($value('first_seen_at')),
        ];
    }

    /**
     * The browser's first-seen timestamp, or now when it is missing, unparsable
     * or in the future (a wrong device clock).
     */
    protected function firstSeenAt(?string $value): Carbon
    {
        try {
            $at = $value !== null ? Carbon::parse($value) : null;
        } catch (Throwable) {
            $at = null;
        }

        return $at !== null && $at->isPast() ? $at : now();
    }
}
