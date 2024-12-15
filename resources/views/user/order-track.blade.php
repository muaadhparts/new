@extends('layouts.front')
@section('content')
<div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
    <div class="container">
        <div class="d-flex">
            <!-- sidebar -->
            @include('includes.user.sidebar')
            <!-- main content -->
            <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                <div class="gs-order-track-section">
                    <div class="gs-order-track-title">
                        <h3>@lang('Track Your Order')</h3>
                        <p>@lang('Have an order? Want to know where your order is now?')</p>
                    </div>
                    <div class="order-track-area">
                        <div class="order-track-title">
                            <h4>@lang('Enter Your Order Number')</h4>
                        </div>
                        <form action="#">
                            <div class="form-group">
                                <label for="code">@lang('Order number')</label>
                                <input type="text" class="form-control" id="code"
                                    placeholder="@lang('Your Order Number')">
                                <div class="row justify-content-end">
                                    <div class="col-auto btn-width">
                                        <button type="submit" id="t-form" class="template-btn btn-forms">
                                            @lang('View Tracking')
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<div class="modal gs-modal fade" id="order-tracking-modal" role="dialog" data-bs-backdrop="static"
    data-bs-keyboard="false" aria-labelledby="order-tracking-modal" aria-hidden="true">
    <div class="modal-dialog assign-rider-modal-dialog modal-dialog-centered">
        <div class="modal-content assign-rider-modal-content form-group">
            <div class="modal-header w-100">
                <h4 class="title">{{ __('Order Tracking') }}</h4>
                <button type="button" data-bs-dismiss="modal">
                    <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                </button>

            </div>
            <div class="modal-body p-0" id="order-track">
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script type="text/javascript">
    $(document).on('click', 'form #t-form', function (e) {
        e.preventDefault();
        var code = $('#code').val();
        $('#order-track').load('{{ url('user/order/trackings/') }}/' + code);
        $('#order-tracking-modal').modal('show');
    })
</script>
@endsection