@extends('layouts.admin')

@section('content')
    <input type="hidden" id="headerdata" value="{{ __('REPORT') }}">
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('Reports') }}</h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('CatalogItem Discussion') }} </a>
                        </li>
                        <li>
                            <a href="{{ route('admin-report-index') }}">{{ __('Reports') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="catalogItem-area">
            <div class="row">
                <div class="col-lg-12">
                    <div class="heading-area">
                        <h4 class="title">
                            {{ __('CatalogItem Report') }} :
                        </h4>
                        <div class="action-list">
                            <select
                                class="process select droplinks {{ $gs->is_report == 1 ? 'drop-success' : 'drop-danger' }}">
                                <option data-val="1" value="{{ route('admin-gs-status', ['is_report', 1]) }}"
                                    {{ $gs->is_report == 1 ? 'selected' : '' }}>{{ __('Activated') }}</option>
                                <option data-val="0" value="{{ route('admin-gs-status', ['is_report', 0]) }}"
                                    {{ $gs->is_report == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mr-table allproduct">
                        @include('alerts.admin.form-success')
                        <div class="table-responsive">
                            <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('CatalogItem') }}</th>
                                        <th>{{ __('Brand') }}</th>
                                        <th>{{ __('Quality Brand') }}</th>
                                        <th>{{ __('Merchant') }}</th>
                                        <th>{{ __('Reporter') }}</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Date & Time') }}</th>
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

    {{-- ADD / EDIT MODAL --}}

    <div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="submit-loader">
                    <img src="{{ asset('assets/images/' . $gs->admin_loader) }}" alt="">
                </div>
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>

    </div>

    {{-- ADD / EDIT MODAL ENDS --}}


    {{-- DELETE MODAL --}}

    <div class="modal fade" id="confirm-delete">
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header text-center">
                    <h4 class="modal-title w-100">{{ __('Confirm Delete') }}</h4>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <p class="text-center">{{ __('You are about to delete this Report.') }}</p>
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
                ajax: '{{ route('admin-report-datatables') }}',
                columns: [
                    { data: 'catalogItem', name: 'catalogItem', searchable: false, orderable: false },
                    { data: 'brand', name: 'brand' },
                    { data: 'quality_brand', name: 'quality_brand' },
                    { data: 'merchant', name: 'merchant' },
                    { data: 'reporter', name: 'reporter' },
                    { data: 'title', name: 'title' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', searchable: false, orderable: false }
                ],
                language: {
                    processing: '<img src="{{ asset('assets/images/' . $gs->admin_loader) }}">'
                }
            });

        })(jQuery);
    </script>

    {{-- DATA TABLE --}}
@endsection
