{{-- Using cached $langg from AppServiceProvider instead of DB query --}}
@foreach($prods as $cartItem)
	@if ($langg->id == $cartItem->language_id)
	@php
		// Check if $cartItem is a MerchantItem or CatalogItem model
		$isMerchantItem = $cartItem instanceof \App\Models\MerchantItem;

		if ($isMerchantItem) {
			$partNumber = $cartItem->catalogItem->part_number ?? $cartItem->part_number ?? null;
		} else {
			$partNumber = $cartItem->part_number;
		}

		$catalogItemUrl = $partNumber
			? route('front.part-result', $partNumber)
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
