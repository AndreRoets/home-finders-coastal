import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { markVerbatimTitle } from '@/lib/document-title';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface PublicLayoutProps {
    /** Fallback only: the server-resolved SEO title (`seo.title`) wins when present. */
    title: string;
    /** Page background tone. `light` (default) is the white theme; `dark` is reserved for inset sections. */
    tone?: 'dark' | 'light';
}

export default function PublicLayout({ title, tone = 'light', children }: PropsWithChildren<PublicLayoutProps>) {
    const { seo } = usePage<SharedData>().props;
    const documentTitle = seo?.title ? markVerbatimTitle(seo.title) : title;

    return (
        <div className={cn('flex min-h-screen flex-col', tone === 'light' ? 'bg-white text-neutral-800' : 'bg-ink text-neutral-100')}>
            <Head title={documentTitle} />
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
