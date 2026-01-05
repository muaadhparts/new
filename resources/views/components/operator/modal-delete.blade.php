{{--
    Delete Confirmation Modal Component - نظام موحد

    Usage:
    @include('components.operator.modal-delete')

    Or with custom message:
    @include('components.operator.modal-delete', [
        'message' => __('You are about to delete this brand.')
    ])
--}}

@php
    $id = $id ?? 'confirm-delete';
    $message = $message ?? __('You are about to delete this item.');
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Confirm Delete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body text-center">
                <p>{{ $message }}</p>
                <p>{{ __('Do you want to proceed?') }}</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <form action="" class="d-inline delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
