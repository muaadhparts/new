@extends('layouts.operator') 

@section('content')  

					<div class="content-area">
						<div class="mr-breadcrumb">
							<div class="row">
								<div class="col-lg-12">
										<h4 class="heading">{{ __('Popular CatalogItems') }}</h4>
										<ul class="links">
											<li>
												<a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
											</li>
											<li>
												<a href="javascript:;">{{ __('SEO Tools') }} </a>
											</li>
											<li>
												<a href="javascript:;">{{ __('Popular CatalogItems') }}</a>
											</li>
										</ul>
								</div>
							</div>
						</div>
						<div class="catalogItem-area">
							<div class="row">
								<div class="col-lg-12">
									<div class="mr-table allproduct">
							          @include('alerts.form-error')
							          @include('alerts.form-success')
										<div class="table-responsive">
												<table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
													<thead>
														<tr>
									                        <th>{{ __('Part Number') }}</th>
									                        <th>{{ __('Name') }}</th>
									                        <th>{{ __('Brand') }}</th>
									                        <th>{{ __('Quality Brand') }}</th>
									                        <th>{{ __('Merchant') }}</th>
									                        <th>{{ __('Branch') }}</th>
									                        <th>{{ __('Clicks') }}</th>
														</tr>
													</thead>

                                              <tbody>
                                                @foreach($productss as $catalogItem)
                    								@foreach($catalogItem as $cartItem)
                                                        <tr>
                                                        <td><code>{{ $cartItem->catalogItem?->part_number ?? __('N/A') }}</code></td>
														<td>{{ $cartItem->catalogItem ? getLocalizedCatalogItemName($cartItem->catalogItem, 60) : __('N/A') }}</td>
                                                        @php
                                                            $fitments = $cartItem->catalogItem?->fitments ?? collect();
                                                            $brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
                                                            $firstBrand = $brands->first();
                                                        @endphp
                                                        <td>{{ $firstBrand ? getLocalizedBrandName($firstBrand) : __('N/A') }}</td>
                                                        <td>{{ $cartItem->merchantItem && $cartItem->merchantItem->qualityBrand ? getLocalizedQualityName($cartItem->merchantItem->qualityBrand) : __('N/A') }}</td>
                                                        <td>
                                                            @if($cartItem->merchantItem && $cartItem->merchantItem->user)
                                                                {{ $cartItem->merchantItem->user->shop_name ?: $cartItem->merchantItem->user->name }}
                                                            @else
                                                                {{ __('N/A') }}
                                                            @endif
                                                        </td>
                                                        <td>{{ $cartItem->merchantItem && $cartItem->merchantItem->merchantBranch ? $cartItem->merchantItem->merchantBranch->warehouse_name : __('N/A') }}</td>
                                                        <td>{{ $catalogItem->count() }}</td>
                                                        </tr>
                                                        @break
                    								@endforeach
                                                @endforeach
                                              </tbody>

												</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>




@endsection    



@section('scripts')

<script type="text/javascript">

(function($) {
		"use strict";

 			$('#muaadhtable').DataTable({
			   ordering: false
            });

$( document ).ready(function() {
        $(".btn-area").append('<div class="col-sm-4 table-contents">'+
        '<select class="form-control" id="prevdate">'+
          '<option value="30" {{$val==30 ? 'selected':''}}>{{__('Last 30 Days')}}</option>'+
          '<option value="15" {{$val==15 ? 'selected':''}}>{{__('Last 15 Days')}}</option>'+
          '<option value="7" {{$val==7 ? 'selected':''}}>{{__('Last 7 Days')}}</option>'+
        '</select>'+
          '</div>'); 

        $("#prevdate").change(function () {
        var sort = $("#prevdate").val();
        window.location = "{{url('/admin/catalogItems/popular/')}}/"+sort;
    });                                                                      
});

})(jQuery);

</script>

@endsection   