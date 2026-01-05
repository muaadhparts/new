@extends('layouts.load')
@section('content')

						<div class="content-area">
							<div class="add-catalogItem-content1">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area">
                        					@include('alerts.operator.form-error') 
											<form id="muaadhformdata" action="{{ route('operator-user-store') }}" method="POST" enctype="multipart/form-data">
												{{csrf_field()}}

						                        <div class="row">
						                          <div class="col-lg-4">
						                            <div class="left-area">
						                                <h4 class="heading">{{ __("Customer Profile Image") }} *</h4>
						                            </div>
						                          </div>
						                          <div class="col-lg-7">
                                                    <div class="img-upload">
                                                        <div id="image-preview" class="img-preview" style="background: url({{ asset('assets/operator/images/upload.png') }});">
                                                            <label for="image-upload" class="img-label" id="image-label"><i class="icofont-upload-alt"></i>{{ __('Upload Image') }}</label>
                                                            <input type="file" name="photo" class="img-upload" id="image-upload">
                                                          </div>
                                                    </div>
						                          </div>
						                        </div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Name") }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="name" placeholder="{{ __("User Name") }}" required="" value="">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Email") }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="email" class="form-control" name="email" placeholder="{{ __("Email Address") }}" value="">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Phone") }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="phone" placeholder="{{ __("Phone Number") }}" required="" value="">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Address") }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="address" placeholder="{{ __("Address") }}" required="" value="">
													</div>
                                                </div>
                                                
												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("City") }} </h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="city" placeholder="{{ __("City") }}" value="">
													</div>
												</div>


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("State") }} </h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="state" placeholder="{{ __("State") }}" value="">
													</div>
												</div>


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Country") }} </h4>
														</div>
													</div>
													<div class="col-lg-7">
                                                        <select class="form-control" name="country" required>
                                                            <option value="">{{ __('Select Country') }}</option>
                                                            @foreach (DB::table('countries')->get() as $data)
                                                                <option value="{{ $data->country_name }}">
                                                                    {{ $data->country_name }}
                                                                </option>		
                                                             @endforeach
                                                        </select>
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Fax") }} </h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="fax" placeholder="{{ __("Fax") }}" value="">
													</div>
												</div>


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Postal Code") }} </h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="zip" placeholder="{{ __("Postal Code") }}" value="">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{__('Password')}} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="password" class="form-control" name="password" placeholder="{{__('Enter Password')}}" value="" required>
													</div>
												</div>


						                        <div class="row">
						                          <div class="col-lg-4">
						                            <div class="left-area">
						                              
						                            </div>
						                          </div>
						                          <div class="col-lg-7">
						                            <button class="btn btn-primary" type="submit">{{ __("Create Customer") }}</button>
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