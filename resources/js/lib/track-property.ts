/**
 * Listing interactions the server cannot see on its own — opening the photo
 * gallery, tapping an agent's phone or email, sharing a property.
 *
 * Page views, results-page impressions and enquiries are counted server-side,
 * so they are deliberately absent here; the endpoint rejects anything outside
 * this list. Everything recorded is pushed on to CoreX in batches so the
 * agency sees it against the property in their CRM.
 */
export type PropertyEvent = 'gallery_open' | 'phone_click' | 'email_click' | 'share_click';

/**
 * Report an interaction against a listing. Fire-and-forget: it never throws,
 * never blocks navigation and never surfaces a failure to the visitor — a lost
 * beacon is a lost count, nothing more.
 *
 * `sendBeacon` is preferred because it survives the page being unloaded, which
 * matters for the tel:/mailto: links that navigate away on click. Browsers
 * without it fall back to a keepalive fetch.
 */
export function trackPropertyEvent(listingId: number | string, event: PropertyEvent): void {
    if (typeof window === 'undefined' || !listingId) {
        return;
    }

    const url = '/api/listing-events';
    const body = JSON.stringify({ listing_id: String(listingId), event });

    try {
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
            return;
        }

        void fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body,
            keepalive: true,
        }).catch(() => {
            // Counting is best effort — never let it reach the visitor.
        });
    } catch {
        // Same: a blocked or unavailable beacon must stay invisible.
    }
}
