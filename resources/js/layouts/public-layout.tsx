import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { cn } from '@/lib/utils';
import { Head } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface PublicLayoutProps {
    title: string;
    /** Page background tone. `light` (default) is the white theme; `dark` is reserved for inset sections. */
    tone?: 'dark' | 'light';
}

export default function PublicLayout({ title, tone = 'light', children }: PropsWithChildren<PublicLayoutProps>) {
    return (
        <div className={cn('flex min-h-screen flex-col', tone === 'light' ? 'bg-white text-neutral-800' : 'bg-ink text-neutral-100')}>
            <Head title={title} />
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
