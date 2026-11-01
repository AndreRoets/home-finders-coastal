import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { captureFirstTouch } from '@/lib/attribution';
import { markVerbatimTitle } from '@/lib/document-title';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, type PropsWithChildren } from 'react';

/** First-touch capture runs once per full page load, not on every Inertia visit. */
let firstTouchCaptured = false;

interface PublicLayoutProps {
    /** Fallback only: the server-resolved SEO title (`seo.title`) wins when present. */
    title: string;
    /** Page background tone. `light` (default) is the white theme; `dark` is reserved for inset sections. */
    tone?: 'dark' | 'light';
}

export default function PublicLayout({ title, tone = 'light', children }: PropsWithChildren<PublicLayoutProps>) {
    const { seo, attribution } = usePage<SharedData>().props;
    const documentTitle = seo?.title ? markVerbatimTitle(seo.title) : title;

    useEffect(() => {
        if (!firstTouchCaptured) {
            firstTouchCaptured = true;
            captureFirstTouch(attribution?.windowDays ?? 90);
        }
    }, [attribution?.windowDays]);

    return (
        <div className={cn('flex min-h-screen flex-col', tone === 'light' ? 'bg-white text-neutral-800' : 'bg-ink text-neutral-100')}>
            <Head title={documentTitle} />
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
