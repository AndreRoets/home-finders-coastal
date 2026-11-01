import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Download, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Enquiries', href: '/admin/enquiries' },
];

interface Filters {
    range: string;
    from: string;
    to: string;
    channel: string | null;
    campaign: string | null;
    form: string | null;
    q: string;
}

interface Option {
    value: string;
    label: string;
}

interface ChannelCount extends Option {
    count: number;
}

interface CampaignCount {
    channel: string;
    campaign: string;
    count: number;
}

interface MonthRow {
    month: string;
    label: string;
    counts: Record<string, number>;
    total: number;
}

interface EnquiryRow {
    id: number;
    form: string;
    name: string;
    email: string;
    listing_reference: string | null;
    excerpt: string;
    channel: string;
    channel_label: string;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    referrer: string | null;
    gclid: boolean;
    fbclid: boolean;
    received_at: string | null;
}

interface Props {
    filters: Filters;
    channels: ChannelCount[];
    campaigns: CampaignCount[];
    monthly: MonthRow[];
    enquiries: EnquiryRow[];
    meta: { current_page: number; last_page: number; total: number };
    options: { channels: Option[]; forms: Option[]; campaigns: string[]; presets: number[] };
}

const selectClass =
    'border-input bg-background h-9 rounded-md border px-3 text-sm shadow-xs focus-visible:ring-ring/50 focus-visible:outline-none focus-visible:ring-[3px]';

/** The query string for a set of filters, dropping empties (and the defaults' noise). */
function params(filters: Filters, page?: number): Record<string, string> {
    const out: Record<string, string> = { range: filters.range };

    if (filters.range === 'custom') {
        out.from = filters.from;
        out.to = filters.to;
    }

    (['channel', 'campaign', 'form', 'q'] as const).forEach((key) => {
        if (filters[key]) {
            out[key] = filters[key] as string;
        }
    });

    if (page && page > 1) {
        out.page = String(page);
    }

    return out;
}

function formatDate(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('en-ZA', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

function referrerHost(url: string | null): string | null {
    if (!url) {
        return null;
    }

    try {
        return new URL(url).hostname.replace(/^www\./, '');
    } catch {
        return url;
    }
}

/** One horizontal bar per row, a single brand hue; the count is always labelled. */
function Bars({ rows, total }: { rows: { key: string; label: string; count: number }[]; total: number }) {
    const max = Math.max(1, ...rows.map((row) => row.count));

    return (
        <ul className="space-y-2">
            {rows.map((row) => {
                const pct = total ? Math.round((row.count / total) * 100) : 0;

                return (
                    <li
                        key={row.key}
                        className="group grid grid-cols-[minmax(6rem,10rem)_1fr_4.5rem] items-center gap-3 rounded-sm text-sm"
                        title={`${row.label}: ${row.count} enquir${row.count === 1 ? 'y' : 'ies'} (${pct}%)`}
                    >
                        <span className="truncate">{row.label}</span>
                        <span className="bg-muted h-2.5 overflow-hidden rounded-full">
                            <span
                                className="bg-primary block h-full rounded-full transition-opacity group-hover:opacity-80"
                                style={{ width: `${(row.count / max) * 100}%` }}
                            />
                        </span>
                        <span className="text-muted-foreground text-right tabular-nums">
                            <span className="text-foreground font-medium">{row.count}</span> · {pct}%
                        </span>
                    </li>
                );
            })}
        </ul>
    );
}

export default function EnquiriesIndex({ filters, channels, campaigns, monthly, enquiries, meta, options }: Props) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [q, setQ] = useState(filters.q);

    const visit = (next: Partial<Filters>, page?: number) => {
        router.get(route('admin.enquiries.index'), params({ ...filters, ...next }, page), { preserveState: true, preserveScroll: true });
    };

    const applyCustom: FormEventHandler = (e) => {
        e.preventDefault();
        visit({ range: 'custom', from, to });
    };

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        visit({ q });
    };

    const rangeTotal = channels.reduce((sum, c) => sum + c.count, 0);
    const paidTotal = campaigns.reduce((sum, c) => sum + c.count, 0);
    const listFiltered = Boolean(filters.channel || filters.campaign || filters.form || filters.q);
    const rangeLabel = filters.range === 'custom' ? `${filters.from} to ${filters.to}` : `Last ${filters.range} days`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Enquiries" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Enquiries</h1>
                        <p className="text-muted-foreground text-sm">
                            Every form submitted on the site, and the marketing channel each visitor first arrived from.
                        </p>
                    </div>
                    <Button asChild variant="secondary">
                        <a href={route('admin.enquiries.download', params(filters))}>
                            <Download className="h-4 w-4" />
                            Download CSV
                        </a>
                    </Button>
                </div>

                {/* Date range — scopes everything on the page. */}
                <div className="flex flex-wrap items-center gap-2">
                    {options.presets.map((days) => (
                        <Button
                            key={days}
                            size="sm"
                            variant={filters.range === String(days) ? 'default' : 'outline'}
                            onClick={() => visit({ range: String(days) })}
                        >
                            Last {days} days
                        </Button>
                    ))}
                    <form onSubmit={applyCustom} className="flex flex-wrap items-center gap-2">
                        <Input type="date" value={from} max={to} onChange={(e) => setFrom(e.target.value)} className="h-9 w-auto" aria-label="From" />
                        <span className="text-muted-foreground text-sm">to</span>
                        <Input type="date" value={to} min={from} onChange={(e) => setTo(e.target.value)} className="h-9 w-auto" aria-label="To" />
                        <Button type="submit" size="sm" variant={filters.range === 'custom' ? 'default' : 'outline'}>
                            Custom range
                        </Button>
                    </form>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Where enquiries came from</CardTitle>
                            <CardDescription>
                                {rangeLabel} · <span className="text-foreground font-medium">{rangeTotal}</span> enquir
                                {rangeTotal === 1 ? 'y' : 'ies'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Bars rows={channels.map((c) => ({ key: c.value, label: c.label, count: c.count }))} total={rangeTotal} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Paid campaigns</CardTitle>
                            <CardDescription>
                                Paid Search &amp; Paid Social enquiries by utm_campaign · {rangeLabel} ·{' '}
                                <span className="text-foreground font-medium">{paidTotal}</span> total
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {campaigns.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No paid enquiries in this range.</p>
                            ) : (
                                <Bars
                                    rows={campaigns.map((c) => ({
                                        key: `${c.channel}|${c.campaign}`,
                                        label: `${c.campaign} (${c.channel})`,
                                        count: c.count,
                                    }))}
                                    total={paidTotal}
                                />
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Monthly summary</CardTitle>
                        <CardDescription>Enquiries per channel per month — compare against GA4&rsquo;s form_start by month.</CardDescription>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full min-w-[48rem] text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-b text-left text-xs">
                                    <th className="py-2 pr-4 font-medium">Month</th>
                                    {options.channels.map((c) => (
                                        <th key={c.value} className="px-2 py-2 text-right font-medium">
                                            {c.label}
                                        </th>
                                    ))}
                                    <th className="py-2 pl-2 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {monthly.map((m) => (
                                    <tr key={m.month} className="border-b last:border-0">
                                        <td className="py-2 pr-4 whitespace-nowrap">{m.label}</td>
                                        {options.channels.map((c) => (
                                            <td
                                                key={c.value}
                                                className={cn('px-2 py-2 text-right tabular-nums', !m.counts[c.value] && 'text-muted-foreground')}
                                            >
                                                {m.counts[c.value] ?? 0}
                                            </td>
                                        ))}
                                        <td className="py-2 pl-2 text-right font-medium tabular-nums">{m.total}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                {/* List filters — narrow the list (and the CSV) within the date range. */}
                <div className="flex flex-wrap items-center gap-2">
                    <form onSubmit={submitSearch} className="flex items-center gap-2">
                        <Input
                            type="search"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Search name, email, message, campaign…"
                            className="h-9 w-72"
                        />
                        <Button type="submit" size="sm" variant="secondary">
                            <Search className="h-4 w-4" />
                            Search
                        </Button>
                    </form>
                    <select
                        aria-label="Channel"
                        className={selectClass}
                        value={filters.channel ?? ''}
                        onChange={(e) => visit({ channel: e.target.value || null })}
                    >
                        <option value="">All channels</option>
                        {options.channels.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </select>
                    <select
                        aria-label="Campaign"
                        className={selectClass}
                        value={filters.campaign ?? ''}
                        onChange={(e) => visit({ campaign: e.target.value || null })}
                    >
                        <option value="">All campaigns</option>
                        {options.campaigns.map((c) => (
                            <option key={c} value={c}>
                                {c}
                            </option>
                        ))}
                    </select>
                    <select
                        aria-label="Form"
                        className={selectClass}
                        value={filters.form ?? ''}
                        onChange={(e) => visit({ form: e.target.value || null })}
                    >
                        <option value="">All forms</option>
                        {options.forms.map((f) => (
                            <option key={f.value} value={f.value}>
                                {f.label}
                            </option>
                        ))}
                    </select>
                    {listFiltered && (
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => {
                                setQ('');
                                visit({ channel: null, campaign: null, form: null, q: '' });
                            }}
                        >
                            Clear filters
                        </Button>
                    )}
                    <span className="text-muted-foreground ml-auto text-sm">
                        {meta.total} enquir{meta.total === 1 ? 'y' : 'ies'}
                    </span>
                </div>

                <Card className="overflow-x-auto p-0">
                    <table className="w-full min-w-[60rem] text-left text-sm">
                        <thead>
                            <tr className="text-muted-foreground border-b text-xs">
                                <th className="px-4 py-3 font-medium">Received</th>
                                <th className="px-4 py-3 font-medium">From</th>
                                <th className="px-4 py-3 font-medium">Message</th>
                                <th className="px-4 py-3 font-medium">Channel</th>
                                <th className="px-4 py-3 font-medium">Source</th>
                                <th className="px-4 py-3 font-medium">
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {enquiries.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground px-4 py-10 text-center">
                                        {listFiltered ? 'No enquiries match these filters.' : 'No enquiries in this date range.'}
                                    </td>
                                </tr>
                            )}
                            {enquiries.map((e) => {
                                const sourceMedium = [e.utm_source, e.utm_medium].filter(Boolean).join(' / ');
                                const host = referrerHost(e.referrer);
                                const clickIds = [e.gclid && 'gclid', e.fbclid && 'fbclid'].filter(Boolean).join(', ');

                                return (
                                    <tr key={e.id} className="border-b align-top last:border-0">
                                        <td className="text-muted-foreground px-4 py-3 whitespace-nowrap">{formatDate(e.received_at)}</td>
                                        <td className="px-4 py-3">
                                            <span className="block font-medium">{e.name}</span>
                                            <span className="text-muted-foreground block text-xs">{e.email}</span>
                                        </td>
                                        <td className="text-muted-foreground max-w-xs px-4 py-3">
                                            <Badge variant="outline">
                                                {e.form}
                                                {e.listing_reference ? ` · ${e.listing_reference}` : ''}
                                            </Badge>
                                            <span className="mt-1.5 block">{e.excerpt}</span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <Badge variant={e.channel === 'direct' ? 'secondary' : 'default'}>{e.channel_label}</Badge>
                                        </td>
                                        <td className="text-muted-foreground max-w-[16rem] px-4 py-3 text-xs">
                                            {sourceMedium || e.utm_campaign ? (
                                                <>
                                                    {sourceMedium && <span className="text-foreground block">{sourceMedium}</span>}
                                                    {e.utm_campaign && <span className="block">{e.utm_campaign}</span>}
                                                </>
                                            ) : host ? (
                                                <span className="block truncate" title={e.referrer ?? ''}>
                                                    {host} (referrer)
                                                </span>
                                            ) : (
                                                <span className="block">(direct)</span>
                                            )}
                                            {clickIds && <span className="block">{clickIds}</span>}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={route('admin.enquiries.show', e.id)}
                                                className="text-muted-foreground hover:text-foreground text-sm"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </Card>

                {meta.last_page > 1 && (
                    <div className="flex items-center justify-between gap-4">
                        <span className="text-muted-foreground text-sm">
                            Page {meta.current_page} of {meta.last_page}
                        </span>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={() => visit({}, meta.current_page - 1)}>
                                <ChevronLeft className="h-4 w-4" />
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={meta.current_page >= meta.last_page}
                                onClick={() => visit({}, meta.current_page + 1)}
                            >
                                Next
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
