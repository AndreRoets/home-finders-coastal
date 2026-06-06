@php
    $site = $siteSettings ?? \App\Models\SiteSetting::current();
    $injectAnalytics = $injectAnalytics ?? false;
    $gtm = trim((string) $site->gtm_container_id);
@endphp

@if($injectAnalytics && $gtm !== '')
    {{-- Google Tag Manager (noscript) --}}
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif

@if($injectAnalytics && trim((string) $site->body_scripts) !== '')
    {!! $site->body_scripts !!}
@endif
