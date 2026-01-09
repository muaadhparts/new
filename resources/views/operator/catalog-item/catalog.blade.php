@extends('layouts.operator') 

@section('content')  
					<input type="hidden" id="headerdata" value="{{ __("CATALOG ITEM") }}">
					<div class="content-area">
						<div class="mr-breadcrumb">
							<div class="row">
								<div class="col-lg-12">
										<h4 class="heading">{{ __("Catalog Items") }}</h4>
										<ul class="links">
											<li>
												<a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
											</li>
											<li>
												<a href="javascript:;">{{ __("Catalog Items") }} </a>
											</li>
											<li>
												<a href="{{ route('operator-catalog-item-index') }}">{{ __("Catalog Items") }}</a>
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
									                        <th>{{ __("Image") }}</th>
									                        <th>{{ __("Name") }}</th>
									                        <th>{{ __("Brand") }}</th>
									                        <th>{{ __("Quality Brand") }}</th>
									                        <th>{{ __("Merchant") }}</th>
									                        <th>{{ __("Stock") }}</th>
									                        <th>{{ __("Price") }}</th>
									                        <th>{{ __("Status") }}</th>
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



{{-- HIGHLIGHT MODAL --}}

										<div class="modal fade" id="modal2" tabindex="-1" role="dialog" aria-labelledby="modal2" aria-hidden="true">
										
										
										<div class="modal-dialog highlight" role="document">
										<div class="modal-content">
												<div class="submit-loader">
														<img  src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
												</div>
											<div class="modal-header">
											<h5 class="modal-title"></h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
												
											</button>
											</div>
											<div class="modal-body">

											</div>
											<div class="modal-footer">
											<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __("Close") }}</button>
											</div>
										</div>
										</div>
</div>

{{-- HIGHLIGHT ENDS --}}


{{-- CATALOG MODAL ENDS --}}


{{-- CATALOG MODAL --}}

<div class="modal fade" id="catalog-modal" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

	<div class="modal-header d-block text-center">
		<h4 class="modal-title d-inline-block">{{ __("Remove Catalog") }}</h4>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
				
			</button>
	</div>

      <!-- Modal body -->
      <div class="modal-body">
            <p class="text-center">{{ __("You are about to remove this CatalogItem from Catalog.") }}</p>
            <p class="text-center">{{ __("Do you want to proceed?") }}</p>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __("Cancel") }}</button>
            <a class="btn btn-danger btn-ok">{{ __("Remove") }}</a>
      </div>

    </div>
  </div>
</div>

{{-- CATALOG MODAL ENDS --}}


@endsection    



@section('scripts')


{{-- DATA TABLE --}}

    <script type="text/javascript">

(function($) {
		"use strict";

		var table = $('#muaadhtable').DataTable({
			   ordering: false,
               processing: true,
               serverSide: true,
               ajax: '{{ route('operator-catalog-item-catalog-datatables') }}',
               columns: [
                        { data: 'photo', name: 'photo' },
                        { data: 'name', name: 'name' },
                        { data: 'brand', name: 'brand' },
                        { data: 'quality_brand', name: 'quality_brand' },
                        { data: 'merchant', name: 'merchant' },
                        { data: 'stock', name: 'stock' },
                        { data: 'price', name: 'price' },
                        { data: 'status', searchable: false, orderable: false},
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

{{-- DATA TABLE ENDS--}}

@endsection   