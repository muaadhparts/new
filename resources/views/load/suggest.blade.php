{{-- Using cached $langg from AppServiceProvider instead of DB query --}}
@foreach($prods as $prod)
	@if ($langg->id == $prod->language_id)
	@php
		// Check if $prod is a MerchantItem or CatalogItem model
		$isMerchantItem = $prod instanceof \App\Models\MerchantItem;

		if ($isMerchantItem) {
			$merchantItemId = $prod->id;
			$merchantId = $prod->user_id;
			$productSlug = $prod->catalogItem->slug ?? $prod->slug;
		} else {
			$mp = $prod->merchantItems()->where('status', 1)->orderBy('price')->first();
			$merchantItemId = $mp->id ?? null;
			$merchantId = $mp->user_id ?? null;
			$productSlug = $prod->slug;
		}

		$productUrl = ($merchantItemId && $merchantId)
			? route('front.catalog-item', ['slug' => $productSlug, 'merchant_id' => $merchantId, 'merchant_item_id' => $merchantItemId])
			: 'javascript:;';
	@endphp
	<div class="docname">
		<a href="{{ $productUrl }}">
			<img src="{{ filter_var($prod->photo, FILTER_VALIDATE_URL) ? $prod->photo : ($prod->photo ? \Illuminate\Support\Facades\Storage::url($prod->photo) : asset('assets/images/noimage.png')) }}" alt="">
			<div class="search-content">
				@php
					$suggestName = getLocalizedProductName($prod);
				@endphp
				<p>{!! mb_strlen($suggestName,'UTF-8') > 66 ? str_replace($slug,'<b>'.$slug.'</b>',mb_substr($suggestName,0,66,'UTF-8')).'...' : str_replace($slug,'<b>'.$slug.'</b>',$suggestName)  !!} </p>
				<span style="font-size: 14px; font-weight:600; display:block;">{{ $prod->showPrice() }}</span>
			</div>
		</a>
	</div>
	@endif
@endforeach
