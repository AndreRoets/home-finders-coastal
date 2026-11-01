/**
 * First-touch marketing attribution for the public forms.
 *
 * The page an ad, search result or referring site lands a visitor on is
 * rarely the page they enquire from. So the first page load records where
 * they came from (UTM tags, gclid/fbclid, referrer, landing page) in
 * localStorage, falling back to a first-party cookie, and every form posts it
 * with its fields as `attribution`. The server classifies it into a channel
 * (App\Services\Enquiries\ChannelClassifier) and saves it with the enquiry.
 *
 * First touch wins: a later visit never replaces it until it is older than
 * the window set in Marketing & Analytics (`attribution.windowDays`).
 */

const STORAGE_KEY = 'hfc_first_touch';

const PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid'] as const;

type Param = (typeof PARAMS)[number];

export type FirstTouch = Record<Param, string | null> & {
    landing_page: string;
    referrer: string | null;
    first_seen_at: string;
};

export type AttributionPayload = Partial<FirstTouch> & { page_url: string };

/** Kept for this page load when the browser blocks both storage options. */
let memoryTouch: FirstTouch | null = null;

function readStored(): FirstTouch | null {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (raw) {
            return JSON.parse(raw) as FirstTouch;
        }
    } catch {
        // Storage blocked or corrupt — fall through to the cookie.
    }

    try {
        const cookie = document.cookie.split('; ').find((part) => part.startsWith(`${STORAGE_KEY}=`));

        if (cookie) {
            return JSON.parse(decodeURIComponent(cookie.slice(STORAGE_KEY.length + 1))) as FirstTouch;
        }
    } catch {
        // Unreadable cookie — treat as no first touch.
    }

    return memoryTouch;
}

function store(touch: FirstTouch, windowDays: number): void {
    memoryTouch = touch;
    const json = JSON.stringify(touch);

    try {
        window.localStorage.setItem(STORAGE_KEY, json);

        return;
    } catch {
        // localStorage unavailable (private mode, blocked) — use a cookie.
    }

    try {
        document.cookie = `${STORAGE_KEY}=${encodeURIComponent(json)}; max-age=${windowDays * 86400}; path=/; samesite=lax`;
    } catch {
        // Cookies blocked too — the in-memory copy still covers this page load.
    }
}

/** The referrer, unless it is one of our own pages (says nothing about how they found us). */
function externalReferrer(): string | null {
    const referrer = document.referrer;

    if (!referrer) {
        return null;
    }

    try {
        const host = (value: string) => value.replace(/^www\./, '');

        return host(new URL(referrer).hostname) === host(window.location.hostname) ? null : referrer;
    } catch {
        return null;
    }
}

function isExpired(touch: FirstTouch, windowDays: number): boolean {
    const seen = Date.parse(touch.first_seen_at);

    return Number.isNaN(seen) || Date.now() - seen > windowDays * 86400 * 1000;
}

/**
 * Record this visit as the first touch unless one is already stored and still
 * inside the window. Call once, on the initial page load — Inertia's
 * client-side navigations keep the original document.referrer, so calling it
 * again would only ever re-record the same visit.
 */
export function captureFirstTouch(windowDays: number): void {
    if (typeof window === 'undefined') {
        return;
    }

    const existing = readStored();

    if (existing && !isExpired(existing, windowDays)) {
        memoryTouch = existing;

        return;
    }

    const query = new URLSearchParams(window.location.search);
    const params = Object.fromEntries(PARAMS.map((name) => [name, query.get(name)?.trim() || null])) as Record<Param, string | null>;

    store(
        {
            ...params,
            landing_page: window.location.href,
            referrer: externalReferrer(),
            first_seen_at: new Date().toISOString(),
        },
        windowDays,
    );
}

/** What a form posts as `attribution`: the first touch plus the page it is on. */
export function attributionPayload(): AttributionPayload {
    return {
        ...(readStored() ?? {}),
        page_url: window.location.href,
    };
}

/**
 * Tell GA4 a form was submitted — called with the `form_submitted` flash, which
 * the server only sets once the enquiry is saved, so GA4 counts real
 * submissions rather than attempts. Sent through gtag when the site loads it,
 * otherwise as a dataLayer event for a Tag Manager trigger (never both, so it
 * is not counted twice).
 */
export function reportFormSubmit(formName: string | null | undefined): void {
    if (!formName || typeof window === 'undefined') {
        return;
    }

    const w = window as unknown as { gtag?: (...args: unknown[]) => void; dataLayer?: unknown[] };

    if (typeof w.gtag === 'function') {
        w.gtag('event', 'form_submit', { form_name: formName });
    } else if (Array.isArray(w.dataLayer)) {
        w.dataLayer.push({ event: 'form_submit', form_name: formName });
    }
}
