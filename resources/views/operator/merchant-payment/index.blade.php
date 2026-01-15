@extends('layouts.operator') 

@section('content')  
					<input type="hidden" id="headerdata" value="{{ __('MERCHANT PAYMENT') }}">
					<div class="content-area">
						<div class="mr-breadcrumb">
							<div class="row">
								<div class="col-lg-12">
										<h4 class="heading">{{ __('Merchant Payments') }}</h4>
										<ul class="links">
											<li>
												<a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
											</li>
											<li>
												<a href="javascript:;">{{ __('Payment Settings') }} </a>
											</li>
											<li>
												<a href="{{ route('operator-merchant-payment-index') }}">{{ __('Merchant Payments') }}</a>
											</li>
										</ul>
								</div>
							</div>
						</div>
						<div class="catalogItem-area">
							<div class="row">
								<div class="col-lg-12">
									<div class="mr-table allproduct">

                        @include('alerts.operator.form-success') 

										<div class="table-responsive">
												<table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
													<thead>
														<tr>
									                        <th width="20%">{{ __('Name') }}</th>
									                        <th width="30%">{{ __('Details') }}</th>
															<th width="10%">{{ __('Checkout') }}</th>
															<th width="10%">{{ __('Top Up') }}</th>
															<th width="10%">{{ __('Subscription') }}</th>
									                        <th>{{ __('Options') }}</th>
														</tr>
													</thead>
												</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>


{{-- ADD / EDIT MODAL --}}

										<div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
										
										
										<div class="modal-dialog modal-dialog-centered" role="document">
										<div class="modal-content">
												<div class="submit-loader">
														<img  src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
												</div>
											<div class="modal-header">
											<h5 class="modal-name"></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
												
											</button>
											</div>
											<div class="modal-body">

											</div>
											<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
											</div>
										</div>
										</div>
</div>

{{-- ADD / EDIT MODAL ENDS --}}


{{-- DELETE MODAL --}}

<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

	<div class="modal-header d-block text-center">
		<h4 class="modal-name d-inline-block">{{ __('Confirm Delete') }}</h4>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
				
			</button>
	</div>

      <!-- Modal body -->
      <div class="modal-body">
            <p class="text-center">{{ __('You are about to delete this Payment') }}.</p>
            <p class="text-center">{{ __('Do you want to proceed?') }}</p>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            			<form action="" class="d-inline delete-form" method="POST">
				<input type="hidden" name="_method" value="delete" />
				<input type="hidden" name="_token" value="{{ csrf_token() }}">
				<button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
			</form>
      </div>

    </div>
  </div>
</div>

{{-- DELETE MODAL ENDS --}}


@endsection    



@section('scripts')

    <script type="text/javascript">


(function($) {
		"use strict";

		var table = $('#muaadhtable').DataTable({
			   ordering: false,
               processing: true,
               serverSide: true,
               ajax: '{{ route('operator-merchant-payment-datatables') }}',
               columns: [
                        { data: 'name', name: 'name' },
                        { data: 'details', name: 'details' },
            			{ data: 'checkout', searchable: false, orderable: false },
						{ data: 'topup', searchable: false, orderable: false },
						{ data: 'subscription', searchable: false, orderable: false },
            			{ data: 'action', searchable: false, orderable: false }

                     ],
               language: {
                	processing: '<img src="{{asset('assets/images/'.$gs->admin_loader)}}">'
                },
				drawCallback : function( settings ) {
	    				$('.select').niceSelect();	
				}
            });

      	$(function() {
        $(".btn-area").append('<div class="col-sm-4 table-contents">'+
        	'<a class="add-btn" data-href="{{route('operator-merchant-payment-create')}}" id="add-data" data-bs-toggle="modal" data-bs-target="#modal1">'+
          '<i class="fas fa-plus"></i> {{ __('Add New Merchant Payment') }}'+
          '</a>'+
          '</div>');
      });												

})(jQuery);
				
    </script>
@endsection   