import SiteFooter from '@/components/public/site-footer';
import SiteHeader from '@/components/public/site-header';
import { Head } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface PublicLayoutProps {
    title: string;
}

export default function PublicLayout({ title, children }: PropsWithChildren<PublicLayoutProps>) {
    return (
        <div className="flex min-h-screen flex-col bg-ink text-neutral-100">
            <Head title={title} />
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
