@if (isset($page->meta_tag) && isset($page->meta_description))
    <meta name="keywords" content="{{ $page->meta_tag }}">
    <meta name="description" content="{{ $page->meta_description }}">
    <title>{{ $gs->title }}</title>
@elseif(isset($blog->meta_tag) && isset($blog->meta_description))
    <meta property="og:title" content="{{ $blog->title }}" />
    <meta property="og:description"
        content="{{ $blog->meta_description != null ? $blog->meta_description : strip_tags($blog->meta_description) }}" />
    <meta property="og:image" content="{{ asset('assets/images/blogs/' . $blog->photo) }}" />
    <meta name="keywords" content="{{ $blog->meta_tag }}">
    <meta name="description" content="{{ $blog->meta_description }}">
    <title>{{ $gs->title }}</title>
@elseif(isset($productt))
    <meta name="keywords" content="{{ !empty($productt->meta_tag) ? implode(',', $productt->meta_tag) : '' }}">
    <meta name="description"
        content="{{ $productt->meta_description != null ? $productt->meta_description : strip_tags($productt->description) }}">
    <meta property="og:title" content="{{ $productt->name }}" />
    <meta property="og:description"
        content="{{ $productt->meta_description != null ? $productt->meta_description : strip_tags($productt->description) }}" />
    <meta property="og:image" content="{{ asset('assets/images/thumbnails/' . $productt->thumbnail) }}" />
    <meta name="author" content="GeniusOcean">
    <title>{{ substr($productt->name, 0, 11) . '-' }}{{ $gs->title }}</title>
@else
    <meta property="og:title" content="{{ $gs->title }}" />
    <meta property="og:image" content="{{ asset('assets/images/' . $gs->logo) }}" />
    <meta name="keywords" content="{{ $seo->meta_keys }}">
    <meta name="author" content="GeniusOcean">
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

<link rel="stylesheet"
    href="{{ asset('assets/front/css/styles.php?color=' . str_replace('#', '', $gs->colors) . '&header_color=' . $gs->header_color) }}">
@if ($default_font->font_family)
    <link rel="stylesheet" id="colorr"
        href="{{ asset('assets/front/css/font.php?font_familly=' . $default_font->font_family) }}">
@else
    <link rel="stylesheet" id="colorr" href="{{ asset('assets/front/css/font.php?font_familly=' . ' Open Sans') }}">
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
@if (!empty($seo->facebook_pixel))
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
