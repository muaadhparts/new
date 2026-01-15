@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('LINK') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Network Presence') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('General Settings') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('operator-network-presence-index') }}">{{ __('Network Presence') }}</a>
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
                                    <th>{{ __('Link') }}</th>
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
@include('components.operator.modal-form', ['id' => 'modal1'])
@include('components.operator.modal-delete', ['message' => __('You are about to delete this Link.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('operator-network-presence-datatables') }}',
        columns: [
            { data: 'link', name: 'link' },
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
            '<a class="add-btn" href="{{ route('operator-network-presence-create') }}" id="add-data">' +
            '<i class="fas fa-plus"></i> {{ __("Add New Link") }}' +
            '</a>' +
            '</div>');
    });

})(jQuery);
</script>
@endsection
