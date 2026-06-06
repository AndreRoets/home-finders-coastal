import FlashMessages from '@/components/admin/flash-messages';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ExternalLink, Pencil } from 'lucide-react';

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

export default function PagesIndex({ pages }: { pages: PageRow[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pages & SEO" />

            <div className="flex flex-col gap-6 p-4">
                <FlashMessages />

                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Pages &amp; SEO</h1>
                    <p className="text-sm text-muted-foreground">Edit each page's URL slug, meta tags, social cards and structured data.</p>
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
                                <div className="mt-0.5 flex flex-wrap items-center gap-x-3 text-sm text-muted-foreground">
                                    <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{page.slug}</code>
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
            </div>
        </AppLayout>
    );
}
