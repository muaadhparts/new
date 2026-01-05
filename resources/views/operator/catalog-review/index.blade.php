@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('CATALOG REVIEW') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Catalog Reviews') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Catalog') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('operator-catalog-review-index') }}">{{ __('Catalog Reviews') }}</a>
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
                    <div class="table-responsive">
                        <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>{{ __('CatalogItem') }}</th>
                                    <th>{{ __('Brand') }}</th>
                                    <th>{{ __('Quality Brand') }}</th>
                                    <th>{{ __('Merchant') }}</th>
                                    <th>{{ __('Reviewer') }}</th>
                                    <th>{{ __('Rating') }}</th>
                                    <th>{{ __('Review') }}</th>
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
@include('components.operator.modal-form', ['id' => 'modal1'])
@include('components.operator.modal-delete', ['message' => __('You are about to delete this Catalog Review.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('operator-catalog-review-datatables') }}',
        columns: [
            { data: 'catalogItem', name: 'catalogItem', searchable: false, orderable: false },
            { data: 'brand', name: 'brand' },
            { data: 'quality_brand', name: 'quality_brand' },
            { data: 'merchant', name: 'merchant' },
            { data: 'reviewer', name: 'reviewer' },
            { data: 'rating', name: 'rating' },
            { data: 'review', name: 'review' },
            { data: 'action', searchable: false, orderable: false }
        ],
        language: {
            processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
        }
    });

})(jQuery);
</script>
@endsection
