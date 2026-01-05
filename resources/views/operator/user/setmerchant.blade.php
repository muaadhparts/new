@extends('layouts.load')
@section('content')

						<div class="content-area">
							<div class="add-catalogItem-content1">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area">

                                            @include('alerts.operator.form-error') 
                                            
                                            <form id="muaadhformdata" action="{{route('operator-user-merchant-update',$data->id)}}" method="POST" enctype="multipart/form-data">
                                                
                                                {{csrf_field()}}
                                                
                                                <div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Shop Name') }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="shop_name" placeholder="{{ __('Shop Name') }}" required="" value="">
													</div>
												</div>

                                                <div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Owner Name') }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="owner_name" placeholder="{{ __('Owner Name') }}" required="" value="">
													</div>
												</div>

                                                <div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Shop Number') }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="shop_number" placeholder="{{ __('Shop Number') }}" required="" value="">
													</div>
												</div>

                                                <div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Shop Address') }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="shop_address" placeholder="{{ __('Shop Address') }}" required="" value="">
													</div>
												</div>

                                                <div class="row">
													<div class="col-lg-4">
														<div class="left-area">
                                                                <h4 class="heading">{{ __('Registration Number') }} *</h4>
                                                                <p class="sub-heading">{{ __('(Optional)') }}</p>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="reg_number" placeholder="{{ __('Registration Number') }}" value="">
													</div>
												</div>


                                                <div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Shop Details') }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
                                                        <textarea name="shop_address" class="form-control" placeholder="{{ __('Shop Details') }}" required></textarea>
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
															
														</div>
													</div>
													<div class="col-lg-7">
														<button class="btn btn-primary" type="submit">{{ __('Submit') }}</button>
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