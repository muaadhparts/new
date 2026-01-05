@extends('layouts.operator')
@section('styles')

<link href="{{asset('assets/admin/css/catalog-item.css')}}" rel="stylesheet"/>

@endsection
@section('content')

						<div class="content-area">
							<div class="mr-breadcrumb">
								<div class="row">
									<div class="col-lg-12">
											<h4 class="heading">{{ __("CatalogItem Bulk Upload") }}</h4>
											<ul class="links">
												<li>
													<a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
												</li>
											<li>
												<a href="javascript:;">{{ __("CatalogItems") }} </a>
											</li>
											<li>
												<a href="{{ route('operator-catalog-item-index') }}">{{ __("All CatalogItems") }}</a>
											</li>
												<li>
													<a href="{{ route('operator-catalog-item-import') }}">{{ __("Bulk Upload") }}</a>
												</li>
											</ul>
									</div>
								</div>
							</div>
							<div class="add-catalogItem-content">
								<div class="row">
									<div class="col-lg-12 p-5">

					                      <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
					                      <form id="muaadhform" action="{{route('operator-catalog-item-importsubmit')}}" method="POST" enctype="multipart/form-data">
					                        {{csrf_field()}}

                        						@include('alerts.operator.form-both')  

											  <div class="row">
												  <div class="col-lg-12 text-right">
													  <span style="margin-top:10px;"><a class="btn btn-primary" href="{{asset('assets/catalogItem-csv-format.csv')}}">{{ __("Download Sample CSV") }}</a></span>
												  </div>

											  </div>
											  <hr>

											  {{-- Merchant Selection --}}
											  <div class="row mb-4">
												  <div class="col-lg-6 offset-lg-3">
													  <div class="left-area">
														  <h4 class="heading">{{ __("Select Merchant") }} *</h4>
														  <p class="sub-heading">{{ __("Items will be imported for this merchant") }}</p>
													  </div>
													  <select name="merchant_id" class="form-control" required>
														  <option value="">{{ __("-- Select Merchant --") }}</option>
														  @foreach($merchants as $merchant)
															  <option value="{{ $merchant->id }}">{{ $merchant->shop_name ?? $merchant->name }} ({{ $merchant->email }})</option>
														  @endforeach
													  </select>
												  </div>
											  </div>
											  <hr>

											  <div class="row text-center">
												  <div class="col-lg-12">
														<div class="csv-icon">
															<i class="fas fa-file-csv"></i>
														</div>
												  </div>
												  <div class="col-lg-12">
													  <div class="left-area mr-4">
														  <h4 class="heading">{{ __("Upload a File") }} *</h4>
													  </div>
													  <span class="file-btn">
														  <input type="file" id="csvfile" name="csvfile" accept=".csv">
													  </span>

												  </div>
											  </div>

						                        <input type="hidden" name="type" value="Physical">
												<div class="row">
													<div class="col-lg-12 mt-4 text-center">
														<button class="btn btn-primary mr-5" type="submit">{{ __("Start Import") }}</button>
													</div>
												</div>
											</form>
									</div>
								</div>
							</div>
						</div>



@endsection