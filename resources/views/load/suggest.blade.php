@php
if (Session::has('language'))
	{
		$language = DB::table('languages')->find(Session::get('language'));
	}
else
	{
		$language = DB::table('languages')->where('is_default','=',1)->first();
	}
@endphp
@foreach($prods as $prod)
	@if ($language->id == $prod->language_id)
	@php
		// Check if $prod is a MerchantProduct or Product model
		$isMerchantProduct = $prod instanceof \App\Models\MerchantProduct;

		if ($isMerchantProduct) {
			$merchantProductId = $prod->id;
			$vendorId = $prod->user_id;
			$productSlug = $prod->product->slug ?? $prod->slug;
		} else {
			$mp = $prod->merchantProducts()->where('status', 1)->orderBy('price')->first();
			$merchantProductId = $mp->id ?? null;
			$vendorId = $mp->user_id ?? null;
			$productSlug = $prod->slug;
		}

		$productUrl = ($merchantProductId && $vendorId)
			? route('front.product', ['slug' => $productSlug, 'vendor_id' => $vendorId, 'merchant_product_id' => $merchantProductId])
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
