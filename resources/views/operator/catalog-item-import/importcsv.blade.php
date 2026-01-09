@extends('layouts.operator')
@section('styles')

<link href="{{asset('assets/operator/css/catalog-item.css')}}" rel="stylesheet"/>

@endsection
@section('content')

						<div class="content-area">
							<div class="mr-breadcrumb">
								<div class="row">
									<div class="col-lg-12">
											<h4 class="heading">{{ __("Import CSV CatalogItem") }} <a class="add-btn" href="{{ route('operator-catalog-item-index') }}"><i class="fas fa-arrow-left"></i> {{ __("Back") }}</a></h4>
											<ul class="links">
												<li>
													<a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
												</li>
											<li>
												<a href="javascript:;">{{ __("Import CSV CatalogItem") }} </a>
											</li>
											<li>
												<a href="{{ route('operator-import-index') }}">{{ __("All CatalogItems") }}</a>
											</li>
												<li>
													<a href="{{ route('operator-import-csv') }}">{{ __("Import CSV CatalogItem") }}</a>
												</li>
											</ul>
									</div>
								</div>
							</div>
							<div class="add-catalogItem-content">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area" id="modalEdit">

					                      <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
					                      <form id="muaadhform" action="{{route('operator-import-csv-store')}}" method="POST" enctype="multipart/form-data">
					                        {{csrf_field()}}

                        @include('alerts.operator.form-both')  



												{{-- Old category system removed - Categories are now linked via parts tables (TreeCategories) --}}

											  <div class="row">
												  <div class="col-lg-4">
													  <div class="left-area">
														  <h4 class="heading">{{ __("Import File") }} *</h4>
													  </div>
												  </div>
												  <div class="col-lg-7">
													  <span class="file-btn">
														  <input type="file" id="csvfile" name="csvfile" accept=".csv">
													  </span>
												  </div>

											  </div>
												
	
												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
															
														</div>
													</div>
													<div class="col-lg-7 text-center">
														<button class="btn btn-primary" type="submit">{{ __("Start Import") }}</button>
													</div>
												</div>
											</form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>



@endsection

@section('scripts')

<script src="{{asset('assets/operator/js/catalog-item.js')}}"></script>
@endsection