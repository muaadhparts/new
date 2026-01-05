<div class="modal fade" id="add-catalogItem" tabindex="-1" role="dialog" aria-labelledby="billing-details-edit"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="submit-loader">
                <img src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
            </div>
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add CatalogItem') }} |  <code class="text-center show_merchant_message">
                    
                </code></h5>
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
                                        <form id="show-catalogItem" action="{{ route('operator-purchase-catalogItem-submit') }}"
                                            method="POST" enctype="multipart/form-data">
                                            {{csrf_field()}}
                                            <input type="hidden" name="merchant_id" id="add_merchant_id" value="">
                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('PART_NUMBER') }} *</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <input type="text" class="form-control" name="part_number"
                                                        placeholder="{{ __('Enter CatalogItem Part_Number') }}" required=""
                                                        value="">
                                                </div>
                                            </div>

                                            <input type="hidden" name="purchase_id" id="order_id" value="{{ $purchase->id }}">

                                            <div class="row">
                                                <div class="col-lg-4">
                                                    <div class="left-area">

                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <button class="btn btn-primary mt-0" type="submit">{{
                                                        __('Submit') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row d-block text-center" id="catalogItem-show">

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