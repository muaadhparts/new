		<a class="clear">{{ __('Catalog Item(s) in Low Quantity.') }}</a>
		@if(count($datas) > 0)
		<a id="catalog-item-notf-clear" data-href="{{ route('catalog-item-notf-clear') }}" class="clear" href="javascript:;">
			{{ __('Clear All') }}
		</a>
		<ul>
		@foreach($datas as $data)
			@php
				$catalogItem = $data->catalogItem;
				$catalogItemName = $catalogItem ? getLocalizedCatalogItemName($catalogItem, 30) : __('N/A');

				// المخزون من merchant_items
				$totalStock = 0;
				if ($catalogItem) {
					$totalStock = $catalogItem->merchantItems()->where('status', 1)->sum('stock');
				}
			@endphp
			<li>
				<a href="{{ route('operator-catalog-item-edit', $catalogItem->id ?? 0) }}"> <i class="icofont-cart"></i> {{ $catalogItemName }}</a>
				<a class="clear">{{ __('Stock') }} : {{ $totalStock }}</a>
			</li>
		@endforeach

		</ul>

		@else

		<a class="clear" href="javascript:;">
			{{ __('No New Notifications.') }}
		</a>

		@endif