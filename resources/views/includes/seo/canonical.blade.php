{{-- SEO Canonical Tags --}}
@section('seo')
    @if(isset($catalogItem,$merchantId,$merchant) && $merchantId && $merchant)
        <link rel="canonical" href="{{ route('front.catalog-item', ['slug'=>$catalogItem->slug, 'merchant_id'=>$merchantId, 'merchant_item_id'=>$merchant->id]) }}">

        {{-- Open Graph Meta Tags --}}
        <meta property="og:type" content="catalogItem">
        <meta property="og:url" content="{{ route('front.catalog-item', ['slug'=>$catalogItem->slug, 'merchant_id'=>$merchantId, 'merchant_item_id'=>$merchant->id]) }}">
        <meta property="og:title" content="{{ $catalogItem->name }}">
        @if($catalogItem->meta_description)
            <meta property="og:description" content="{{ $catalogItem->meta_description }}">
        @endif
        @if($catalogItem->photo)
            <meta property="og:image" content="{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}">
        @endif

        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $catalogItem->name }}">
        @if($catalogItem->meta_description)
            <meta name="twitter:description" content="{{ $catalogItem->meta_description }}">
        @endif
        @if($catalogItem->photo)
            <meta name="twitter:image" content="{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}">
        @endif

        {{-- CatalogItem Schema --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org/",
            "@type": "CatalogItem",
            "name": "{{ $catalogItem->name }}",
            "image": "{{ $catalogItem->photo ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png') }}",
            "description": "{{ $catalogItem->meta_description ?? strip_tags($catalogItem->description ?? '') }}",
            "part_number": "{{ $catalogItem->part_number }}",
            "offers": {
                "@type": "Offer",
                "url": "{{ route('front.catalog-item', ['slug'=>$catalogItem->slug, 'merchant_id'=>$merchantId, 'merchant_item_id'=>$merchant->id]) }}",
                "priceCurrency": "{{ isset($currencies) ? ($currencies->where('is_default', 1)->first()->name ?? 'USD') : 'USD' }}",
                "price": "{{ $merchant->price }}",
                "availability": "{{ ($merchant->stock > 0 || is_null($merchant->stock)) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}"
            }
        }
        </script>
    @endif
@endsection
