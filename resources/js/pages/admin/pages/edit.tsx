import FlashMessages from '@/components/admin/flash-messages';
import { CheckboxField, FieldGroup, ImageField, SelectField, TextField, TextareaField } from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PageRecord } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { FormEventHandler } from 'react';

interface Redirect {
    id: number;
    old_slug: string;
    status_code: number;
}

const priorityOptions = ['0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0'].map((v) => ({ value: v, label: v }));
const frequencyOptions = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'].map((v) => ({
    value: v,
    label: v.charAt(0).toUpperCase() + v.slice(1),
}));
const ogTypeOptions = ['website', 'article', 'profile', 'business.business'].map((v) => ({ value: v, label: v }));
const twitterCardOptions = [
    { value: 'summary', label: 'Summary' },
    { value: 'summary_large_image', label: 'Summary large image' },
];

export default function PageEdit({ page, redirects, defaultOgImage }: { page: PageRecord; redirects: Redirect[]; defaultOgImage: string | null }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Pages & SEO', href: '/admin/pages' },
        { title: page.name, href: `/admin/pages/${page.id}/edit` },
    ];

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        name: page.name,
        slug: page.slug,
        is_active: page.is_active,
        meta_title: page.meta_title ?? '',
        meta_description: page.meta_description ?? '',
        meta_keywords: page.meta_keywords ?? '',
        canonical_url: page.canonical_url ?? '',
        robots_index: page.robots_index,
        robots_follow: page.robots_follow,
        og_title: page.og_title ?? '',
        og_description: page.og_description ?? '',
        og_image: page.og_image ?? '',
        og_type: page.og_type ?? 'website',
        twitter_card: page.twitter_card ?? 'summary_large_image',
        twitter_title: page.twitter_title ?? '',
        twitter_description: page.twitter_description ?? '',
        twitter_image: page.twitter_image ?? '',
        json_ld: page.json_ld ?? '',
        head_scripts: page.head_scripts ?? '',
        sitemap_priority: page.sitemap_priority,
        sitemap_frequency: page.sitemap_frequency,
    });

    const isHome = page.slug === '/';

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/pages/${page.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit · ${page.name}`} />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <Link href="/admin/pages" className="text-muted-foreground hover:text-foreground mb-1 inline-flex items-center gap-1 text-sm">
                            <ArrowLeft className="h-3.5 w-3.5" /> Back to pages
                        </Link>
                        <h1 className="text-2xl font-semibold tracking-tight">{page.name}</h1>
                    </div>
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>
                </div>

                <FlashMessages />

                <Card>
                    <CardHeader>
                        <CardTitle>Page &amp; URL</CardTitle>
                        <CardDescription>The slug controls this page's public URL.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextField
                            id="name"
                            label="Internal name"
                            value={data.name}
                            onChange={(v) => setData('name', v)}
                            error={errors.name}
                            required
                        />
                        <TextField
                            id="slug"
                            label="URL slug"
                            value={data.slug}
                            onChange={(v) => setData('slug', v)}
                            error={errors.slug}
                            required
                            prefix={isHome ? undefined : '/'}
                            hint={
                                isHome
                                    ? 'This is the homepage. Keep the slug as "/".'
                                    : 'Changing this updates the page URL. The old URL will 301-redirect here automatically.'
                            }
                        />
                        <CheckboxField
                            id="is_active"
                            label="Page active"
                            checked={data.is_active}
                            onChange={(v) => setData('is_active', v)}
                            hint="Inactive pages are excluded from the XML sitemap."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Core SEO</CardTitle>
                        <CardDescription>What Google shows in search results.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextField
                            id="meta_title"
                            label="Meta title"
                            value={data.meta_title}
                            onChange={(v) => setData('meta_title', v)}
                            error={errors.meta_title}
                            hint="Aim for under 60 characters."
                        />
                        <TextareaField
                            id="meta_description"
                            label="Meta description"
                            value={data.meta_description}
                            onChange={(v) => setData('meta_description', v)}
                            error={errors.meta_description}
                            hint="Aim for under 160 characters."
                        />
                        <TextField
                            id="meta_keywords"
                            label="Meta keywords"
                            value={data.meta_keywords}
                            onChange={(v) => setData('meta_keywords', v)}
                            error={errors.meta_keywords}
                            hint="Comma-separated. Optional — most engines ignore these."
                        />
                        <TextField
                            id="canonical_url"
                            label="Canonical URL"
                            value={data.canonical_url}
                            onChange={(v) => setData('canonical_url', v)}
                            error={errors.canonical_url}
                            placeholder="Leave blank to use this page's own URL"
                        />
                        <FieldGroup columns={2}>
                            <CheckboxField
                                id="robots_index"
                                label="Allow indexing"
                                checked={data.robots_index}
                                onChange={(v) => setData('robots_index', v)}
                                hint="index vs noindex"
                            />
                            <CheckboxField
                                id="robots_follow"
                                label="Follow links"
                                checked={data.robots_follow}
                                onChange={(v) => setData('robots_follow', v)}
                                hint="follow vs nofollow"
                            />
                        </FieldGroup>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Social sharing</CardTitle>
                        <CardDescription>
                            The image, title and description shown when this page's URL is shared on Facebook, WhatsApp, LinkedIn or X.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <ImageField
                            id="og_image"
                            label="Share image"
                            value={data.og_image}
                            onChange={(v) => setData('og_image', v)}
                            error={errors.og_image}
                            fallback={defaultOgImage}
                            hint="Shown when this URL is shared. Use a 1200 × 630px image. Leave blank to fall back to the site-wide default from Marketing & Analytics."
                        />
                        <TextField
                            id="og_title"
                            label="OG title"
                            value={data.og_title}
                            onChange={(v) => setData('og_title', v)}
                            error={errors.og_title}
                        />
                        <TextareaField
                            id="og_description"
                            label="OG description"
                            value={data.og_description}
                            onChange={(v) => setData('og_description', v)}
                            error={errors.og_description}
                        />
                        <SelectField
                            id="og_type"
                            label="OG type"
                            value={data.og_type}
                            onChange={(v) => setData('og_type', v)}
                            options={ogTypeOptions}
                        />
                        <ImageField
                            id="twitter_image"
                            label="Twitter/X image"
                            value={data.twitter_image}
                            onChange={(v) => setData('twitter_image', v)}
                            error={errors.twitter_image}
                            fallback={data.og_image || defaultOgImage}
                            hint="Leave blank to reuse the share image above."
                        />
                        <SelectField
                            id="twitter_card"
                            label="Twitter card type"
                            value={data.twitter_card}
                            onChange={(v) => setData('twitter_card', v)}
                            options={twitterCardOptions}
                        />
                        <TextField
                            id="twitter_title"
                            label="Twitter title"
                            value={data.twitter_title}
                            onChange={(v) => setData('twitter_title', v)}
                            error={errors.twitter_title}
                        />
                        <TextareaField
                            id="twitter_description"
                            label="Twitter description"
                            value={data.twitter_description}
                            onChange={(v) => setData('twitter_description', v)}
                            error={errors.twitter_description}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Structured data &amp; scripts</CardTitle>
                        <CardDescription>JSON-LD schema and any per-page tracking snippets.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextareaField
                            id="json_ld"
                            label="JSON-LD structured data"
                            value={data.json_ld}
                            onChange={(v) => setData('json_ld', v)}
                            error={errors.json_ld}
                            rows={8}
                            mono
                            placeholder='{ "@context": "https://schema.org", "@type": "RealEstateAgent", "name": "…" }'
                        />
                        <TextareaField
                            id="head_scripts"
                            label="Custom head scripts (this page only)"
                            value={data.head_scripts}
                            onChange={(v) => setData('head_scripts', v)}
                            error={errors.head_scripts}
                            rows={5}
                            mono
                            placeholder="<script>…</script>"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Sitemap</CardTitle>
                        <CardDescription>Hints for how crawlers should treat this URL.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <FieldGroup columns={2}>
                            <SelectField
                                id="sitemap_priority"
                                label="Priority"
                                value={data.sitemap_priority}
                                onChange={(v) => setData('sitemap_priority', v)}
                                options={priorityOptions}
                            />
                            <SelectField
                                id="sitemap_frequency"
                                label="Change frequency"
                                value={data.sitemap_frequency}
                                onChange={(v) => setData('sitemap_frequency', v)}
                                options={frequencyOptions}
                            />
                        </FieldGroup>
                    </CardContent>
                </Card>

                {redirects.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Redirects pointing here</CardTitle>
                            <CardDescription>Previous slugs that now redirect to this page.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-2">
                            {redirects.map((redirect) => (
                                <div key={redirect.id} className="flex items-center gap-2 text-sm">
                                    <code className="bg-muted rounded px-1.5 py-0.5 text-xs">/{redirect.old_slug}</code>
                                    <span className="text-muted-foreground">→ {redirect.status_code}</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>
                    {recentlySuccessful && <span className="text-muted-foreground text-sm">Saved</span>}
                </div>
            </form>
        </AppLayout>
    );
}
