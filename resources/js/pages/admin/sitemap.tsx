import FlashMessages from '@/components/admin/flash-messages';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Check, CheckCircle2, ChevronDown, ChevronRight, Copy, ExternalLink, Map, RefreshCw } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Sitemap', href: '/admin/sitemap' },
];

interface SitemapUrl {
    loc: string;
    lastmod: string | null;
    changefreq: string | null;
    priority: string | null;
}

interface Section {
    key: string;
    label: string;
    description: string;
    dynamic: boolean;
    count: number;
    urls: SitemapUrl[];
}

interface Props {
    sitemapUrl: string;
    robotsUrl: string;
    robotsReferencesSitemap: boolean;
    searchConsoleVerified: boolean;
    corexAvailable: boolean;
    sections: Section[];
    total: number;
    previewLimit: number;
}

/** A short, human date for the lastmod column. */
function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString();
}

function CopyButton({ value }: { value: string }) {
    const [copied, setCopied] = useState(false);

    const copy = () => {
        void navigator.clipboard.writeText(value).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    return (
        <Button type="button" variant="outline" size="sm" onClick={copy}>
            {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
            {copied ? 'Copied' : 'Copy URL'}
        </Button>
    );
}

function SectionCard({ section, previewLimit, corexAvailable }: { section: Section; previewLimit: number; corexAvailable: boolean }) {
    const [open, setOpen] = useState(false);
    const hidden = section.count - section.urls.length;
    const stale = section.dynamic && !corexAvailable;

    return (
        <Card>
            <button type="button" onClick={() => setOpen(!open)} className="flex w-full items-center gap-3 p-4 text-left">
                {open ? <ChevronDown className="h-4 w-4 shrink-0" /> : <ChevronRight className="h-4 w-4 shrink-0" />}

                <div className="min-w-0 flex-1">
                    <span className="font-medium">{section.label}</span>
                    <p className="text-muted-foreground truncate text-sm">{section.description}</p>
                </div>

                <Badge variant={stale ? 'destructive' : 'secondary'}>{section.count}</Badge>
            </button>

            {open && (
                <CardContent className="border-t pt-4">
                    {section.count === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {stale ? 'None right now — the CoreX feed is unreachable.' : 'No URLs in this section.'}
                        </p>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-muted-foreground text-left text-xs uppercase">
                                            <th className="pb-2 font-medium">URL</th>
                                            <th className="pb-2 pl-4 font-medium">Last modified</th>
                                            <th className="pb-2 pl-4 font-medium">Frequency</th>
                                            <th className="pb-2 pl-4 font-medium">Priority</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {section.urls.map((url) => (
                                            <tr key={url.loc} className="border-t">
                                                <td className="max-w-md truncate py-2">
                                                    <a
                                                        href={url.loc}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="hover:text-primary inline-flex items-center gap-1"
                                                    >
                                                        <span className="truncate">{url.loc}</span>
                                                        <ExternalLink className="h-3 w-3 shrink-0" />
                                                    </a>
                                                </td>
                                                <td className="py-2 pl-4 whitespace-nowrap">{formatDate(url.lastmod)}</td>
                                                <td className="py-2 pl-4 whitespace-nowrap">{url.changefreq ?? '—'}</td>
                                                <td className="py-2 pl-4 whitespace-nowrap">{url.priority ?? '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {hidden > 0 && (
                                <p className="text-muted-foreground mt-3 text-xs">
                                    Showing the first {previewLimit} of {section.count}. The full set is in the sitemap file.
                                </p>
                            )}
                        </>
                    )}
                </CardContent>
            )}
        </Card>
    );
}

export default function Sitemap({
    sitemapUrl,
    robotsUrl,
    robotsReferencesSitemap,
    searchConsoleVerified,
    corexAvailable,
    sections,
    total,
    previewLimit,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sitemap" />

            <div className="flex flex-col gap-6 p-4">
                <FlashMessages />

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Sitemap</h1>
                        <p className="text-muted-foreground text-sm">
                            The list of pages Google is told to crawl. It is generated live, so new properties, agents and articles appear
                            automatically.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <CopyButton value={sitemapUrl} />
                        <Button asChild size="sm">
                            <a href={sitemapUrl} target="_blank" rel="noopener noreferrer">
                                <ExternalLink className="h-4 w-4" />
                                View sitemap.xml
                            </a>
                        </Button>
                    </div>
                </div>

                {!corexAvailable && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>CoreX is unreachable</AlertTitle>
                        <AlertDescription>
                            Property, agent, branch and article URLs are left out of the sitemap until the feed responds again. Google keeps the pages
                            it already knows about, so this is safe to ride out — but do not submit the sitemap while it is short.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">URLs submitted</CardTitle>
                            <Map className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">{total}</div>
                            <p className="text-muted-foreground text-xs break-all">{sitemapUrl}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">robots.txt</CardTitle>
                            {robotsReferencesSitemap ? (
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                            ) : (
                                <AlertTriangle className="h-4 w-4 text-amber-600" />
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm font-medium">{robotsReferencesSitemap ? 'Points at the sitemap' : 'Missing a Sitemap line'}</div>
                            <p className="text-muted-foreground text-xs">
                                {robotsReferencesSitemap ? (
                                    <a href={robotsUrl} target="_blank" rel="noopener noreferrer" className="hover:text-primary underline">
                                        View robots.txt
                                    </a>
                                ) : (
                                    <>
                                        Add <span className="font-mono">Sitemap: {sitemapUrl}</span> under{' '}
                                        <Link href="/admin/marketing" className="hover:text-primary underline">
                                            Marketing &amp; Analytics
                                        </Link>
                                        .
                                    </>
                                )}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Search Console</CardTitle>
                            {searchConsoleVerified ? (
                                <CheckCircle2 className="h-4 w-4 text-green-600" />
                            ) : (
                                <AlertTriangle className="h-4 w-4 text-amber-600" />
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm font-medium">{searchConsoleVerified ? 'Verification tag set' : 'No verification tag'}</div>
                            <p className="text-muted-foreground text-xs">
                                {searchConsoleVerified ? (
                                    'The site is verified for Google Search Console.'
                                ) : (
                                    <Link href="/admin/marketing" className="hover:text-primary underline">
                                        Add the verification token
                                    </Link>
                                )}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="flex flex-col gap-3">
                    {sections.map((section) => (
                        <SectionCard key={section.key} section={section} previewLimit={previewLimit} corexAvailable={corexAvailable} />
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Submitting to Google</CardTitle>
                        <CardDescription>Only needed once — Google re-crawls the same URL from then on.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ol className="text-muted-foreground list-decimal space-y-2 pl-5 text-sm">
                            <li>
                                Open{' '}
                                <a
                                    href="https://search.google.com/search-console"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="hover:text-primary underline"
                                >
                                    Google Search Console
                                </a>{' '}
                                and select this website's property.
                            </li>
                            <li>
                                Go to <span className="text-foreground font-medium">Indexing → Sitemaps</span>.
                            </li>
                            <li>
                                Paste <span className="text-foreground font-mono">{sitemapUrl}</span> into "Add a new sitemap" and press Submit.
                            </li>
                            <li>Google reports the status within a day or two; "Success" means it read every URL above.</li>
                        </ol>

                        <p className="text-muted-foreground mt-4 flex items-start gap-2 text-sm">
                            <RefreshCw className="mt-0.5 h-4 w-4 shrink-0" />
                            <span>
                                There is nothing to re-submit after adding a property or page — the sitemap is rebuilt on every request, so Google
                                picks up changes on its next crawl.
                            </span>
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
