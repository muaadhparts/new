<?php if(isset($page->meta_tag) && isset($page->meta_description)): ?>
    <meta name="keywords" content="<?php echo e($page->meta_tag); ?>">
    <meta name="description" content="<?php echo e($page->meta_description); ?>">
    <title><?php echo e($gs->title); ?></title>
<?php elseif(isset($blog->meta_tag) && isset($blog->meta_description)): ?>
    <meta property="og:title" content="<?php echo e($blog->title); ?>" />
    <meta property="og:description"
        content="<?php echo e($blog->meta_description != null ? $blog->meta_description : strip_tags($blog->meta_description)); ?>" />
    <meta property="og:image" content="<?php echo e(asset('assets/images/blogs/' . $blog->photo)); ?>" />
    <meta name="keywords" content="<?php echo e($blog->meta_tag); ?>">
    <meta name="description" content="<?php echo e($blog->meta_description); ?>">
    <title><?php echo e($gs->title); ?></title>
<?php elseif(isset($productt)): ?>
    <meta name="keywords" content="<?php echo e($productt->meta_tag ?? ''); ?>">
    <meta name="description"
        content="<?php echo e($productt->meta_description != null ? $productt->meta_description : strip_tags($productt->description)); ?>">
    <meta property="og:title" content="<?php echo e($productt->name); ?>" />
    <meta property="og:description"
        content="<?php echo e($productt->meta_description != null ? $productt->meta_description : strip_tags($productt->description)); ?>" />
    <meta property="og:image" content="<?php echo e(filter_var($productt->photo, FILTER_VALIDATE_URL) ? $productt->photo : ($productt->photo ? \Illuminate\Support\Facades\Storage::url($productt->photo) : asset('assets/images/noimage.png'))); ?>" />
    <meta name="author" content="Muaadh">
    <title><?php echo e(substr($productt->name, 0, 11) . '-'); ?><?php echo e($gs->title); ?></title>
<?php else: ?>
    <meta property="og:title" content="<?php echo e($gs->title); ?>" />
    <meta property="og:image" content="<?php echo e(asset('assets/images/' . $gs->logo)); ?>" />
    <meta name="keywords" content="<?php echo e($seo->meta_keys); ?>">
    <meta name="author" content="Muaadh">
    <title><?php echo e($gs->title); ?></title>
<?php endif; ?>

<?php if($default_font->font_value): ?>
    <link
        href="https://fonts.googleapis.com/css?family=<?php echo e($default_font->font_value); ?>:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap"
        rel="stylesheet">
<?php else: ?>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
<?php endif; ?>

<link rel="stylesheet"
    href="<?php echo e(asset('assets/front/css/styles.php?color=' . str_replace('#', '', $gs->colors) . '&header_color=' . $gs->header_color)); ?>">
<?php if($default_font->font_family): ?>
    <link rel="stylesheet" id="colorr"
        href="<?php echo e(asset('assets/front/css/font.php?font_familly=' . $default_font->font_family)); ?>">
<?php else: ?>
    <link rel="stylesheet" id="colorr" href="<?php echo e(asset('assets/front/css/font.php?font_familly=' . ' Open Sans')); ?>">
<?php endif; ?>

<?php if(!empty($seo->google_analytics)): ?>
    <script>
        "use strict";
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', '<?php echo e($seo->google_analytics); ?>');
    </script>
<?php endif; ?>
<?php if(isset($seo) && isset($seo->facebook_pixel) && !empty($seo->facebook_pixel) && $seo->facebook_pixel != 'null' && $seo->facebook_pixel != null && trim($seo->facebook_pixel) != '' && strlen(trim($seo->facebook_pixel)) > 5): ?>
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
        fbq('init', '<?php echo e($seo->facebook_pixel); ?>');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=<?php echo e($seo->facebook_pixel); ?>&ev=PageView&noscript=1" />
    </noscript>
<?php endif; ?>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/extra_head.blade.php ENDPATH**/ ?>