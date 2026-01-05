@extends('layouts.load')
@section('content')

						<div class="content-area">

							<div class="add-catalogItem-content">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area" id="modalEdit">
											@include('includes.admin.form-error') 
											<form id="muaadhformdata" action="{{route('operator-article-type-create')}}" method="POST" enctype="multipart/form-data">
												{{csrf_field()}}

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Slug') }} *</h4>
																<p class="sub-heading">{{ __('(In Any English)') }}</p>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="slug" placeholder="{{ __('Slug') }}" required="" value="">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
															
														</div>
													</div>
													<div class="col-lg-7">
														<button class="btn btn-primary" type="submit">{{ __('Create Category') }}</button>
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