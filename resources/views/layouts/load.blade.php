{{--
================================================================================
    MUAADH THEME - LOAD LAYOUT (AJAX/Modal Content for Operator/Merchant Panels)
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. This layout is used for modal/AJAX content in Operator and Merchant panels
    2. Uses Admin CSS (NOT frontend CSS) to match parent panel styling
    3. DO NOT add <style> tags - use admin/css files instead
================================================================================
--}}
{{-- Admin Panel CSS - Used for Operator/Merchant modals --}}
<link rel="stylesheet" href="{{ asset('assets/operator/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/operator/css/fontawesome.css') }}">
<link rel="stylesheet" href="{{ asset('assets/operator/css/plugin.css') }}">
<link rel="stylesheet" href="{{ asset('assets/operator/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/operator/css/custom.css') }}">
{{-- Theme Colors - Generated from Admin Panel --}}
@themeStyles
@yield('styles')


@yield('content')

<script src="{{asset('assets/operator/js/vendors/jquery-1.12.4.min.js')}}"></script>
<script src="{{asset('assets/operator/js/jqueryui.min.js')}}"></script>
<script src="{{asset('assets/operator/js/vendors/vue.js')}}"></script>
<script src="{{asset('assets/operator/js/bootstrap-colorpicker.min.js') }}"></script>
<script src="{{asset('assets/operator/js/plugin.js')}}"></script>
<script src="{{asset('assets/operator/js/tag-it.js')}}"></script>
<script src="{{asset('assets/operator/js/load.js')}}"></script>



@yield('scripts')
