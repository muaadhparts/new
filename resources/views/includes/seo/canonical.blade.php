{{-- SEO Canonical & Structured Data Tags --}}
@section('seo')
    @if(isset($catalogItem, $merchantId, $merchant) && $merchantId && $merchant)
        @php
            $productUrl = route('front.catalog-item', ['slug'=>$catalogItem->slug, 'merchant_item_id'=>$merchant->id]);
            $imageUrl = $catalogItem->photo
                ? (filter_var($catalogItem->photo, FILTER_VALIDATE_URL) ? $catalogItem->photo : \Illuminate\Support\Facades\Storage::url($catalogItem->photo))
                : asset('assets/images/noimage.png');
            $description = $catalogItem->meta_description ?? strip_tags($catalogItem->description ?? $catalogItem->name);
            $currency = isset($curr) ? $curr->name : 'SAR';
            $brandName = $catalogItem->brand->name ?? 'Generic';
        @endphp

        {{-- Canonical URL --}}
        <link rel="canonical" href="{{ $productUrl }}">

        {{-- Open Graph Meta Tags --}}
        <meta property="og:type" content="product">
        <meta property="og:url" content="{{ $productUrl }}">
        <meta property="og:name" content="{{ $catalogItem->name }}">
        <meta property="og:description" content="{{ Str::limit($description, 200) }}">
        <meta property="og:image" content="{{ $imageUrl }}">
        <meta property="og:site_name" content="{{ $gs->site_name ?? config('app.name') }}">
        <meta property="product:price:amount" content="{{ $merchant->price }}">
        <meta property="product:price:currency" content="{{ $currency }}">
        @if($merchant->stock > 0 || is_null($merchant->stock))
            <meta property="product:availability" content="in stock">
        @else
            <meta property="product:availability" content="out of stock">
        @endif

        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:name" content="{{ $catalogItem->name }}">
        <meta name="twitter:description" content="{{ Str::limit($description, 200) }}">
        <meta name="twitter:image" content="{{ $imageUrl }}">

        {{-- Product Schema (JSON-LD) - Google Rich Results --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org/",
            "@type": "Product",
            "name": {!! json_encode($catalogItem->name) !!},
            "image": {!! json_encode($imageUrl) !!},
            "description": {!! json_encode(Str::limit($description, 500)) !!},
            "sku": {!! json_encode($catalogItem->part_number ?? $catalogItem->sku ?? '') !!},
            "mpn": {!! json_encode($catalogItem->part_number ?? '') !!},
            "brand": {
                "@type": "Brand",
                "name": {!! json_encode($brandName) !!}
            },
            @if($catalogItem->brand)
            "category": {!! json_encode($catalogItem->brand->name) !!},
            @endif
            "offers": {
                "@type": "Offer",
                "url": {!! json_encode($productUrl) !!},
                "priceCurrency": {!! json_encode($currency) !!},
                "price": {{ $merchant->price }},
                "availability": "{{ ($merchant->stock > 0 || is_null($merchant->stock)) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
                "seller": {
                    "@type": "Organization",
                    "name": {!! json_encode($merchant->user->shop_name ?? $merchant->user->name ?? 'Merchant') !!}
                },
                "priceValidUntil": "{{ now()->addYear()->format('Y-m-d') }}",
                "itemCondition": "https://schema.org/NewCondition"
            }
            @if($catalogItem->reviews_count ?? false)
            ,"aggregateRating": {
                "@type": "AggregateRating",
                "ratingValue": {{ $catalogItem->average_rating ?? 4 }},
                "reviewCount": {{ $catalogItem->reviews_count ?? 1 }}
            }
            @endif
        }
        </script>

        {{-- BreadcrumbList Schema --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org/",
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": {!! json_encode(__('Home')) !!},
                    "item": {!! json_encode(url('/')) !!}
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": {!! json_encode(__('Catalog')) !!},
                    "item": {!! json_encode(route('front.catalog')) !!}
                }
                @if($catalogItem->brand)
                ,{
                    "@type": "ListItem",
                    "position": 3,
                    "name": {!! json_encode($catalogItem->brand->name) !!},
                    "item": {!! json_encode(route('front.catalog', ['category' => $catalogItem->brand->slug])) !!}
                },
                {
                    "@type": "ListItem",
                    "position": 4,
                    "name": {!! json_encode($catalogItem->name) !!},
                    "item": {!! json_encode($productUrl) !!}
                }
                @else
                ,{
                    "@type": "ListItem",
                    "position": 3,
                    "name": {!! json_encode($catalogItem->name) !!},
                    "item": {!! json_encode($productUrl) !!}
                }
                @endif
            ]
        }
        </script>
    @endif
@endsection
