@extends('layouts.admin')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('COMMENT') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Comments') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Product Discussion') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('admin-comment-index') }}">{{ __('Comments') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="product-area">
        <div class="row">
            <div class="col-lg-12">
                <div class="heading-area">
                    <h4 class="title">{{ __('Product Comment') }} :</h4>
                    <div class="action-list">
                        <select class="process select droplinks {{ $gs->is_comment == 1 ? 'drop-success' : 'drop-danger' }}">
                            <option data-val="1" value="{{ route('admin-gs-status', ['is_comment', 1]) }}" {{ $gs->is_comment == 1 ? 'selected' : '' }}>{{ __('Activated') }}</option>
                            <option data-val="0" value="{{ route('admin-gs-status', ['is_comment', 0]) }}" {{ $gs->is_comment == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}</option>
                        </select>
                    </div>
                </div>
                <div class="mr-table allproduct">
                    @include('alerts.admin.form-success')
                    <div class="table-responsive">
                        <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('Brand') }}</th>
                                    <th>{{ __('Quality Brand') }}</th>
                                    <th>{{ __('Vendor') }}</th>
                                    <th>{{ __('Commenter') }}</th>
                                    <th>{{ __('Comment') }}</th>
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
@include('components.admin.modal-delete', ['message' => __('You are about to delete this Comment.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin-comment-datatables') }}',
        columns: [
            { data: 'product', name: 'product', searchable: false, orderable: false },
            { data: 'brand', name: 'brand' },
            { data: 'quality_brand', name: 'quality_brand' },
            { data: 'vendor', name: 'vendor' },
            { data: 'commenter', name: 'commenter' },
            { data: 'text', name: 'text' },
            { data: 'action', searchable: false, orderable: false }
        ],
        language: {
            processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
        }
    });

})(jQuery);
</script>
@endsection
