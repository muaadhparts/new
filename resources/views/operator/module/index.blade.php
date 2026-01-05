@extends('layouts.operator')

@section('content')

					<div class="content-area">

						<div class="mr-breadcrumb">
							<div class="row">
								<div class="col-lg-12">
									<h4 class="heading">{{ __("Modules") }}</h4>
									<ul class="links">
										<li>
											<a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
										</li>
										<li>
											<a href="{{ route('operator-module-index') }}">{{ __("Modules") }}</a>
										</li>
									</ul>
								</div>
							</div>
						</div>

						<div class="catalogItem-area">
							<div class="row">
								<div class="col-lg-12">
									<div class="mr-table allproduct">
                        				@include('alerts.form-success')
										<div class="table-responsive">
												<table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
													<thead>
														<tr>
                                                            <th class="pl-2">{{ __("Name") }}</th>
                                                            <th class="pl-2">{{ __("Keyword") }}</th>
                                                            <th class="pl-2">{{ __("Installation Date") }}</th>
														</tr>
													</thead>
												</table>
										</div>
									</div>
								</div>
							</div>
						</div>

					</div>


			{{-- DELETE MODAL --}}

			<div class="modal fade" id="confirm-status">
				<div class="modal-dialog">
				<div class="modal-content">

					<!-- Modal Header -->
					<div class="modal-header text-center">
					<h4 class="modal-title w-100">{{ __('Confirm Uninstall') }}</h4>
					</div>

					<!-- Modal body -->
					<div class="modal-body">
						<p class="text-center">{{ __('You are about to uninstall this Module.') }}</p>
						<p class="text-center">{{ __('Do you want to proceed?') }}</p>
					</div>

					<!-- Modal footer -->
					<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
						<a  class="btn btn-success btn-ok">{{ __('Uninstall') }}</a>
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

		$('#muaadhtable').DataTable({
			ordering: false,
			processing: true,
			serverSide: true,
			ajax: '{{ route('operator-module-datatables') }}',
			columns: [
					{ data: 'name', name: 'name' },
					{ data: 'keyword', name: 'keyword' },
					{ data: 'created_at', name: 'created_at' }
					],
			language : {
				processing: '<img src="{{asset('assets/images/'.$gs->admin_loader)}}">'
			}
        });

		$(function() {
			$(".btn-area").append('<div class="col-sm-4 table-contents">'+
				'<a class="add-btn" href="{{route('operator-module-create')}}">'+
			'<i class="fas fa-upload"></i> {{ __('Install New Module') }}'+
			'</a>'+
			'</div>');
      	});

})(jQuery);

	</script>

@endsection
