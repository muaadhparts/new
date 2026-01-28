@extends('layouts.operator')

@section('content')
    <input type="hidden" id="headerdata" value="{{ __('CATALOG ITEM') }}">
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('Catalog Items') }}</h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Catalog Items') }} </a>
                        </li>
                        <li>
                            <a href="{{ route('operator-catalog-item-index') }}">{{ __('All Catalog Items') }}</a>
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
                        <div class="alert alert-danger validation" style="display: none;">
                            <button type="button" class="close alert-close"><span>×</span></button>
                            <p class="text-left"></p>
                        </div>

                        <div class="table-responsive">
                            <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('Image') }}</th>
                                        <th>{{ __('Part Number') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Brand') }}</th>
                                        <th>{{ __('Offers') }}</th>
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




    {{-- CATALOG MODAL --}}

    <div class="modal fade" id="catalog-modal" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header d-block text-center">
                    <h4 class="modal-name d-inline-block">{{ __('Update Status') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <p class="text-center">{{ __('You are about to change the status of this CatalogItem.') }}</p>
                    <p class="text-center">{{ __('Do you want to proceed?') }}</p>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <a class="btn btn-success btn-ok">{{ __('Proceed') }}</a>
                </div>

            </div>
        </div>
    </div>

    {{-- CATALOG MODAL ENDS --}}

    {{-- DELETE MODAL --}}

    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header d-block text-center">
                    <h4 class="modal-name d-inline-block">{{ __('Confirm Delete') }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <p class="text-center">{{ __('You are about to delete this CatalogItem.') }}</p>
                    <p class="text-center">{{ __('Do you want to proceed?') }}</p>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form action="" class="d-inline delete-form" method="POST">
                        <input type="hidden" name="_method" value="delete" />
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- DELETE MODAL ENDS --}}

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
                ajax: '{{ route('operator-catalog-item-datatables') }}',
                columns: [
                    { data: 'photo', name: 'photo', searchable: false, orderable: false },
                    { data: 'part_number', name: 'part_number' },
                    { data: 'name', name: 'name' },
                    { data: 'brand', name: 'brand', searchable: false },
                    { data: 'offers_count', name: 'offers_count', searchable: false, orderable: false },
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
                    '<a class="add-btn" href="{{ route('operator-catalog-item-create', 'items') }}">' +
                    '<i class="fas fa-plus"></i> <span class="remove-mobile">{{ __('Add CatalogItem') }}<span>' +
                    '</a>' +
                    '</div>');
            });

            // Override delete form submission to handle errors
            $("#confirm-delete .delete-form").off('submit').on("submit", function (e) {
                e.preventDefault();
                var $form = $(this);

                $.ajax({
                    method: "POST",
                    url: $form.attr('action'),
                    data: new FormData(this),
                    dataType: "JSON",
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function (data) {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirm-delete')).hide();
                        $("#muaadhtable").DataTable().ajax.reload();
                        $(".alert-danger").hide();
                        $(".alert-success").show();
                        $(".alert-success p").html(data);
                    },
                    error: function(xhr) {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirm-delete')).hide();
                        var response = xhr.responseJSON;
                        var message = response && response.message ? response.message : '{{ __("Delete failed") }}';
                        $(".alert-success").hide();
                        $(".alert-danger").show();
                        $(".alert-danger p").html(message);
                    }
                });
            });

        })(jQuery);
    </script>

    {{-- DATA TABLE ENDS --}}
@endsection
