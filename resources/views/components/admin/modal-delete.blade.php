{{--
    Delete Confirmation Modal Component - لتوحيد Modal الحذف المكرر

    Usage:
    @include('components.admin.modal-delete')

    Or with custom message:
    @include('components.admin.modal-delete', [
        'message' => __('You are about to delete this brand.')
    ])
--}}

@php
    $id = $id ?? 'confirm-delete';
    $message = $message ?? __('You are about to delete this item.');
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header d-block text-center">
                <h4 class="modal-title d-inline-block">{{ __('Confirm Delete') }}</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-center">{{ $message }}</p>
                <p class="text-center">{{ __('Do you want to proceed?') }}</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <form action="" class="d-inline delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
