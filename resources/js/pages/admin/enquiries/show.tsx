import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

interface Enquiry {
    id: number;
    form: string;
    name: string;
    email: string;
    phone: string | null;
    message: string;
    listing_id: string | null;
    listing_reference: string | null;
    channel_label: string;
    page_url: string | null;
    landing_page: string | null;
    referrer: string | null;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    utm_term: string | null;
    utm_content: string | null;
    gclid: string | null;
    fbclid: string | null;
    first_seen_at: string | null;
    received_at: string | null;
}

function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('en-ZA', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

function Row({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="grid gap-1 py-2 sm:grid-cols-[11rem_1fr] sm:gap-4">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm break-all">{value || <span className="text-muted-foreground">—</span>}</dd>
        </div>
    );
}

export default function EnquiryShow({ enquiry }: { enquiry: Enquiry }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Enquiries', href: '/admin/enquiries' },
        { title: enquiry.name, href: `/admin/enquiries/${enquiry.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Enquiry from ${enquiry.name}`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{enquiry.name}</h1>
                    <p className="text-muted-foreground text-sm">
                        {enquiry.form} · received {formatDate(enquiry.received_at)}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Message</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="divide-y">
                            <Row label="Email" value={enquiry.email} />
                            <Row label="Phone" value={enquiry.phone} />
                            {enquiry.listing_reference && (
                                <Row label="Property" value={`${enquiry.listing_reference} (CoreX #${enquiry.listing_id})`} />
                            )}
                        </dl>
                        <p className="mt-4 text-sm whitespace-pre-line">{enquiry.message}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            Attribution <Badge>{enquiry.channel_label}</Badge>
                        </CardTitle>
                        <CardDescription>
                            The raw first-touch values the channel was worked out from, captured on the visitor&rsquo;s first page.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="divide-y">
                            <Row label="utm_source" value={enquiry.utm_source} />
                            <Row label="utm_medium" value={enquiry.utm_medium} />
                            <Row label="utm_campaign" value={enquiry.utm_campaign} />
                            <Row label="utm_term" value={enquiry.utm_term} />
                            <Row label="utm_content" value={enquiry.utm_content} />
                            <Row label="gclid" value={enquiry.gclid} />
                            <Row label="fbclid" value={enquiry.fbclid} />
                            <Row label="Referrer" value={enquiry.referrer} />
                            <Row label="Landing page" value={enquiry.landing_page} />
                            <Row label="First visit" value={formatDate(enquiry.first_seen_at)} />
                            <Row label="Submitted from" value={enquiry.page_url} />
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
