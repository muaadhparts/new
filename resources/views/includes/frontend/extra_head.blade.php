{{-- ============================================== --}}
{{-- SEO HEAD SECTION - Optimized for Search Engines --}}
{{-- ============================================== --}}

{{-- Search Engine Verification Meta Tags --}}
@if (!empty($seo->search_console_verification))
    <meta name="google-site-verification" content="{{ $seo->search_console_verification }}">
@endif
@if (!empty($seo->bing_verification))
    <meta name="msvalidate.01" content="{{ $seo->bing_verification }}">
@endif

{{-- Global Schemas (Organization & WebSite) --}}
@include('includes.seo.global-schemas')

{{-- Page-specific Meta Tags --}}
@if (isset($page->meta_tag) && isset($page->meta_description))
    <meta name="keywords" content="{{ $page->meta_tag }}">
    <meta name="description" content="{{ $page->meta_description }}">
    <name>{{ $gs->site_name }}</name>
@elseif(isset($blog->meta_tag) && isset($blog->meta_description))
    <meta property="og:name" content="{{ $blog->name }}" />
    <meta property="og:description" content="{{ $blog->meta_description ?? strip_tags($blog->description ?? '') }}" />
    <meta property="og:image" content="{{ asset('assets/images/blogs/' . $blog->photo) }}" />
    <meta name="keywords" content="{{ $blog->meta_tag }}">
    <meta name="description" content="{{ $blog->meta_description }}">
    <name>{{ $gs->site_name }}</name>
@elseif(isset($catalogItem) && is_object($catalogItem) && !empty($catalogItem->name))
    <meta name="keywords" content="{{ $catalogItem->meta_tag ?? '' }}">
    <meta name="description" content="{{ $catalogItem->meta_description ?? strip_tags($catalogItem->description ?? '') }}">
    <meta property="og:name" content="{{ $catalogItem->name }}" />
    <meta property="og:description" content="{{ $catalogItem->meta_description ?? strip_tags($catalogItem->description ?? '') }}" />
    <meta property="og:image" content="{{ filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png')) }}" />
    <meta name="author" content="Muaadh">
    <name>{{ substr($catalogItem->name, 0, 11) . '-' }}{{ $gs->site_name }}</name>
@else
    <meta property="og:name" content="{{ $gs->site_name }}" />
    <meta property="og:image" content="{{ asset('assets/images/' . $gs->logo) }}" />
    @if(isset($seo) && $seo && !empty($seo->meta_keys))
        <meta name="keywords" content="{{ $seo->meta_keys }}">
    @endif
    <meta name="author" content="Muaadh">
    <name>{{ $gs->site_name }}</name>
@endif

{{-- Typeface Loading --}}
@if ($default_typeface->font_value ?? false)
    <link href="https://fonts.googleapis.com/css?family={{ $default_typeface->font_value }}:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
@else
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@endif

{{-- Typeface styles --}}
@if ($default_typeface->font_family ?? false)
    <style>
        body, * { font-family: '{{ $default_typeface->font_family }}', sans-serif; }
    </style>
@endif

{{-- ============================================== --}}
{{-- TRACKING & ANALYTICS SECTION --}}
{{-- ============================================== --}}

{{-- Google Consent Mode v2 - MUST come before GTM --}}
@if (!empty($seo->gtm_id))
@consentMode
@endif

{{-- Google Tag Manager - Primary tracking solution --}}
@if (!empty($seo->gtm_id))
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $seo->gtm_id }}');
    </script>
@endif

{{-- Google Analytics (legacy - only if GTM not configured) --}}
@if (!empty($seo->google_analytics) && empty($seo->gtm_id))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo->google_analytics }}"></script>
    <script>
        "use strict";
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $seo->google_analytics }}');
    </script>
@endif

{{-- Facebook Pixel --}}
@if (isset($seo) && !empty($seo->facebook_pixel) && $seo->facebook_pixel != 'null' && strlen(trim($seo->facebook_pixel)) > 5)
    <script>
        "use strict";
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $seo->facebook_pixel }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $seo->facebook_pixel }}&ev=PageView&noscript=1" /></noscript>
@endif

{{-- Core Web Vitals Monitoring (sends to GA4 via GTM) --}}
@if (!empty($seo->gtm_id))
<script>
// Web Vitals - Sends CWV metrics to GTM dataLayer
(function(){
    // Simplified CWV tracking
    if ('PerformanceObserver' in window) {
        // LCP - Largest Contentful Paint
        try {
            new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                const lastEntry = entries[entries.length - 1];
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    event: 'web_vitals',
                    metric_name: 'LCP',
                    metric_value: Math.round(lastEntry.startTime),
                    metric_rating: lastEntry.startTime < 2500 ? 'good' : lastEntry.startTime < 4000 ? 'needs-improvement' : 'poor'
                });
            }).observe({type: 'largest-contentful-paint', buffered: true});
        } catch(e) {}

        // FID - First Input Delay
        try {
            new PerformanceObserver((entryList) => {
                const entries = entryList.getEntries();
                entries.forEach(entry => {
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({
                        event: 'web_vitals',
                        metric_name: 'FID',
                        metric_value: Math.round(entry.processingStart - entry.startTime),
                        metric_rating: (entry.processingStart - entry.startTime) < 100 ? 'good' : (entry.processingStart - entry.startTime) < 300 ? 'needs-improvement' : 'poor'
                    });
                });
            }).observe({type: 'first-input', buffered: true});
        } catch(e) {}

        // CLS - Cumulative Layout Shift
        try {
            let clsValue = 0;
            new PerformanceObserver((entryList) => {
                for (const entry of entryList.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                }
            }).observe({type: 'layout-shift', buffered: true});

            // Report CLS on page hide
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({
                        event: 'web_vitals',
                        metric_name: 'CLS',
                        metric_value: Math.round(clsValue * 1000) / 1000,
                        metric_rating: clsValue < 0.1 ? 'good' : clsValue < 0.25 ? 'needs-improvement' : 'poor'
                    });
                }
            });
        } catch(e) {}
    }
})();
</script>
@endif
