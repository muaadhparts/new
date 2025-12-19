{{--
================================================================================
    MUAADH THEME - LOAD LAYOUT (AJAX/Modal Content)
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. The ONLY file for adding/modifying custom CSS is: public/assets/front/css/style.css
    2. DO NOT add <style> tags in Blade files - move all styles to style.css
    3. DO NOT create new CSS files - use style.css sections instead
================================================================================
--}}
{{-- Main Theme File - Contains all styles --}}
<link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}">
{{-- Theme Colors - Generated from Admin Panel (MUST load LAST to override :root variables) --}}
<link rel="stylesheet" href="{{ asset('assets/front/css/theme-colors.css') }}">
@yield('styles')


@yield('content')

<script src="{{asset('assets/admin/js/vendors/jquery-1.12.4.min.js')}}"></script>
<script src="{{asset('assets/admin/js/jqueryui.min.js')}}"></script>
<script src="{{asset('assets/admin/js/vendors/vue.js')}}"></script>
<script src="{{asset('assets/admin/js/bootstrap-colorpicker.min.js') }}"></script>
<script src="{{asset('assets/admin/js/plugin.js')}}"></script>
<script src="{{asset('assets/admin/js/tag-it.js')}}"></script>
<script src="{{asset('assets/admin/js/load.js')}}"></script>



@yield('scripts')
