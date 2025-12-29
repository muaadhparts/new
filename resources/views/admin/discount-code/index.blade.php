@extends('layouts.admin')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('DISCOUNT CODE') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Discount Codes') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('admin-discount-code-index') }}">{{ __('Discount Codes') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="product-area">
        <div class="row">
            <div class="col-lg-12">
                <div class="mr-table allproduct">
                    @include('alerts.admin.form-success')
                    <div class="table-responsive">
                        <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Used') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Status') }}</th>
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

{{-- MODALS --}}
@include('components.admin.modal-form', ['id' => 'modal1'])
@include('components.admin.modal-delete', ['message' => __('You are about to delete this Discount Code.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin-discount-code-datatables') }}',
        columns: [
            { data: 'code', name: 'code' },
            { data: 'type', name: 'type' },
            { data: 'price', name: 'price' },
            { data: 'used', name: 'used' },
            { data: 'vendor', name: 'vendor' },
            { data: 'status', searchable: false, orderable: false },
            { data: 'action', searchable: false, orderable: false }
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
            '<a class="add-btn" href="{{ route('admin-discount-code-create') }}">' +
            '<i class="fas fa-plus"></i> <span class="remove-mobile">{{ __("Add New") }}<span>' +
            '</a>' +
            '</div>');
    });

})(jQuery);
</script>
@endsection
