@extends('layouts.vendor')

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex gap-4 flex-wrap align-items-center custom-gap-sm-2">
                <h4 class="text-capitalize">@lang('Coupons')</h4>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('vendor-coupon-create') }}"
                        class="template-btn md-btn black-btn data-table-btn">
                        <i class="fas fa-plus"></i> @lang('Add New Coupon')
                    </a>
                </div>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor-coupon-index') }}" class="text-capitalize"> @lang('Coupons') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Table area start  -->
        <div class="vendor-table-wrapper">
            <div class="user-table table-responsive position-relative">

                <table class="gs-data-table w-100" id="coupon-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Used') }}</th>
                            <th>{{ __('Start Date') }}</th>
                            <th>{{ __('End Date') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-center">{{ __('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($datas as $data)
                            <tr>
                                <td>
                                    <span class="badge bg-primary">{{ $data->code }}</span>
                                </td>
                                <td>
                                    @if($data->type == 0)
                                        <span class="badge bg-info">{{ __('Percentage') }}</span>
                                    @else
                                        <span class="badge bg-success">{{ __('Fixed Amount') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($data->type == 0)
                                        {{ $data->price }}%
                                    @else
                                        {{ \PriceHelper::showAdminCurrencyPrice($data->price * $curr->value) }}
                                    @endif
                                </td>
                                <td>{{ $data->used ?? 0 }}</td>
                                <td>{{ $data->start_date }}</td>
                                <td>{{ $data->end_date }}</td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input status-toggle" type="checkbox"
                                            data-id="{{ $data->id }}"
                                            {{ $data->status == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="{{ route('vendor-coupon-edit', $data->id) }}"
                                            class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                            data-id="{{ $data->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#confirm-delete"
                                            title="{{ __('Delete') }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">{{ __('No coupons found') }}</p>
                                        <a href="{{ route('vendor-coupon-create') }}" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus"></i> {{ __('Create Your First Coupon') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
        <!-- Table area end  -->
    </div>

    {{-- DELETE MODAL --}}
    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Confirm Delete') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <p>{{ __('You are about to delete this Coupon.') }}</p>
                    <p class="text-muted">{{ __('Do you want to proceed?') }}</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form action="" class="d-inline delete-form" method="POST" id="deleteForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- DELETE MODAL ENDS --}}

@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize DataTable
        $('#coupon-table').DataTable({
            responsive: true,
            language: {
                search: "{{ __('Search') }}:",
                lengthMenu: "{{ __('Show') }} _MENU_ {{ __('entries') }}",
                info: "{{ __('Showing') }} _START_ {{ __('to') }} _END_ {{ __('of') }} _TOTAL_ {{ __('entries') }}",
                infoEmpty: "{{ __('Showing 0 to 0 of 0 entries') }}",
                infoFiltered: "({{ __('filtered from') }} _MAX_ {{ __('total entries') }})",
                paginate: {
                    first: "{{ __('First') }}",
                    last: "{{ __('Last') }}",
                    next: "{{ __('Next') }}",
                    previous: "{{ __('Previous') }}"
                },
                emptyTable: "{{ __('No data available in table') }}"
            }
        });

        // Status Toggle
        $('.status-toggle').on('change', function() {
            var id = $(this).data('id');
            var status = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                url: '{{ url("vendor/coupon/status") }}/' + id + '/' + status,
                type: 'GET',
                success: function(response) {
                    toastr.success(response);
                },
                error: function() {
                    toastr.error('{{ __("Something went wrong!") }}');
                }
            });
        });

        // Delete Button Click
        $('.delete-btn').on('click', function() {
            var id = $(this).data('id');
            $('#deleteForm').attr('action', '{{ url("vendor/coupon/delete") }}/' + id);
        });

        // Delete Form Submit
        $('#deleteForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);

            $.ajax({
                url: form.attr('action'),
                type: 'DELETE',
                data: form.serialize(),
                success: function(response) {
                    $('#confirm-delete').modal('hide');
                    toastr.success(response);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function() {
                    toastr.error('{{ __("Something went wrong!") }}');
                }
            });
        });
    });
</script>
@endsection
