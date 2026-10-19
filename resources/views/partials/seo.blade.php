@php
    /** @var \App\Models\Page|null $seoPage */
    /** @var \App\Models\SiteSetting|null $siteSettings */

    $site = $siteSettings ?? \App\Models\SiteSetting::current();

    $metaTitle = \App\Support\DynamicSeo::titleFor($seoPage);
    $metaDescription = $seoPage?->meta_description ?: $site->default_meta_description;
    $metaKeywords = $seoPage?->meta_keywords;
    $canonical = \App\Support\DynamicSeo::canonicalUrl($seoPage, request());
    $robots = $seoPage?->robotsDirective() ?? 'index, follow';

    $ogTitle = $seoPage?->og_title ?: $metaTitle;
    $ogDescription = $seoPage?->og_description ?: $metaDescription;
    $ogImage = $seoPage?->og_image ?: $site->default_og_image;
    $ogImageAlt = $seoPage?->og_image_alt ?: ($seoPage?->name ?: $ogTitle);
    $ogImageSize = \App\Support\DynamicSeo::imageSize($ogImage);
    $ogType = $seoPage?->og_type ?: 'website';
    $ogSiteName = trim((string) $site->site_name) ?: config('app.name');

    $twitterCard = $seoPage?->twitter_card ?: 'summary_large_image';
    $twitterTitle = $seoPage?->twitter_title ?: $ogTitle;
    $twitterDescription = $seoPage?->twitter_description ?: $ogDescription;
    $twitterImage = $seoPage?->twitter_image ?: $ogImage;
    $twitterHandle = $site->default_twitter_handle;

    $injectAnalytics = $injectAnalytics ?? false;

    // Only a bare meta content code is valid here; an HTML-file token pasted
    // into the field ("google-site-verification: google….html") is verified
    // by the file in /public instead, so never echo it as a meta tag.
    $searchConsoleToken = trim((string) $site->google_search_console_verification);
    $searchConsoleToken = str_contains($searchConsoleToken, '.html') || str_contains(strtolower($searchConsoleToken), 'google-site-verification:') ? '' : $searchConsoleToken;

    // Schema.org JSON-LD (Organization + any page nodes) on public pages only.
    $structuredDataJson = $injectAnalytics ? \App\Support\StructuredData::render($structuredData ?? []) : null;
    $ga4 = trim((string) $site->ga4_measurement_id);
    $gtm = trim((string) $site->gtm_container_id);
    $adsId = trim((string) $site->google_ads_conversion_id);
    $loadGtag = $injectAnalytics && ($ga4 !== '' || $adsId !== '');
@endphp

{{-- Google Search Console site verification --}}
@if($injectAnalytics && $searchConsoleToken !== '')
    <meta name="google-site-verification" content="{{ $searchConsoleToken }}">
@endif

{{-- Core SEO meta --}}
@if($metaDescription)
    <meta name="description" content="{{ $metaDescription }}">
@endif
@if($metaKeywords)
    <meta name="keywords" content="{{ $metaKeywords }}">
@endif
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph --}}
<meta property="og:title" content="{{ $ogTitle }}">
@if($ogDescription)
    <meta property="og:description" content="{{ $ogDescription }}">
@endif
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonical }}">
@if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    @if($ogImageSize)
        <meta property="og:image:width" content="{{ $ogImageSize['width'] }}">
        <meta property="og:image:height" content="{{ $ogImageSize['height'] }}">
    @endif
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
@endif
<meta property="og:site_name" content="{{ $ogSiteName }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:title" content="{{ $twitterTitle }}">
@if($twitterDescription)
    <meta name="twitter:description" content="{{ $twitterDescription }}">
@endif
@if($twitterImage)
    <meta name="twitter:image" content="{{ $twitterImage }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
@endif
@if($twitterHandle)
    <meta name="twitter:site" content="{{ $twitterHandle }}">
@endif

{{-- Structured data (JSON-LD): generated, then any admin-entered block --}}
@if($structuredDataJson)
    <script type="application/ld+json">{!! $structuredDataJson !!}</script>
@endif
@if($seoPage && trim((string) $seoPage->json_ld) !== '')
    <script type="application/ld+json">{!! $seoPage->json_ld !!}</script>
@endif

@if($loadGtag)
    {{-- Google tag (gtag.js) --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 !== '' ? $ga4 : $adsId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @if($ga4 !== '')
            gtag('config', '{{ $ga4 }}');
        @endif
        @if($adsId !== '')
            gtag('config', '{{ $adsId }}');
        @endif
    </script>
@endif

@if($injectAnalytics && $gtm !== '')
    {{-- Google Tag Manager --}}
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $gtm }}');</script>
@endif

{{-- Site-wide custom head scripts --}}
@if($injectAnalytics && trim((string) $site->head_scripts) !== '')
    {!! $site->head_scripts !!}
@endif

{{-- Per-page custom head scripts --}}
@if($seoPage && trim((string) $seoPage->head_scripts) !== '')
    {!! $seoPage->head_scripts !!}
@endif
