import FlashMessages from '@/components/admin/flash-messages';
import { TextField, TextareaField } from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, ExternalLink } from 'lucide-react';
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
    is_done: boolean;
}

export default function PropertySeoEdit({ property, seo }: { property: Property; seo: Seo }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin' },
        { title: 'Pages & SEO', href: '/admin/pages' },
        { title: property.title, href: `/admin/properties/${property.id}/edit` },
    ];

    const { data, setData, put, transform, processing, errors, recentlySuccessful } = useForm({
        meta_title: seo.meta_title ?? '',
        meta_description: seo.meta_description ?? '',
        canonical_url: seo.canonical_url ?? '',
        is_done: seo.is_done ?? false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/properties/${property.id}`, { preserveScroll: true });
    };

    // Saves the current fields and flips the "done" flag in one request. We both
    // setData (for the immediate UI) and transform (so this submit's payload
    // carries the new value, since setData only lands on the next render).
    const setDone = (done: boolean) => {
        setData('is_done', done);
        transform((current) => ({ ...current, is_done: done }));
        put(`/admin/properties/${property.id}`, {
            preserveScroll: true,
            onFinish: () => transform((current) => current),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`SEO · ${property.title}`} />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div className="min-w-0">
                        <Link href="/admin/pages" className="text-muted-foreground hover:text-foreground mb-1 inline-flex items-center gap-1 text-sm">
                            <ArrowLeft className="h-3.5 w-3.5" /> Back to pages
                        </Link>
                        <h1 className="truncate text-2xl font-semibold tracking-tight">{property.title}</h1>
                        <a
                            href={property.view_url}
                            target="_blank"
                            rel="noreferrer"
                            className="text-muted-foreground hover:text-foreground mt-1 inline-flex items-center gap-1 text-sm"
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
                                className="border-border aspect-[16/9] w-full rounded-md border object-cover sm:max-w-sm"
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
                            hint={
                                property.defaults.meta_description ? `Auto: ${property.defaults.meta_description}` : 'Aim for under 160 characters.'
                            }
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

                <div className="flex flex-wrap items-center gap-3">
                    <Button type="submit" disabled={processing}>
                        Save changes
                    </Button>
                    {data.is_done ? (
                        <>
                            <span className="inline-flex items-center gap-1.5 text-sm font-medium text-green-600">
                                <span className="inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-600 text-white">
                                    <Check className="h-3.5 w-3.5" />
                                </span>
                                Done
                            </span>
                            <Button type="button" variant="outline" disabled={processing} onClick={() => setDone(false)}>
                                Mark as not done
                            </Button>
                        </>
                    ) : (
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                            onClick={() => setDone(true)}
                            className="border-green-600 text-green-700 hover:bg-green-50 hover:text-green-700"
                        >
                            <Check className="h-4 w-4" />
                            Done
                        </Button>
                    )}
                    {recentlySuccessful && <span className="text-muted-foreground text-sm">Saved</span>}
                </div>
            </form>
        </AppLayout>
    );
}
