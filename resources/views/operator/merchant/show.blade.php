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
                                                                    @if($data->isTrustBadgeVerified())
                                                                    <a class="badge badge-success verify-link" href="javascript:;">Verified</a>
                                                                    <a class="set-gallery1" href="javascript:;" data-bs-toggle="modal" data-bs-target="#setgallery"><input type="hidden" value="{{ $data->trustBadges()->where('status','=','Verified')->first()->id }}">(View)</a>
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
                                                        <h4 class="name">{{ __("Items Added") }} ({{ $data->merchantItems()->count() }})</h4>
                                                        <div class="table-responsive">
                                                                <table id="merchant-items-table" class="table table-hover dt-responsive" cellspacing="0" width="100%">
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
                    <h5 class="modal-name" id="merchantformLabel">{{ __("Send Message") }}</h5>
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
				<h5 class="modal-name" id="exampleModalCenterName">{{ __('Attachments') }}</h5>
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

    $('#merchant-items-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('operator-merchant-items-datatables', $data->id) }}',
        columns: [
            { data: 'mp_id', name: 'id' },
            { data: 'name', name: 'name', orderable: false },
            { data: 'brand', name: 'brand', orderable: false },
            { data: 'quality_brand', name: 'quality_brand', orderable: false },
            { data: 'condition', name: 'condition', orderable: false },
            { data: 'stock', name: 'stock', orderable: false },
            { data: 'price', name: 'price', orderable: false },
            { data: 'status', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true,
        language: {
            processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
        }
    });

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
						url:"{{ route('operator-trust-badge-show') }}",
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