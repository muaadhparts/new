{{--
    Location Trigger Button Component
    ==================================
    A button that shows current location and allows changing it.
    Typically placed in the site header/navbar.

    Usage:
    <x-location-trigger />

    Props:
    - class (optional): Additional CSS classes
    - placeholder (optional): Text shown when no location is set
--}}

@props([
    'class' => '',
    'placeholder' => null,
])

@php
    $placeholder = $placeholder ?? __('حدد موقعك');
    $locationService = app(\App\Domain\Shipping\Services\CustomerLocationService::class);
    $hasLocation = $locationService->hasLocation();
    $displayText = $locationService->getDisplayText() ?? $placeholder;
@endphp

<button type="button"
    class="m-location-trigger {{ $hasLocation ? 'has-location' : '' }} {{ $class }}"
    data-location-trigger
    onclick="CustomerLocation.requestLocation()"
>
    <i class="fas fa-map-marker-alt m-location-trigger__icon"></i>
    <span class="m-location-trigger__text"
          data-location-display
          data-location-placeholder="{{ $placeholder }}"
    >{{ $displayText }}</span>
    <i class="fas fa-chevron-down" style="font-size: 0.75rem; opacity: 0.6;"></i>
</button>
