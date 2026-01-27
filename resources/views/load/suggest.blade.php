{{-- Using cached $langg from AppServiceProvider instead of DB query --}}
{{-- Display data pre-computed in Controller (DATA_FLOW_POLICY) --}}
@foreach($prods as $cartItem)
	@if ($langg->id == $cartItem->language_id)
	<div class="docname">
		<a href="{{ $suggestData[$cartItem->id]['url'] }}">
			<img src="{{ filter_var($cartItem->photo, FILTER_VALIDATE_URL) ? $cartItem->photo : ($cartItem->photo ? \Illuminate\Support\Facades\Storage::url($cartItem->photo) : asset('assets/images/noimage.png')) }}" alt="">
			<div class="search-content">
				<p>{!! mb_strlen($suggestData[$cartItem->id]['name'],'UTF-8') > 66 ? str_replace($slug,'<b>'.$slug.'</b>',mb_substr($suggestData[$cartItem->id]['name'],0,66,'UTF-8')).'...' : str_replace($slug,'<b>'.$slug.'</b>',$suggestData[$cartItem->id]['name'])  !!} </p>
				<span style="font-size: 14px; font-weight:600; display:block;">{{ $cartItem->showPrice() }}</span>
			</div>
		</a>
	</div>
	@endif
@endforeach
