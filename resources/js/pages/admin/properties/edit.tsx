import FlashMessages from '@/components/admin/flash-messages';
import { TextField, TextareaField } from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ExternalLink } from 'lucide-react';
import { FormEventHandler } from 'react';

interface Property {
    id: string;
    title: string;
    slug: string;
    image: string | null;
    view_url: string;
    defaults: {
        meta_title: string;
        meta_description: string;
        canonical_url: string;
    };
}

interface Seo {
    meta_title: string;
    meta_description: string;
    canonical_url: string;
}

export default function PropertySeoEdit({ property, seo }: { property: Property; seo: Seo }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Pages & SEO', href: '/admin/pages' },
        { title: property.title, href: `/admin/properties/${property.id}/edit` },
    ];

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        meta_title: seo.meta_title ?? '',
        meta_description: seo.meta_description ?? '',
        canonical_url: seo.canonical_url ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/properties/${property.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`SEO · ${property.title}`} />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div className="min-w-0">
                        <Link href="/admin/pages" className="mb-1 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                            <ArrowLeft className="h-3.5 w-3.5" /> Back to pages
                        </Link>
                        <h1 className="truncate text-2xl font-semibold tracking-tight">{property.title}</h1>
                        <a
                            href={property.view_url}
                            target="_blank"
                            rel="noreferrer"
                            className="mt-1 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ExternalLink className="h-3.5 w-3.5" /> View live property
                        </a>
                    </div>
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>
                </div>

                <FlashMessages />

                <Card>
                    <CardHeader>
                        <CardTitle>Property SEO</CardTitle>
                        <CardDescription>
                            This is a live, syndicated listing. Leave a field blank to keep the auto-generated value shown beneath it.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        {property.image && (
                            <img
                                src={property.image}
                                alt={property.title}
                                loading="lazy"
                                className="aspect-[16/9] w-full rounded-md border border-border object-cover sm:max-w-sm"
                            />
                        )}
                        <TextField
                            id="meta_title"
                            label="Meta title"
                            value={data.meta_title}
                            onChange={(v) => setData('meta_title', v)}
                            error={errors.meta_title}
                            placeholder={property.defaults.meta_title}
                            hint={property.defaults.meta_title ? `Auto: ${property.defaults.meta_title}` : 'Aim for under 60 characters.'}
                        />
                        <TextareaField
                            id="meta_description"
                            label="Meta description"
                            value={data.meta_description}
                            onChange={(v) => setData('meta_description', v)}
                            error={errors.meta_description}
                            rows={4}
                            placeholder={property.defaults.meta_description}
                            hint={property.defaults.meta_description ? `Auto: ${property.defaults.meta_description}` : 'Aim for under 160 characters.'}
                        />
                        <TextField
                            id="canonical_url"
                            label="Canonical URL"
                            value={data.canonical_url}
                            onChange={(v) => setData('canonical_url', v)}
                            error={errors.canonical_url}
                            placeholder={property.defaults.canonical_url}
                            hint="The preferred URL for this listing. Leave blank to use its own address."
                        />
                    </CardContent>
                </Card>

                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>
                    {recentlySuccessful && <span className="text-sm text-muted-foreground">Saved</span>}
                </div>
            </form>
        </AppLayout>
    );
}
