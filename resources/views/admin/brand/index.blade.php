@extends('layouts.admin')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('BRAND') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Brands') }} ({{ __('Catalogs') }})</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Catalog Settings') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('admin-brand-index') }}">{{ __('Brands') }}</a>
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
                                    <th>{{ __('Photo') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Catalogs Count') }}</th>
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
@include('components.admin.modal-delete', ['message' => __('You are about to delete this Brand.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin-brand-datatables') }}',
        columns: [
            { data: 'photo', name: 'photo', searchable: false, orderable: false },
            { data: 'name', name: 'name' },
            { data: 'catalogs_count', name: 'catalogs_count', searchable: false, orderable: false },
            { data: 'action', searchable: false, orderable: false }
        ],
        language: {
            processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
        }
    });

    $(function() {
        $(".btn-area").append('<div class="col-sm-4 table-contents">' +
            '<a class="add-btn" data-href="{{ route('admin-brand-create') }}" id="add-data" data-bs-toggle="modal" data-bs-target="#modal1">' +
            '<i class="fas fa-plus"></i> <span class="remove-mobile">{{ __("Add New Brand") }}<span>' +
            '</a>' +
            '</div>');
    });

})(jQuery);
</script>
@endsection
