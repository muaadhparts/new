{{--
    Submit Button Component - لتوحيد زر الإرسال المكرر

    Usage:
    @include('components.admin.submit-button', [
        'label' => __('Create Brand')
    ])
--}}

@php
    $label = $label ?? __('Submit');
    $class = $class ?? 'btn btn-primary';
    $colLeft = $colLeft ?? 'col-lg-4';
    $colRight = $colRight ?? 'col-lg-7';
@endphp

<div class="row">
    <div class="{{ $colLeft }}">
        <div class="left-area"></div>
    </div>
    <div class="{{ $colRight }}">
        <button class="{{ $class }} mt-4" type="submit">{{ $label }}</button>
    </div>
</div>
