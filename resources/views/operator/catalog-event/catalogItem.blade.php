		<a class="clear">{{ __('Catalog Item(s) in Low Quantity.') }}</a>
		@if(count($datas) > 0)
		<a id="catalog-item-event-clear" data-href="{{ route('catalog-item-event-clear') }}" class="clear" href="javascript:;">
			{{ __('Clear All') }}
		</a>
		<ul>
		@foreach($datas as $data)
			<li>
				<a href="{{ route('operator-catalog-item-edit', $data->catalogItem->id ?? 0) }}"> <i class="icofont-cart"></i> {{ $data->catalogItem ? getLocalizedCatalogItemName($data->catalogItem, 30) : __('N/A') }}</a>
				<a class="clear">{{ __('Stock') }} : {{ $data->total_stock ?? 0 }}</a>
			</li>
		@endforeach

		</ul>

		@else

		<a class="clear" href="javascript:;">
			{{ __('No New Events.') }}
		</a>

		@endif
