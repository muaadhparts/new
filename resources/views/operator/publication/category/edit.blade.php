@extends('layouts.load')
@section('content')

						<div class="content-area">
							<div class="add-catalogItem-content1">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area">
											@include('alerts.operator.form-error') 
											<form id="muaadhformdata" action="{{route('operator-article-type-update',$data->id)}}" method="POST" enctype="multipart/form-data">
												
												{{csrf_field()}}
											

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Name') }} *</h4>
																<p class="sub-heading">{{ __('(In English)') }}</p>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="name" placeholder="{{ __('Name') }}" required="" value="{{$data->name}}">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Slug') }} *</h4>
																<p class="sub-heading">{{ __('(In English)') }}</p>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="slug" placeholder="{{ __('Slug') }}" required="" value="{{$data->slug}}">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
															
														</div>
													</div>
													<div class="col-lg-7">
														<button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
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