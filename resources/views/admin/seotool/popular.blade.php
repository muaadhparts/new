@extends('layouts.admin') 

@section('content')  

					<div class="content-area">
						<div class="mr-breadcrumb">
							<div class="row">
								<div class="col-lg-12">
										<h4 class="heading">{{ __('Popular Products') }}</h4>
										<ul class="links">
											<li>
												<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
											</li>
											<li>
												<a href="javascript:;">{{ __('SEO Tools') }} </a>
											</li>
											<li>
												<a href="javascript:;">{{ __('Popular Products') }}</a>
											</li>
										</ul>
								</div>
							</div>
						</div>
						<div class="product-area">
							<div class="row">
								<div class="col-lg-12">
									<div class="mr-table allproduct">
							          @include('alerts.form-error')
							          @include('alerts.form-success')
										<div class="table-responsive">
												<table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
													<thead>
														<tr>
									                        <th>{{ __('Name') }}</th>
									                        <th>{{ __('Brand') }}</th>
									                        <th>{{ __('Quality Brand') }}</th>
									                        <th>{{ __('Vendor') }}</th>
									                        <th>{{ __('Category') }}</th>
									                        <th>{{ __('Clicks') }}</th>
														</tr>
													</thead>

                                              <tbody>
                                                @foreach($productss as $productt)
                    								@foreach($productt as $prod)
                                                        <tr>
														<td>
															{{ $prod->product ? getLocalizedProductName($prod->product, 60) : __('N/A') }}
														</td>
                                                        <td>
                                                            {{ $prod->product && $prod->product->brand ? getLocalizedBrandName($prod->product->brand) : __('N/A') }}
                                                        </td>
                                                        <td>
                                                            {{ $prod->merchantItem && $prod->merchantItem->qualityBrand ? getLocalizedQualityName($prod->merchantItem->qualityBrand) : __('N/A') }}
                                                        </td>
                                                        <td>
                                                            @if($prod->merchantItem && $prod->merchantItem->user)
                                                                {{ $prod->merchantItem->user->shop_name ?: $prod->merchantItem->user->name }}
                                                            @else
                                                                {{ __('N/A') }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            {{ $prod->product && $prod->product->brand ? $prod->product->brand->localized_name : __('N/A') }}
                                                        </td>
                                                        <td>{{ $productt->count() }}</td>
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
        window.location = "{{url('/admin/products/popular/')}}/"+sort;
    });                                                                      
});

})(jQuery);

</script>

@endsection   