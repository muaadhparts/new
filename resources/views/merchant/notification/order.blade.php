		<a class="clear">{{ __('New Order(s).') }}</a>
		@if(count($datas) > 0)
		<a id="order-notf-clear" data-href="{{ route('merchant-purchase-notf-clear',Auth::user()->id) }}" class="clear" href="javascript:;">
			{{ __('Clear All.') }}
		</a>
		<ul>
		@foreach($datas as $data)
			<li>
				<a href="{{ route('merchant-purchase-show',$data->purchase_number) }}"> <i class="fas fa-newspaper"></i> {{ __('You Have a new order.') }}</a>
			</li>
		@endforeach

		</ul>

		@else 

		<a class="clear" href="javascript:;">
			{{ __('No New Notifications.')}}
		</a>

		@endif