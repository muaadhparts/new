@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('Help Article') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Help Article') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Menu Page Settings') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('operator-help-article-index') }}">{{ __('Help Article') }}</a>
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
                                    <th width="30%">{{ __('Help Article Name') }}</th>
                                    <th width="50%">{{ __('Help Article Details') }}</th>
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
@include('components.operator.modal-delete', ['message' => __('You are about to delete this Help Article.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('operator-help-article-datatables') }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'details', name: 'details' },
            { data: 'action', searchable: false, orderable: false }
        ],
        language: {
            processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
        }
    });

    $(function() {
        $(".btn-area").append('<div class="col-sm-4 table-contents">' +
            '<a class="add-btn" href="{{ route('operator-help-article-create') }}">' +
            '<i class="fas fa-plus"></i> {{ __("Add New Help Article") }}' +
            '</a>' +
            '</div>');
    });

})(jQuery);
</script>
@endsection
