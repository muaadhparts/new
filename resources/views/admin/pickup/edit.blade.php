@extends('layouts.load')
@section('content')

						<div class="content-area">
							<div class="add-catalogItem-content1">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area">
											@include('alerts.admin.form-error') 
											<form id="muaadhformdata" action="{{route('admin-pick-update',$data->id)}}" method="POST" enctype="multipart/form-data">
												{{csrf_field()}}


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Location') }} *</h4>
																<p class="sub-heading">{{ __('(In Any Language)') }}</p>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="location" placeholder="{{ __('Location') }}" required="" value="{{$data->location}}">
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