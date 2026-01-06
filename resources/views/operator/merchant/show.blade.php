@extends('layouts.operator')

@section('content')

                    <div class="content-area">
                        <div class="mr-breadcrumb">
                            <div class="row">
                                <div class="col-lg-12">
                                        <h4 class="heading">{{ __("Merchant Details") }} <a class="add-btn" href="{{ url()->previous() }}"><i class="fas fa-arrow-left"></i> {{ __("Back") }}</a></h4>
                                        <ul class="links">
                                            <li>
                                                <a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('operator-merchant-index') }}">{{ __("Merchants") }}</a>
                                            </li>
                                            <li>
                                                <a href="{{ route('operator-merchant-show',$data->id) }}">{{ __("Details") }}</a>
                                            </li>
                                        </ul>
                                </div>
                            </div>
                        </div>
                            <div class="add-catalogItem-content1 customar-details-area">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="catalogItem-description">
                                            <div class="body-area">
                                            <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="user-image">
                                                            @if($data->is_provider == 1)
                                                            <img src="{{ $data->photo ? asset($data->photo):asset('assets/images/noimage.png')}}" alt="No Image">
                                                            @else
                                                            <img src="{{ $data->photo ? asset('assets/images/users/'.$data->photo):asset('assets/images/noimage.png')}}" alt="{{ __("No Image") }}">                                            
                                                            @endif
                                                        <a href="javascript:;" class="btn btn-primary send" data-email="{{ $data->email }}" data-bs-toggle="modal" data-bs-target="#merchantform">{{ __("Send Message") }}</a>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                    <div class="table-responsive show-table">
                                                        <table class="table">
                                                        <tr>
                                                            <th>{{ __("Merchant ID#") }}</th>
                                                            <td>{{$data->id}}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __("Store Name") }}</th>
                                                            <td>{{ $data->shop_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __("Owner Name") }}</th>
                                                            <td>{{ $data->owner_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __("Email") }}</th>
                                                            <td>{{ $data->email }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __("Shop Number") }}</th>
                                                            <td>{{ $data->shop_number }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __("Registration Number") }}</th>
                                                            <td>{{ $data->reg_number }}</td>
                                                        </tr>

                                                        <tr>
                                                            <th>{{ __("Shop Address") }}</th>
                                                            <td>{{ $data->shop_address }}</td>
                                                        </tr>
                                                        
                                                        </table>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                    <div class="table-responsive show-table">
                                                    <table class="table">

                                                        <tr>
                                                            <th>{{ __("Message") }}</th>
                                                            <td>{{ $data->shop_message }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __("Total CatalogItem(s)") }}</th>
                                                            <td>{{ $data->merchantItems()->count() }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th>{{ __("Joined") }}</th>
                                                            <td>{{ $data->created_at->diffForHumans() }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th width="35%">{{ __("Shop Details") }}</th>
                                                            <td>{!! clean($data->shop_details , array('Attr.EnableID' => true)) !!}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                    @if($data->checkStatus())
                                                                    <a class="badge badge-success verify-link" href="javascript:;">Verified</a>
                                                                    <a class="set-gallery1" href="javascript:;" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="{{ $data->verifies()->where('status','=','Verified')->first()->id }}">(View)</a>
                                                                    @else 
                                                                    <a class="badge badge-danger verify-link" href="javascript:;">Unverified</a>
                                                                    @endif
                                                            </td>
                                                            <td>
                                                            </td>
                                                        </tr>
                                                        </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="purchase-table-wrap">
                                                <div class="purchase-details-table">
                                                    <div class="mr-table">
                                                        <h4 class="title">{{ __("Items Added") }}</h4>
                                                        <div class="table-responsive">
                                                                <table id="example2" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>{{ __("MP ID") }}</th>
                                                                            <th>{{ __("Name") }}</th>
                                                                            <th>{{ __("Brand") }}</th>
                                                                            <th>{{ __("Quality Brand") }}</th>
                                                                            <th>{{ __("Condition") }}</th>
                                                                            <th>{{ __("Stock") }}</th>
                                                                            <th>{{ __("Price") }}</th>
                                                                            <th>{{ __("Status") }}</th>
                                                                            <th></th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($data->merchantItems as $merchantItem)
                                                                        @php
                                                                            // Get the actual catalog item
                                                                            $dt = $merchantItem->catalogItem;

                                                                            $adminMerchantUrl = $dt && $dt->slug
                                                                                ? route('front.catalog-item', ['slug' => $dt->slug, 'merchant_id' => $merchantItem->user_id, 'merchant_item_id' => $merchantItem->id])
                                                                                : '#';


                                                                            // حالة المنتج (جديد/مستعمل)
                                                                            $condition = $merchantItem->item_condition == 1 ? __('Used') : __('New');

                                                                            // المخزون
                                                                            $stck = $merchantItem->stock;
                                                                            if($stck === null || $stck === '')
                                                                                $stckDisplay = __('Unlimited');
                                                                            elseif((int)$stck === 0)
                                                                                $stckDisplay = '<span class="text-danger">'.__('Out Of Stock').'</span>';
                                                                            else
                                                                                $stckDisplay = $stck;

                                                                            // السعر مع العمولة
                                                                            $gs = cache()->remember('muaadhsettings', now()->addDay(), fn () => DB::table('muaadhsettings')->first());
                                                                            $price = (float) $merchantItem->price;
                                                                            $finalPrice = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);
                                                                        @endphp
                                                                        <tr>
                                                                            <td><a href="{{ $adminMerchantUrl }}" target="_blank">{{ sprintf("%'.06d", $merchantItem->id) }}</a></td>
                                                                            <td>{{ $dt ? getLocalizedCatalogItemName($dt, 50) : __('N/A') }}</td>
                                                                            <td>{{ $dt && $dt->brand ? getLocalizedBrandName($dt->brand) : __('N/A') }}</td>
                                                                            <td>{{ $merchantItem->qualityBrand ? getLocalizedQualityName($merchantItem->qualityBrand) : __('N/A') }}</td>
                                                                            <td><span class="badge {{ $merchantItem->item_condition == 1 ? 'badge-warning' : 'badge-success' }}">{{ $condition }}</span></td>
                                                                            <td>{!! $stckDisplay !!}</td>
                                                                            <td>{{ \PriceHelper::showAdminCurrencyPrice($finalPrice) }}</td>
                                                                            <td>
                                                                                <div class="action-list">
                                                                                <select class="process select droplinks {{ $merchantItem->status == 1 ? 'drop-success' : 'drop-danger' }}">
                                                                                    <option data-val="1" value="{{ route('operator-merchant-item-status',['id' => $merchantItem->id, 'status' => 1]) }}" {{ $merchantItem->status == 1 ? 'selected' : '' }}>{{ __("Activated") }}</option>
                                                                                    <option data-val="0" value="{{ route('operator-merchant-item-status',['id' => $merchantItem->id, 'status' => 0]) }}" {{ $merchantItem->status == 0 ? 'selected' : '' }}>{{ __("Deactivated") }}</option>
                                                                                </select>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <a href="{{ route('operator-catalog-item-edit', $dt->id ?? 0) }}" class="view-details">
                                                                                    <i class="fas fa-eye"></i>{{ __("Details") }}
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

{{-- MESSAGE MODAL --}}
<div class="sub-categori">
    <div class="modal" id="merchantform" tabindex="-1" role="dialog" aria-labelledby="merchantformLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="merchantformLabel">{{ __("Send Message") }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                            
                        </button>
                </div>
            <div class="modal-body">
                <div class="container-fluid p-0">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="contact-form">
                                <form id="emailreply1" action="{{route('operator-send-message')}}">
                                    {{csrf_field()}}
                                    <input type="hidden" name="type" value="merchant">
                                    <ul>
                                        <li>
                                            <input type="email" class="form-control eml-val" id="eml1" name="to" placeholder="{{ __("Email") }} *" value="" required="">
                                        </li>
                                        <li>
                                            <input type="text" class="form-control" id="subj1" name="subject" placeholder="{{ __("Subject") }} *" required="">
                                        </li>
                                        <li>
                                            <textarea class="form-control textarea" name="message" id="msg1" placeholder="{{ __("Your Message") }} *" required=""></textarea>
                                        </li>
                                    </ul>
                                    <button class="btn btn-primary" id="emlsub1" type="submit">{{ __("Send Message") }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>

{{-- MESSAGE MODAL ENDS --}}


{{-- GALLERY MODAL --}}

<div class="modal fade" id="setgallery" tabindex="-1" role="dialog" aria-labelledby="setgallery" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
			<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalCenterTitle">{{ __('Attachments') }}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
				
				</button>
			</div>
			<div class="modal-body">



				<div class="gallery-images">
					<div class="selected-image">
						<div class="row">


						</div>
					</div>
				</div>
			</div>


			</div>
		</div>
	</div>


{{-- GALLERY MODAL ENDS --}}


@endsection

@section('scripts')

<script type="text/javascript">

(function($) {
		"use strict";

$('#example2').dataTable( {
  "ordering": false,
      'lengthChange': false,
      'searching'   : false,
      'ordering'    : false,
      'info'        : false,
      'autoWidth'   : false,
      'responsive'  : true
} );

})(jQuery);

</script>

<script type="text/javascript">
	
	// Gallery Section Update
	
	
    (function($) {
		"use strict";

		$(document).on("click", ".set-gallery1" , function(){
			var pid = $(this).find('input[type=hidden]').val();
			$('#pid').val(pid);
			$('.selected-image .row').html('');
				$.ajax({
						type: "GET",
						url:"{{ route('operator-vr-show') }}",
						data:{id:pid},
						success:function(data){

						  if(data[0] == 0)
						  {
							$('.selected-image .row').addClass('justify-content-center');
							  $('.selected-image .row').html('<h3>{{ __("No Images Found.") }}</h3>');
						   }
						  else {
							$('.selected-image .row').removeClass('justify-content-center');
							  $('.selected-image .row h3').remove();      
							  var arr = $.map(data[1], function(el) {
							  return el });
	
							  for(var k in arr)
							  {
							$('.selected-image .row').append('<div class="col-sm-6">'+
											'<div class="img gallery-img">'+
												'<a class="img-popup" href="'+'{{asset('assets/images/attachments').'/'}}'+arr[k]+'">'+
												'<img  src="'+'{{asset('assets/images/attachments').'/'}}'+arr[k]+'" alt="gallery image">'+
												'</a>'+
											'</div>'+
										  '</div>');
							  }                         
						   }
	 
							$('.img-popup').magnificPopup({
							type: 'image'
						  });
	
						 $(document).off('focusin');
	
						}
	
	
					  });
		  });
	
	// Gallery Section Update Ends	
	
          })(jQuery);

	</script>

@endsection