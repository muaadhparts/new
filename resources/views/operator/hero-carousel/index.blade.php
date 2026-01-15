@extends('layouts.operator')

@section('content')
<input type="hidden" id="headerdata" value="{{ __('HERO CAROUSEL') }}">
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Hero Carousels') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Home Page Settings') }} </a>
                    </li>
                    <li>
                        <a href="{{ route('operator-hero-carousel-index') }}">{{ __('Hero Carousels') }}</a>
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
                                    <th>{{ __('Featured Image') }}</th>
                                    <th width="40%">{{ __('Title') }}</th>
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
@include('components.operator.modal-delete', ['message' => __('You are about to delete this Hero Carousel.')])

@endsection

@section('scripts')
<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#muaadhtable').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route('operator-hero-carousel-datatables') }}',
        columns: [
            { data: 'photo', name: 'photo', searchable: false, orderable: false },
            { data: 'title_text', name: 'title_text' },
            { data: 'action', searchable: false, orderable: false }
        ],
        language: {
            processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
        }
    });

    $(function() {
        $(".btn-area").append('<div class="col-sm-4 table-contents">' +
            '<a class="add-btn" href="{{ route('operator-hero-carousel-create') }}">' +
            '<i class="fas fa-plus"></i> {{ __("Add New Hero Carousel") }}' +
            '</a>' +
            '</div>');
    });

})(jQuery);
</script>
@endsection
