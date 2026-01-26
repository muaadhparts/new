<div class="modal fade" id="shipping-details-edit" tabindex="-1" role="dialog" aria-labelledby="shipping-details-edit" aria-hidden="true">
										
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="submit-loader">
                <img  src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
            </div>
            <div class="modal-header">
                <h5 class="modal-name">{{ __('Shipping Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    
                </button>
            </div>
            <div class="modal-body">

                <div class="content-area">

                    <div class="add-catalogItem-content1">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="catalogItem-description">
                                    <div class="body-area">
                                    <form  action="{{route('operator-purchase-update',$purchase->id)}}" method="POST" enctype="multipart/form-data">
                                        {{csrf_field()}}

                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Name') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <input type="text" class="form-control" name="shipping_name" placeholder="{{ __('Name') }}" required="" value="{{$purchase->customer_name}}">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Email') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <input type="email" class="form-control" name="shipping_email" placeholder="{{ __('Email') }}" required="" value="{{$purchase->customer_email}}">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Phone') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <input type="text" class="form-control" name="shipping_phone" placeholder="{{ __('Phone') }}" required="" value="{{$purchase->customer_phone}}">
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Address') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <input type="text" class="form-control" name="shipping_address" placeholder="{{ __('Address') }}" required="" value="{{$purchase->customer_address}}">
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('City') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <input type="text" class="form-control" name="shipping_city" placeholder="{{ __('City') }}" required="" value="{{$purchase->customer_city}}">
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('State') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <input type="text" class="form-control" name="shipping_state" placeholder="{{ __('State') }}" required="" value="{{$purchase->customer_state}}">
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Country') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <select type="text" class="form-control" name="shipping_country" required="">
                                                    <option value="">{{ __('Select Country') }}</option>
                                                    {!! $countriesHtml !!}
                                                </select>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-4">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Postal Code') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-7">
                                                <input type="text" class="form-control" name="shipping_zip" placeholder="{{ __('Postal Code') }}" required="" value="{{$purchase->customer_zip}}">
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

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>

</div>