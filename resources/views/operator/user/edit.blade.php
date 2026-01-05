@extends('layouts.load')
@section('content')

						<div class="content-area">
							<div class="add-catalogItem-content1">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area">
                        					@include('alerts.operator.form-error') 
											<form id="muaadhformdata" action="{{ route('operator-user-edit',$data->id) }}" method="POST" enctype="multipart/form-data">
												{{csrf_field()}}

						                        <div class="row">
						                          <div class="col-lg-4">
						                            <div class="left-area">
						                                <h4 class="heading">{{ __("Customer Profile Image") }} *</h4>
						                            </div>
						                          </div>
						                          <div class="col-lg-7">
						                            <div class="img-upload">
						                            	@if($data->is_provider == 1)
						                                <div id="image-preview" class="img-preview" style="background: url({{ $data->photo ? asset($data->photo):asset('assets/images/noimage.png') }});">
						                            	@else
						                                <div id="image-preview" class="img-preview" style="background: url({{ $data->photo ? asset('assets/images/users/'.$data->photo):asset('assets/images/noimage.png') }});">
						                                @endif
						                                @if($data->is_provider != 1)
						                                    <label for="image-upload" class="img-label" id="image-label"><i class="icofont-upload-alt"></i>{{ __("Upload Image") }}</label>
						                                    <input type="file" name="photo" class="img-upload" id="image-upload">
						                                @endif
						                                  </div>
						                                  <p class="text">{{ __("Prefered Size: (600x600) or Square Sized Image") }}</p>
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
														<input type="text" class="form-control" name="name" placeholder="{{ __("User Name") }}" required="" value="{{ $data->name }}">
													</div>
												</div>


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Email") }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="email" class="form-control" name="email" placeholder="{{ __("Email Address") }}" value="{{ $data->email }}">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Phone") }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="phone" placeholder="{{ __("Phone Number") }}" required="" value="{{ $data->phone }}">
													</div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Address") }} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="address" placeholder="{{ __("Address") }}" required="" value="{{ $data->address }}">
													</div>
												</div>


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("City") }} </h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="city" placeholder="{{ __("City") }}" value="{{ $data->city }}">
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
                                                            @foreach (DB::table('countries')->get() as $dt)
                                                                <option value="{{ $dt->country_name }}" {{ $data->country == $dt->country_name ? 'selected' : '' }}>
                                                                    {{ $dt->country_name }}
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
														<input type="text" class="form-control" name="fax" placeholder="{{ __("Fax") }}" value="{{ $data->fax }}">
													</div>
												</div>


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __("Postal Code") }} </h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="text" class="form-control" name="zip" placeholder="{{ __("Postal Code") }}" value="{{ $data->zip }}">
													</div>
												</div>


												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{__('Password')}} *</h4>
														</div>
													</div>
													<div class="col-lg-7">
														<input type="password" class="form-control" name="password" placeholder="{{__('Enter Password')}}" value="">
													</div>
												</div>


						                        <div class="row">
						                          <div class="col-lg-4">
						                            <div class="left-area">
						                              
						                            </div>
						                          </div>
						                          <div class="col-lg-7">
						                            <button class="btn btn-primary" type="submit">{{ __("Save") }}</button>
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