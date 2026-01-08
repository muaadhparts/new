		<a class="clear">{{ __('New Purchase(s).') }}</a>
		@if(count($datas) > 0)
		<a id="purchase-event-clear" data-href="{{ route('purchase-event-clear') }}" class="clear" href="javascript:;">
			{{ __('Clear All') }}
		</a>
		<ul>
		@foreach($datas as $data)
			<li>
				<a href="{{ route('operator-purchase-show',$data->purchase_id) }}"> <i class="fas fa-newspaper"></i> {{ __('You Have a new purchase.') }}</a>
			</li>
		@endforeach

		</ul>

		@else

		<a class="clear" href="javascript:;">
			{{ __('No New Events.') }}
		</a>

		@endif
