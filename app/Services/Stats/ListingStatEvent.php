<?php

namespace App\Services\Stats;

/**
 * The engagement events the website counts per listing and reports to CoreX.
 *
 * The backing values are the metric keys CoreX receives, so renaming one is a
 * breaking change to the stats contract — add a new case instead.
 */
enum ListingStatEvent: string
{
    /** The property detail page was rendered ("views"). */
    case DetailView = 'detail_view';

    /** As above, deduplicated per visitor within {@see ListingStatsRecorder::UNIQUE_VIEW_TTL}. */
    case UniqueDetailView = 'unique_detail_view';

    /** The listing appeared on a results page — search, bucket or home ("hits"). */
    case Impression = 'impression';

    /** The photo lightbox was opened on the detail page. */
    case GalleryOpen = 'gallery_open';

    /** The agent's phone number was clicked (tel: link). */
    case PhoneClick = 'phone_click';

    /** The agent's email address was clicked (mailto: link). */
    case EmailClick = 'email_click';

    /** The listing was shared, or the share sheet was opened. */
    case ShareClick = 'share_click';

    /** An enquiry form was submitted for the listing. */
    case Enquiry = 'enquiry';

    /**
     * Events the browser is allowed to report through the public beacon
     * endpoint. Everything else is recorded server-side only, so a script
     * cannot inflate view counts or fabricate enquiries.
     *
     * @return array<int, self>
     */
    public static function reportable(): array
    {
        return [
            self::GalleryOpen,
            self::PhoneClick,
            self::EmailClick,
            self::ShareClick,
        ];
    }

    /**
     * Backing values of {@see self::reportable()}, for validation rules.
     *
     * @return array<int, string>
     */
    public static function reportableValues(): array
    {
        return array_map(static fn (self $event): string => $event->value, self::reportable());
    }
}
