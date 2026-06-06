import FlashMessages from '@/components/admin/flash-messages';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, FileText, LineChart, Users, XCircle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/admin' }];

interface Stats {
    pages: number;
    users: number;
    integrations: { ga4: boolean; gtm: boolean; search_console: boolean; google_ads: boolean };
    integrationsConfigured: number;
    integrationsTotal: number;
}

const integrationLabels: Record<keyof Stats['integrations'], string> = {
    ga4: 'Google Analytics 4',
    gtm: 'Google Tag Manager',
    search_console: 'Search Console',
    google_ads: 'Google Ads conversion',
};

export default function AdminDashboard({ stats }: { stats: Stats }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marketing Dashboard" />

            <div className="flex flex-col gap-6 p-4">
                <FlashMessages />

                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Marketing &amp; SEO</h1>
                    <p className="text-sm text-muted-foreground">Manage page SEO, Google analytics &amp; tags, and panel users.</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link href="/admin/pages">
                        <Card className="transition-colors hover:border-primary/50">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Managed Pages</CardTitle>
                                <FileText className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{stats.pages}</div>
                                <p className="text-xs text-muted-foreground">Edit slugs &amp; SEO meta</p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href="/admin/users">
                        <Card className="transition-colors hover:border-primary/50">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Panel Users</CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">{stats.users}</div>
                                <p className="text-xs text-muted-foreground">Accounts that can log in</p>
                            </CardContent>
                        </Card>
                    </Link>

                    <Link href="/admin/marketing">
                        <Card className="transition-colors hover:border-primary/50">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Google Integrations</CardTitle>
                                <LineChart className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">
                                    {stats.integrationsConfigured}
                                    <span className="text-base font-normal text-muted-foreground">/{stats.integrationsTotal}</span>
                                </div>
                                <p className="text-xs text-muted-foreground">Connected services</p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Google integration status</CardTitle>
                        <CardDescription>Configure these under Marketing &amp; Analytics.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 sm:grid-cols-2">
                        {(Object.keys(integrationLabels) as (keyof Stats['integrations'])[]).map((key) => (
                            <div key={key} className="flex items-center gap-2 text-sm">
                                {stats.integrations[key] ? (
                                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                                ) : (
                                    <XCircle className="h-4 w-4 text-muted-foreground" />
                                )}
                                <span className={stats.integrations[key] ? '' : 'text-muted-foreground'}>{integrationLabels[key]}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
