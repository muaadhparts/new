@extends('layouts.operator')

@section('content')
    <input type="hidden" id="headerdata" value="{{ __('PURCHASE') }}">

    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('All Purchases') }}</h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Purchases') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('operator-purchases-all') }}">{{ __('All Purchases') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="catalogItem-area">
            <div class="row">
                <div class="col-lg-12">
                    <div class="mr-table allproduct">
                        @include('alerts.operator.form-success')
                        @include('alerts.form-success')
                        <div class="table-responsive">
                            <div class="gocover"
                                style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                            </div>
                            <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('Customer Email') }}</th>
                                        <th>{{ __('Purchase Number') }}</th>
                                        <th>{{ __('Merchant') }}</th>
                                        <th>{{ __('Total Qty') }}</th>
                                        <th>{{ __('Total Cost') }}</th>
                                        <th>{{ __('Options') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PURCHASE MODAL --}}
    <div class="modal fade" id="confirm-delete1" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Update Status') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">{{ __("You are about to update the purchase's Status.") }}</p>
                    <p class="text-center">{{ __('Do you want to proceed?') }}</p>
                    <input type="hidden" id="t-add" value="{{ route('operator-purchase-timeline-add') }}">
                    <input type="hidden" id="t-id" value="">
                    <input type="hidden" id="t-title" value="">
                    <textarea class="form-control" placeholder="{{ __('Enter Your Tracking Note (Optional)') }}" id="t-txt" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-success btn-ok">{{ __('Proceed') }}</button>
                </div>
            </div>
        </div>
    </div>
    {{-- PURCHASE MODAL ENDS --}}



    {{-- MESSAGE MODAL --}}
    <div class="modal fade" id="merchantform" tabindex="-1" role="dialog" aria-labelledby="merchantformLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="merchantformLabel">{{ __('Send Email') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="emailreply">
                        @csrf
                        <div class="mb-3">
                            <input type="email" class="form-control eml-val" id="eml" name="to" placeholder="{{ __('Email') }} *" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="subj" name="subject" placeholder="{{ __('Subject') }} *" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="message" id="msg" placeholder="{{ __('Your Message') }} *" rows="4" required></textarea>
                        </div>
                        <button class="btn btn-primary w-100" id="emlsub" type="submit">{{ __('Send Email') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- MESSAGE MODAL ENDS --}}

    {{-- ADD / EDIT MODAL --}}
    <div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
    {{-- ADD / EDIT MODAL ENDS --}}
@endsection

@section('scripts')
    {{-- DATA TABLE --}}

    <script type="text/javascript">
        (function($) {
            "use strict";

            var table = $('#muaadhtable').DataTable({
                ordering: false,
                processing: true,
                serverSide: true,
                ajax: '{{ route('operator-purchase-datatables', 'none') }}',
                columns: [{
                        data: 'customer_email',
                        name: 'customer_email'
                    },
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'merchants',
                        name: 'merchants'
                    },
                    {
                        data: 'totalQty',
                        name: 'totalQty'
                    },
                    {
                        data: 'pay_amount',
                        name: 'pay_amount'
                    },
                    {
                        data: 'action',
                        searchable: false,
                        orderable: false
                    }
                ],
                language: {
                    processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
                },
                drawCallback: function(settings) {
                    $('.select').niceSelect();
                }
            });

            $(function() {
                $(".btn-area").append('<div class="col-sm-4 table-contents">' +
                    '<a class="add-btn" href="{{ route('operator-purchase-create') }}">' +
                    '<i class="fas fa-plus"></i> <span class="remove-mobile">{{ __('Add a Purchase') }}<span>' +
                    '</a>' +
                    '</div>');
            });

        })(jQuery);
    </script>

    {{-- DATA TABLE --}}
@endsection
