{{-- resources/views/livewire/alternativerelatedproduct.blade.php --}}
<div class="product-cards-slider">
    @forelse($alternatives as $alt)
        @php
            // تأمين أن المتغير $product هو كائن Product كما يتوقع الـ include
            $product = $alt instanceof \App\Models\Product ? $alt : ($alt->product ?? null);
        @endphp

        @if($product)
            @include('includes.frontend.home_product', ['class' => 'not'])
        @endif
    @empty
        <p class="text-center my-3">{{ __('No alternatives found for this item yet.') }}</p>
    @endforelse
</div>


