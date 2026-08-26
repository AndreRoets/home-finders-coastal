<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingStatEventRequest;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use App\Services\Stats\ListingStatEvent;
use App\Services\Stats\ListingStatsRecorder;
use Illuminate\Http\Response;

/**
 * Beacon endpoint for the listing interactions only the browser can see —
 * opening the photo gallery, clicking an agent's phone or email, sharing.
 *
 * Page views, results-page impressions and enquiries are all counted
 * server-side instead, so this endpoint deliberately accepts only the small
 * allowlist in {@see ListingStatEvent::reportable()}.
 *
 * The listing id is resolved against CoreX (a cached read) before anything is
 * written, so junk ids can never create counter rows. Always answers 204: the
 * page has nothing to do with the result and must not wait on it.
 */
class ListingStatController extends Controller
{
    public function __construct(
        protected CorexClient $corex,
        protected ListingStatsRecorder $recorder,
    ) {}

    public function store(ListingStatEventRequest $request): Response
    {
        if ($this->recorder->isCrawler($request->userAgent())) {
            return response()->noContent();
        }

        $listing = $this->corex->listing(ListingMapper::idFromSlug($request->validated('listing_id')));

        if ($listing === []) {
            return response()->noContent();
        }

        $this->recorder->record(
            listingId: $listing['id'] ?? $request->validated('listing_id'),
            reference: isset($listing['reference']) ? (string) $listing['reference'] : null,
            event: $request->event(),
        );

        return response()->noContent();
    }
}
