@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __(" RIDER") }}">
<div class="content-area">
	<div class="mr-breadcrumb">
		<div class="row">
			<div class="col-lg-12">
				<h4 class="heading">{{ __("Riders") }}</h4>
				<ul class="links">
					<li>
						<a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
					</li>
					<li>
						<a href="{{ route('operator-rider-index') }}">{{ __("Riders") }}</a>
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
									<th>{{ __("Name") }}</th>
									<th>{{ __("Phone") }}</th>
									<th>{{ __("Email") }}</th>
									<th>{{ __("Total Delivery") }}</th>
									<th>{{ __("Options") }}</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header d-block text-center">
				<h4 class="modal-title d-inline-block">{{ __("Confirm Delete") }}</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					
				</button>
			</div>

			<!-- Modal body -->
			<div class="modal-body">
				<p class="text-center">{{ __("You are about to delete this Customer.") }}</p>
				<p class="text-center">{{ __("Do you want to proceed?") }}</p>
			</div>

			<!-- Modal footer -->
			<div class="modal-footer justify-content-center">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __("Cancel") }}</button>
				<form action="" class="d-inline delete-form" method="POST">
					<input type="hidden" name="_method" value="delete" />
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
					<button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
				</form>
			</div>

		</div>
	</div>
</div>


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
								<form id="emailreply1" action="{{route("admin-send-message")}}">
									{{csrf_field()}}
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



@endsection

@section('scripts')

{{-- DATA TABLE --}}

<script type="text/javascript">
	(function($) {
		"use strict";

		var table = $('#muaadhtable').DataTable({
			   ordering: false,
			   responsive: true,
               processing: true,
               serverSide: true,
               ajax: '{{ route('operator-rider-datatables') }}',
               columns: [
                        { data: 'name', name: 'name' },
                        { data: 'phone', name: 'phone' },
                        { data: 'email', name: 'email' },
                        { data: 'total_delivery', name: 'total_delivery' },
            			{ data: 'action', searchable: false, orderable: false }
                     ],
               language : {
                	processing: '<img src="{{asset('assets/images/'.$gs->admin_loader)}}">'
                },
                drawCallback : function( settings ) {
                        $('.select').niceSelect();  
                }
            });


})(jQuery);


</script>

{{-- DATA TABLE --}}





@endsection