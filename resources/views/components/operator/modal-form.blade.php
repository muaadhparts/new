{{--
    Generic Form Modal Component - نظام موحد

    Usage:
    @include('components.operator.modal-form', [
        'id' => 'modal1',
        'name' => __('Add New Item')
    ])
--}}

@php
    $id = $id ?? 'modal1';
    $name = $name ?? '';
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-name" id="{{ $id }}-label">{{ $name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                {{-- Content will be loaded dynamically --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
