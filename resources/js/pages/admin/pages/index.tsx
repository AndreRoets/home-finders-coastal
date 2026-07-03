import FlashMessages from '@/components/admin/flash-messages';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight, ExternalLink, Pencil, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Pages & SEO', href: '/admin/pages' },
];

interface PageRow {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    robots_index: boolean;
    meta_title: string | null;
    edit_url: string;
    view_url: string;
}

type PropertyStatus = 'for-sale' | 'to-rent' | 'sold';

interface PropertyRow {
    id: string;
    title: string;
    status: PropertyStatus;
    exclusive: boolean;
    customised: boolean;
    done: boolean;
    edit_url: string;
}

const statusLabels: Record<PropertyStatus, string> = {
    'for-sale': 'For Sale',
    'to-rent': 'To Rent',
    sold: 'Sold',
};

const statusStyles: Record<PropertyStatus, string> = {
    'for-sale': 'bg-emerald-600 hover:bg-emerald-600',
    'to-rent': 'bg-sky-600 hover:bg-sky-600',
    sold: 'bg-neutral-500 hover:bg-neutral-500',
};

// The tags an admin can filter properties by, in display order.
const propertyFilters: { value: string; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'for-sale', label: 'For Sale' },
    { value: 'to-rent', label: 'To Rent' },
    { value: 'sold', label: 'Sold' },
    { value: 'exclusive', label: 'Exclusive' },
    { value: 'done', label: 'Done' },
    { value: 'customised', label: 'Customised' },
];

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export default function PagesIndex({
    pages,
    properties,
    property_search,
    property_filter,
}: {
    pages: PageRow[];
    properties: Paginated<PropertyRow>;
    property_search: string;
    property_filter: string;
}) {
    const [search, setSearch] = useState(property_search ?? '');
    const activeFilter = property_filter || 'all';

    // Reloads the property list; page resets since neither param carries `page`.
    const reloadProperties = (params: { property_search?: string; property_filter?: string }) => {
        router.get(
            '/admin/pages',
            { property_search: search, property_filter: activeFilter, ...params },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        reloadProperties({ property_search: search });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pages & SEO" />

            <div className="flex flex-col gap-6 p-4">
                <FlashMessages />

                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Pages &amp; SEO</h1>
                    <p className="text-muted-foreground text-sm">Edit each page's URL slug, meta tags, social cards and structured data.</p>
                </div>

                <Card className="divide-y p-0">
                    {pages.map((page) => (
                        <div key={page.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">{page.name}</span>
                                    {!page.is_active && <Badge variant="secondary">Hidden</Badge>}
                                    {!page.robots_index && <Badge variant="outline">noindex</Badge>}
                                </div>
                                <div className="text-muted-foreground mt-0.5 flex flex-wrap items-center gap-x-3 text-sm">
                                    <code className="bg-muted rounded px-1.5 py-0.5 text-xs">{page.slug}</code>
                                    {page.meta_title && <span className="truncate">{page.meta_title}</span>}
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                <a href={page.view_url} target="_blank" rel="noreferrer">
                                    <Button variant="ghost" size="sm">
                                        <ExternalLink className="h-4 w-4" />
                                        View
                                    </Button>
                                </a>
                                <Link href={page.edit_url}>
                                    <Button size="sm">
                                        <Pencil className="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    ))}
                </Card>

                <div>
                    <h2 className="text-lg font-semibold tracking-tight">Properties</h2>
                    <p className="text-muted-foreground text-sm">
                        Live, syndicated listings. Override the auto-generated SEO for any individual property.
                    </p>
                </div>

                <form onSubmit={submitSearch} className="flex items-center gap-2">
                    <div className="relative flex-1">
                        <Search className="text-muted-foreground absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search properties by title…"
                            className="pl-8"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="flex flex-wrap items-center gap-2">
                    {propertyFilters.map((filter) => (
                        <Button
                            key={filter.value}
                            type="button"
                            size="sm"
                            variant={activeFilter === filter.value ? 'default' : 'outline'}
                            onClick={() => reloadProperties({ property_filter: filter.value })}
                        >
                            {filter.label}
                        </Button>
                    ))}
                </div>

                <Card className="divide-y p-0">
                    {properties.data.length === 0 ? (
                        <p className="text-muted-foreground p-4 text-sm">No properties found.</p>
                    ) : (
                        properties.data.map((property) => (
                            <div key={property.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex min-w-0 flex-wrap items-center gap-2">
                                    {property.done && (
                                        <span
                                            title="SEO done"
                                            className="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-600 text-white"
                                        >
                                            <Check className="h-3.5 w-3.5" />
                                        </span>
                                    )}
                                    <span className="truncate font-medium">{property.title}</span>
                                    <Badge className={`text-white ${statusStyles[property.status]}`}>{statusLabels[property.status]}</Badge>
                                    {property.exclusive && <Badge className="bg-amber-500 text-white hover:bg-amber-500">Exclusive</Badge>}
                                    {property.done && <Badge className="bg-green-600 text-white hover:bg-green-600">Done</Badge>}
                                    {property.customised && <Badge variant="secondary">Customised</Badge>}
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    <Link href={property.edit_url}>
                                        <Button size="sm">
                                            <Pencil className="h-4 w-4" />
                                            Edit SEO
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        ))
                    )}
                </Card>

                {properties.last_page > 1 && (
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-muted-foreground text-sm">
                            Showing {properties.from ?? 0}–{properties.to ?? 0} of {properties.total}
                        </p>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!properties.prev_page_url}
                                onClick={() => properties.prev_page_url && router.get(properties.prev_page_url, {}, { preserveScroll: true })}
                            >
                                <ChevronLeft className="h-4 w-4" />
                                Previous
                            </Button>
                            <span className="text-muted-foreground text-sm">
                                Page {properties.current_page} of {properties.last_page}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!properties.next_page_url}
                                onClick={() => properties.next_page_url && router.get(properties.next_page_url, {}, { preserveScroll: true })}
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
