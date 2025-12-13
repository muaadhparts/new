{{--
    Generic Form Modal Component - لتوحيد Modal الإضافة/التعديل المكرر

    Usage:
    @include('components.admin.modal-form', [
        'id' => 'modal1',
        'title' => __('Add New Item')
    ])
--}}

@php
    $id = $id ?? 'modal1';
    $title = $title ?? '';
    $gs = $gs ?? \App\Models\Generalsetting::first();
@endphp

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="submit-loader">
                <img src="{{ asset('assets/images/' . ($gs->admin_loader ?? 'loader.gif')) }}" alt="{{ __('Loading...') }}">
            </div>
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
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
