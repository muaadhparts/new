@extends('layouts.operator') 

@section('content')  
					<div class="content-area">

						<div class="mr-breadcrumb">
							<div class="row">
								<div class="col-lg-12">
										<h4 class="heading">{{ __('Completed Top Ups') }}</h4>
										<ul class="links">
											<li>
												<a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
											</li>
											<li>
												<a href="javascript:;">{{ __('Customer Top Ups') }} </a>
											</li>
											<li>
												<a href="{{ route('operator-wallet-log-index') }}">{{ __('Completed Top Ups') }}</a>
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
									                        <th>{{ __('Customer Name') }}</th>
															<th>{{ __('Amount') }}</th>
									                        <th>{{ __('Payment Method') }}</th>
									                        <th>{{ __('Transaction ID') }}</th>
									                        <th>{{ __('Status') }}</th>
														</tr>
													</thead>
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

		var table = $('#muaadhtable').DataTable({
			   ordering: false,
               processing: true,
               serverSide: true,
               ajax: '{{ route('operator-user-top-up-datatables','1') }}',
               columns: [
                        { data: 'name', name: 'name' },
                        { data: 'amount', name: 'amount' },
                        { data: 'method', name: 'method' },
                        { data: 'txnid', name: 'txnid' },
            			{ data: 'action', searchable: false, orderable: false }

                     ],
               language: {
                	processing: '<img src="{{asset('assets/images/'.$gs->admin_loader)}}">'
                }
            });


	})(jQuery);		

	</script>
	
@endsection   