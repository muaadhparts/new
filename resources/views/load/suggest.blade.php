{{-- Using cached $langg from AppServiceProvider instead of DB query --}}
@foreach($prods as $cartItem)
	@if ($langg->id == $cartItem->language_id)
	@php
		// Check if $cartItem is a MerchantItem or CatalogItem model
		$isMerchantItem = $cartItem instanceof \App\Models\MerchantItem;

		if ($isMerchantItem) {
			$merchantItemId = $cartItem->id;
			$merchantId = $cartItem->user_id;
			$catalogItemSlug = $cartItem->catalogItem->slug ?? $cartItem->slug;
		} else {
			$mp = $cartItem->merchantItems()->where('status', 1)->orderBy('price')->first();
			$merchantItemId = $mp->id ?? null;
			$merchantId = $mp->user_id ?? null;
			$catalogItemSlug = $cartItem->slug;
		}

		$catalogItemUrl = ($merchantItemId && $merchantId)
			? route('front.catalog-item', ['slug' => $catalogItemSlug, 'merchant_id' => $merchantId, 'merchant_item_id' => $merchantItemId])
			: 'javascript:;';
	@endphp
	<div class="docname">
		<a href="{{ $catalogItemUrl }}">
			<img src="{{ filter_var($cartItem->photo, FILTER_VALIDATE_URL) ? $cartItem->photo : ($cartItem->photo ? \Illuminate\Support\Facades\Storage::url($cartItem->photo) : asset('assets/images/noimage.png')) }}" alt="">
			<div class="search-content">
				@php
					$suggestName = getLocalizedCatalogItemName($cartItem);
				@endphp
				<p>{!! mb_strlen($suggestName,'UTF-8') > 66 ? str_replace($slug,'<b>'.$slug.'</b>',mb_substr($suggestName,0,66,'UTF-8')).'...' : str_replace($slug,'<b>'.$slug.'</b>',$suggestName)  !!} </p>
				<span style="font-size: 14px; font-weight:600; display:block;">{{ $cartItem->showPrice() }}</span>
			</div>
		</a>
	</div>
	@endif
@endforeach
