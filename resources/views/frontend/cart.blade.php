{{-- @php
    dd(Session::get('cart'));
@endphp --}}

@extends('layouts.front')
@section('content')
    <style>
        /* Modern Teal/Cyan Cart Theme */
        .gs-breadcrumb-section {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important;
            position: relative;
            overflow: hidden;
        }

        .gs-breadcrumb-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}') center/cover;
            opacity: 0.15;
            z-index: 0;
        }

        .gs-breadcrumb-section .content-wrapper {
            position: relative;
            z-index: 1;
        }

        .breadcrumb-title {
            color: #ffffff !important;
            font-size: 2.5rem;
            font-weight: 800;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            letter-spacing: 1px;
        }

        .bread-menu li a {
            color: #e0f2fe !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .bread-menu li a:hover {
            color: #ffffff !important;
            transform: translateX(4px);
        }

        /* Cart Section */
        .gs-cart-section {
            background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 100%);
            padding: 4rem 0;
            min-height: 60vh;
        }

        /* Cart Table Styling */
        .cart-table {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(13, 148, 136, 0.1);
            border: 2px solid #e0f2fe;
        }

        .cart-table table thead {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
        }

        .cart-table table thead th {
            color: #ffffff !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1.2rem 1rem;
            border: none !important;
        }

        .cart-table table thead th:first-child {
            border-radius: 12px 0 0 0;
        }

        .cart-table table thead th:last-child {
            border-radius: 0 12px 0 0;
        }

        .cart-table table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #e0f2fe;
        }

        .cart-table table tbody tr:hover {
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%);
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.1);
        }

        .cart-table table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
            color: #334155;
            font-weight: 500;
        }

        .cart-image img {
            border-radius: 14px;
            border: 2px solid #e0f2fe;
            transition: all 0.3s ease;
        }

        .cart-image img:hover {
            border-color: #14b8a6;
            transform: scale(1.1);
        }

        /* Quantity Controls */
        .cart-quantity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2fe 100%);
            border-radius: 12px;
            padding: 0.3rem;
            border: 2px solid #14b8a6;
        }

        .cart-quantity-btn {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            color: #ffffff;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .cart-quantity-btn:hover {
            transform: scale(1.15);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
            background: linear-gradient(135deg, #14b8a6 0%, #2dd4bf 100%);
        }

        .cart-quantity input {
            width: 50px;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 600;
            color: #0d9488;
        }

        /* Remove Button */
        .cart-remove-btn {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .cart-remove-btn:hover {
            transform: translateY(-4px) rotate(8deg) scale(1.1);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .cart-remove-btn svg path {
            stroke: #ffffff;
        }

        /* Cart Summary */
        .cart-summary {
            background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(13, 148, 136, 0.15);
            border: 2px solid #14b8a6;
            position: sticky;
            top: 20px;
        }

        .cart-summary-title {
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%) 1;
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cart-summary-item {
            padding: 1rem 0;
            border-bottom: 1px solid #e0f2fe;
        }

        .cart-summary-subtitle {
            color: #64748b;
            font-weight: 600;
            margin: 0;
        }

        .cart-summary-price {
            color: #0d9488;
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
        }

        .cart-summary-btn {
            margin-top: 1.5rem;
        }

        .cart-summary-btn .template-btn {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            color: #ffffff;
            border: none;
            padding: 1rem 2rem;
            border-radius: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);
            text-align: center;
            display: block;
        }

        .cart-summary-btn .template-btn:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 32px rgba(13, 148, 136, 0.4);
            background: linear-gradient(135deg, #14b8a6 0%, #2dd4bf 100%);
        }

        /* Empty Cart */
        .card {
            background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);
            border: 2px solid #e0f2fe !important;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(13, 148, 136, 0.1);
        }

        .card h4 {
            color: #64748b;
            font-weight: 600;
        }
    </style>

    <section class="gs-breadcrumb-section bg-class">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Cart')</h2>
                    <ul class="bread-menu">

                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{route("front.cart")}}">@lang('Cart')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="gs-cart-section load_cart">
        @include('frontend.ajax.cart-page')
    </section>

    {{-- Include Google Maps Modal for Cart --}}
    @include('components.google-maps-picker', ['showAsModal' => true, 'modalId' => 'google-maps-modal-cart'])
@endsection

@push('scripts')
<script src="{{ asset('assets/front/js/google-maps-location-picker.js') }}"></script>
<script>
    // Google Maps Location Picker for Cart
    let cartLocationPicker;

    function initCartLocationPicker() {
        cartLocationPicker = new GoogleMapsLocationPicker({
            containerId: 'map-picker-container',
            mapId: 'location-map',
            onLocationSelect: function(data) {
                // Store location in session/localStorage for checkout
                localStorage.setItem('delivery_location', JSON.stringify(data));

                // Show selected location info
                $('#location-info-display').addClass('show');
                $('#confirm-location-btn').prop('disabled', false);

                toastr.success('@lang("Delivery location saved!")');
            }
        });

        cartLocationPicker.init();
    }

    // Initialize when modal is shown
    $('#google-maps-modal-cart').on('shown.bs.modal', function() {
        if (!cartLocationPicker) {
            initCartLocationPicker();
        }
    });

    // Confirm location button
    $(document).on('click', '#confirm-location-btn', function() {
        const location = cartLocationPicker.getSelectedLocation();
        if (location) {
            toastr.success('@lang("Location will be used during checkout!")');
            $('#google-maps-modal-cart').modal('hide');
        }
    });

    // Reset button
    $(document).on('click', '#reset-location-btn', function() {
        if (cartLocationPicker) {
            cartLocationPicker.reset();
            $('#confirm-location-btn').prop('disabled', true);
        }
    });
</script>
@endpush
