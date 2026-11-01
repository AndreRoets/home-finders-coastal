import FlashMessages from '@/components/admin/flash-messages';
import { FieldGroup, ImageField, TextField, TextareaField } from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin' },
    { title: 'Marketing & Analytics', href: '/admin/marketing' },
];

interface Settings {
    site_name: string | null;
    default_meta_description: string | null;
    default_og_image: string | null;
    default_twitter_handle: string | null;
    ga4_measurement_id: string | null;
    gtm_container_id: string | null;
    google_search_console_verification: string | null;
    google_ads_conversion_id: string | null;
    google_ads_conversion_label: string | null;
    head_scripts: string | null;
    body_scripts: string | null;
    robots_txt: string | null;
    attribution_window_days: number | null;
    channel_search_sources: string | null;
    channel_social_sources: string | null;
    channel_paid_mediums: string | null;
    channel_email_mediums: string | null;
    channel_social_mediums: string | null;
}

/** The values the enquiry channel rules fall back to while a setting is blank. */
interface AttributionDefaults {
    attribution_window_days: number;
    channel_search_sources: string;
    channel_social_sources: string;
    channel_paid_mediums: string;
    channel_email_mediums: string;
    channel_social_mediums: string;
}

export default function Marketing({ settings, attributionDefaults }: { settings: Settings; attributionDefaults: AttributionDefaults }) {
    const { data, setData, put, processing, errors, recentlySuccessful } = useForm({
        site_name: settings.site_name ?? '',
        default_meta_description: settings.default_meta_description ?? '',
        default_og_image: settings.default_og_image ?? '',
        default_twitter_handle: settings.default_twitter_handle ?? '',
        ga4_measurement_id: settings.ga4_measurement_id ?? '',
        gtm_container_id: settings.gtm_container_id ?? '',
        google_search_console_verification: settings.google_search_console_verification ?? '',
        google_ads_conversion_id: settings.google_ads_conversion_id ?? '',
        google_ads_conversion_label: settings.google_ads_conversion_label ?? '',
        head_scripts: settings.head_scripts ?? '',
        body_scripts: settings.body_scripts ?? '',
        robots_txt: settings.robots_txt ?? '',
        // Pre-filled with what is in effect, so editing starts from the defaults.
        attribution_window_days: String(settings.attribution_window_days ?? attributionDefaults.attribution_window_days),
        channel_search_sources: settings.channel_search_sources ?? attributionDefaults.channel_search_sources,
        channel_social_sources: settings.channel_social_sources ?? attributionDefaults.channel_social_sources,
        channel_paid_mediums: settings.channel_paid_mediums ?? attributionDefaults.channel_paid_mediums,
        channel_email_mediums: settings.channel_email_mediums ?? attributionDefaults.channel_email_mediums,
        channel_social_mediums: settings.channel_social_mediums ?? attributionDefaults.channel_social_mediums,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put('/admin/marketing', { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marketing & Analytics" />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Marketing &amp; Analytics</h1>
                        <p className="text-muted-foreground text-sm">Site-wide Google integrations applied to every public page.</p>
                    </div>
                    <Button type="submit" disabled={processing}>
                        Save
                    </Button>
                </div>

                <FlashMessages />

                <Card>
                    <CardHeader>
                        <CardTitle>Google Analytics &amp; Tags</CardTitle>
                        <CardDescription>IDs are injected into the site head automatically.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextField
                            id="ga4_measurement_id"
                            label="GA4 Measurement ID"
                            value={data.ga4_measurement_id}
                            onChange={(v) => setData('ga4_measurement_id', v)}
                            error={errors.ga4_measurement_id}
                            placeholder="G-XXXXXXXXXX"
                        />
                        <TextField
                            id="gtm_container_id"
                            label="Google Tag Manager Container ID"
                            value={data.gtm_container_id}
                            onChange={(v) => setData('gtm_container_id', v)}
                            error={errors.gtm_container_id}
                            placeholder="GTM-XXXXXXX"
                        />
                        <TextField
                            id="google_search_console_verification"
                            label="Search Console verification token"
                            value={data.google_search_console_verification}
                            onChange={(v) => setData('google_search_console_verification', v)}
                            error={errors.google_search_console_verification}
                            placeholder="AbC123_xyz"
                            hint='Only the content="…" code from the HTML-tag method. Leave empty if you verified with an HTML file or DNS.'
                        />
                        <FieldGroup columns={2}>
                            <TextField
                                id="google_ads_conversion_id"
                                label="Google Ads Conversion ID"
                                value={data.google_ads_conversion_id}
                                onChange={(v) => setData('google_ads_conversion_id', v)}
                                error={errors.google_ads_conversion_id}
                                placeholder="AW-XXXXXXXXX"
                            />
                            <TextField
                                id="google_ads_conversion_label"
                                label="Conversion label"
                                value={data.google_ads_conversion_label}
                                onChange={(v) => setData('google_ads_conversion_label', v)}
                                error={errors.google_ads_conversion_label}
                            />
                        </FieldGroup>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Site defaults</CardTitle>
                        <CardDescription>Fallback values used when a page has no specific override.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextField
                            id="site_name"
                            label="Site name"
                            value={data.site_name}
                            onChange={(v) => setData('site_name', v)}
                            error={errors.site_name}
                        />
                        <TextareaField
                            id="default_meta_description"
                            label="Default meta description"
                            value={data.default_meta_description}
                            onChange={(v) => setData('default_meta_description', v)}
                            error={errors.default_meta_description}
                        />
                        <ImageField
                            id="default_og_image"
                            label="Default share image"
                            value={data.default_og_image}
                            onChange={(v) => setData('default_og_image', v)}
                            error={errors.default_og_image}
                            hint="Used for link previews on any page without its own share image. Use a 1200 × 630px image."
                        />
                        <TextField
                            id="default_twitter_handle"
                            label="Twitter handle"
                            value={data.default_twitter_handle}
                            onChange={(v) => setData('default_twitter_handle', v)}
                            error={errors.default_twitter_handle}
                            placeholder="@homefinders"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Custom scripts</CardTitle>
                        <CardDescription>Raw snippets injected on every public page.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextareaField
                            id="head_scripts"
                            label="Head scripts"
                            value={data.head_scripts}
                            onChange={(v) => setData('head_scripts', v)}
                            error={errors.head_scripts}
                            rows={5}
                            mono
                            placeholder="<!-- e.g. Meta Pixel, Hotjar, custom verification -->"
                        />
                        <TextareaField
                            id="body_scripts"
                            label="Body scripts"
                            value={data.body_scripts}
                            onChange={(v) => setData('body_scripts', v)}
                            error={errors.body_scripts}
                            rows={5}
                            mono
                            placeholder="<!-- Injected at the top of <body> -->"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Enquiry attribution</CardTitle>
                        <CardDescription>
                            How each enquiry is credited to a channel on the Enquiries screen. One entry per line. Changes apply to new enquiries only
                            — existing ones keep the channel they were saved with.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <TextField
                            id="attribution_window_days"
                            label="Remember a visitor's first visit for (days)"
                            type="number"
                            value={data.attribution_window_days}
                            onChange={(v) => setData('attribution_window_days', v)}
                            error={errors.attribution_window_days}
                            hint="A visitor who lands from an ad and enquires within this many days is credited to that ad. After that, their next visit starts afresh."
                        />
                        <FieldGroup columns={2}>
                            <TextareaField
                                id="channel_search_sources"
                                label="Search engines"
                                value={data.channel_search_sources}
                                onChange={(v) => setData('channel_search_sources', v)}
                                error={errors.channel_search_sources}
                                rows={6}
                                mono
                                hint="Matched against utm_source and the referrer's domain → Organic Search."
                            />
                            <TextareaField
                                id="channel_social_sources"
                                label="Social networks"
                                value={data.channel_social_sources}
                                onChange={(v) => setData('channel_social_sources', v)}
                                error={errors.channel_social_sources}
                                rows={6}
                                mono
                                hint="Matched against utm_source and the referrer's domain → Organic Social (Paid Social with a paid medium)."
                            />
                            <TextareaField
                                id="channel_paid_mediums"
                                label="Paid mediums"
                                value={data.channel_paid_mediums}
                                onChange={(v) => setData('channel_paid_mediums', v)}
                                error={errors.channel_paid_mediums}
                                rows={6}
                                mono
                                hint="utm_medium values for bought media → Paid Search or Paid Social. * is a wildcard."
                            />
                            <TextareaField
                                id="channel_social_mediums"
                                label="Social mediums"
                                value={data.channel_social_mediums}
                                onChange={(v) => setData('channel_social_mediums', v)}
                                error={errors.channel_social_mediums}
                                rows={6}
                                mono
                                hint="utm_medium values meaning social → Organic Social. * is a wildcard."
                            />
                            <TextareaField
                                id="channel_email_mediums"
                                label="Email mediums"
                                value={data.channel_email_mediums}
                                onChange={(v) => setData('channel_email_mediums', v)}
                                error={errors.channel_email_mediums}
                                rows={4}
                                mono
                                hint="utm_medium or utm_source values → Email."
                            />
                        </FieldGroup>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>robots.txt</CardTitle>
                        <CardDescription>Served at /robots.txt. Leave blank for the sensible default.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <TextareaField
                            id="robots_txt"
                            label="robots.txt content"
                            value={data.robots_txt}
                            onChange={(v) => setData('robots_txt', v)}
                            error={errors.robots_txt}
                            rows={6}
                            mono
                            placeholder={'User-agent: *\nAllow: /\n\nSitemap: https://…/sitemap.xml'}
                        />
                    </CardContent>
                </Card>

                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={processing}>
                        Save
                    </Button>
                    {recentlySuccessful && <span className="text-muted-foreground text-sm">Saved</span>}
                </div>
            </form>
        </AppLayout>
    );
}
