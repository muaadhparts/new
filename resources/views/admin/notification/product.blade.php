		<a class="clear">{{ __('Product(s) in Low Quantity.') }}</a>
		@if(count($datas) > 0)
		<a id="product-notf-clear" data-href="{{ route('product-notf-clear') }}" class="clear" href="javascript:;">
			{{ __('Clear All') }}
		</a>
		<ul>
		@foreach($datas as $data)
			@php
				$product = $data->product;
				$productName = $product ? getLocalizedProductName($product, 30) : __('N/A');

				// المخزون من merchant_products
				$totalStock = 0;
				if ($product) {
					$totalStock = $product->merchantProducts()->where('status', 1)->sum('stock');
				}
			@endphp
			<li>
				<a href="{{ route('admin-prod-edit', $product->id ?? 0) }}"> <i class="icofont-cart"></i> {{ $productName }}</a>
				<a class="clear">{{ __('Stock') }} : {{ $totalStock }}</a>
			</li>
		@endforeach

		</ul>

		@else

		<a class="clear" href="javascript:;">
			{{ __('No New Notifications.') }}
		</a>

		@endif