@extends('layouts.admin')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('SUB CATEGORY') }}">
<input type="hidden" id="attribute_data" value="{{ __('ADD NEW ATTRIBUTE') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Sub Categories') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li><a href="javascript:;">{{ __('Manage Categories') }}</a></li>
                    <li>
                        <a href="{{ route('admin-subcat-index') }}">{{ __('Sub Categories') }}</a>
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
                                    <th>{{ __('Main Category') }}</th>
                                    <th>{{ __('Sub Category') }}</th>
                                    <th>{{ __('Slug') }}</th>
                                    <th>{{ __('Attributes') }}</th>
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
@include('components.admin.modal-form', ['id' => 'attribute'])
@include('components.admin.modal-delete', ['message' => __('You are about to delete this Category. Everything under this category will be deleted.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin-subcat-datatables') }}',
        columns: [
            { data: 'category', searchable: false, orderable: false },
            { data: 'name', name: 'name' },
            { data: 'slug', name: 'slug' },
            { data: 'attributes', name: 'attributes', searchable: false, orderable: false },
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
            '<a class="add-btn" data-href="{{ route('admin-subcat-create') }}" id="add-data" data-bs-toggle="modal" data-bs-target="#modal1">' +
            '<i class="fas fa-plus"></i> <span class="remove-mobile">{{ __("Add New") }}<span>' +
            '</a>' +
            '</div>');
    });

})(jQuery);
</script>
@endsection
