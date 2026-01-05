@extends('layouts.load')
@section('content')

						<div class="content-area">
							<div class="add-catalogItem-content">
								<div class="row">
									<div class="col-lg-12">
										<div class="catalogItem-description">
											<div class="body-area">

                                            @include('alerts.operator.form-error')

                                            <form id="muaadhformdata" action="{{route('operator-merchant-membership-plan-store',$data->id)}}" method="POST" enctype="multipart/form-data">

                                                {{csrf_field()}}

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Current Plan') }} :</h4>
														</div>
													</div>
                                                    <div class="col-lg-7">
                                                            <h5 class="heading title">{{ $data->membershipPlans()->orderBy('id','desc')->first() ? $data->membershipPlans()->orderBy('id','desc')->first()->title : 'No Plan' }} </h5>
                                                    </div>
												</div>

												<div class="row">
													<div class="col-lg-4">
														<div class="left-area">
																<h4 class="heading">{{ __('Choose Plan') }} :</h4>
														</div>
													</div>
                                                    <div class="col-lg-7">
                                                        <select name="plan_id" required="">
                                                            @foreach(DB::table('membership_plans')->get() as $planData)
                                                                <option value="{{ $planData->id }}">{{ $planData->title }}</option>
                                                            @endforeach
                                                        </select>
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
