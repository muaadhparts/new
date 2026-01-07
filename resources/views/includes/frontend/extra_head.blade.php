@if (isset($page->meta_tag) && isset($page->meta_description))
    <meta name="keywords" content="{{ $page->meta_tag }}">
    <meta name="description" content="{{ $page->meta_description }}">
    <title>{{ $gs->title }}</title>
@elseif(isset($blog->meta_tag) && isset($blog->meta_description))
    <meta property="og:title" content="{{ $blog->title }}" />
    <meta property="og:description"
        content="{{ $blog->meta_description ?? strip_tags($blog->description ?? '') }}" />
    <meta property="og:image" content="{{ asset('assets/images/blogs/' . $blog->photo) }}" />
    <meta name="keywords" content="{{ $blog->meta_tag }}">
    <meta name="description" content="{{ $blog->meta_description }}">
    <title>{{ $gs->title }}</title>
@elseif(isset($catalogItem) && is_object($catalogItem) && !empty($catalogItem->name))
    <meta name="keywords" content="{{ $catalogItem->meta_tag ?? '' }}">
    <meta name="description"
        content="{{ $catalogItem->meta_description ?? strip_tags($catalogItem->description ?? '') }}">
    <meta property="og:title" content="{{ $catalogItem->name }}" />
    <meta property="og:description"
        content="{{ $catalogItem->meta_description ?? strip_tags($catalogItem->description ?? '') }}" />
    <meta property="og:image" content="{{ filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : ($catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png')) }}" />
    <meta name="author" content="Muaadh">
    <title>{{ substr($catalogItem->name, 0, 11) . '-' }}{{ $gs->title }}</title>
@else
    <meta property="og:title" content="{{ $gs->title }}" />
    <meta property="og:image" content="{{ asset('assets/images/' . $gs->logo) }}" />
    @if(isset($seo) && $seo)
        <meta name="keywords" content="{{ $seo->meta_keys }}">
    @endif
    <meta name="author" content="Muaadh">
    <title>{{ $gs->title }}</title>
@endif

@if ($default_font->font_value)
    <link
        href="https://fonts.googleapis.com/css?family={{ $default_font->font_value }}:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">
@else
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
@endif

{{-- Dynamic styles handled by theme-colors.css in layouts/front.blade.php --}}
{{-- Font styles --}}
@if ($default_font->font_family ?? false)
    <style>
        body, * { font-family: '{{ $default_font->font_family }}', sans-serif; }
    </style>
@endif

@if (!empty($seo->google_analytics))
    <script>
        "use strict";
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', '{{ $seo->google_analytics }}');
    </script>
@endif
@if (isset($seo) && isset($seo->facebook_pixel) && !empty($seo->facebook_pixel) && $seo->facebook_pixel != 'null' && $seo->facebook_pixel != null && trim($seo->facebook_pixel) != '' && strlen(trim($seo->facebook_pixel)) > 5)
    <script>
        "use strict";

        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $seo->facebook_pixel }}');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $seo->facebook_pixel }}&ev=PageView&noscript=1" />
    </noscript>
@endif
