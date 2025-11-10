		<a class="clear">{{ __('Product(s) in Low Quantity.') }}</a>
		@if(count($datas) > 0)
		<a id="product-notf-clear" data-href="{{ route('product-notf-clear') }}" class="clear" href="javascript:;">
			{{ __('Clear All') }}
		</a>
		<ul>
		@foreach($datas as $data)
			<li>
				<a href="{{ route('admin-prod-edit',$data->product->id) }}"> <i class="icofont-cart"></i> <x-product-name :product="$data->product" :vendor-id="0" /></a>
				<a class="clear">{{ __('Stock') }} : {{$data->product->vendorSizeStock()}}</a>
			</li>
		@endforeach

		</ul>

		@else 

		<a class="clear" href="javascript:;">
			{{ __('No New Notifications.') }}
		</a>

		@endif