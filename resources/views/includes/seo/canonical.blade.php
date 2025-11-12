{{-- SEO Canonical Tags --}}
@section('seo')
    @if(isset($productt,$vendorId,$merchant) && $vendorId && $merchant)
        <link rel="canonical" href="{{ route('front.product', ['slug'=>$productt->slug, 'vendor_id'=>$vendorId, 'merchant_product_id'=>$merchant->id]) }}">

        {{-- Open Graph Meta Tags --}}
        <meta property="og:type" content="product">
        <meta property="og:url" content="{{ route('front.product', ['slug'=>$productt->slug, 'vendor_id'=>$vendorId, 'merchant_product_id'=>$merchant->id]) }}">
        <meta property="og:title" content="{{ $productt->name }}">
        @if($productt->meta_description)
            <meta property="og:description" content="{{ $productt->meta_description }}">
        @endif
        @if($productt->photo)
            <meta property="og:image" content="{{ $productt->photo ? \Illuminate\Support\Facades\Storage::url($productt->photo) : asset('assets/images/noimage.png') }}">
        @endif

        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $productt->name }}">
        @if($productt->meta_description)
            <meta name="twitter:description" content="{{ $productt->meta_description }}">
        @endif
        @if($productt->photo)
            <meta name="twitter:image" content="{{ $productt->photo ? \Illuminate\Support\Facades\Storage::url($productt->photo) : asset('assets/images/noimage.png') }}">
        @endif

        {{-- Product Schema --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org/",
            "@type": "Product",
            "name": "{{ $productt->name }}",
            "image": "{{ $productt->photo ? \Illuminate\Support\Facades\Storage::url($productt->photo) : asset('assets/images/noimage.png') }}",
            "description": "{{ $productt->meta_description ?? strip_tags($productt->description ?? '') }}",
            "sku": "{{ $productt->sku }}",
            "offers": {
                "@type": "Offer",
                "url": "{{ route('front.product', ['slug'=>$productt->slug, 'vendor_id'=>$vendorId, 'merchant_product_id'=>$merchant->id]) }}",
                "priceCurrency": "{{ isset($currencies) ? ($currencies->where('is_default', 1)->first()->name ?? 'USD') : 'USD' }}",
                "price": "{{ $merchant->price }}",
                "availability": "{{ ($merchant->stock > 0 || is_null($merchant->stock)) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}"
            }
        }
        </script>
    @endif
@endsection
