{{--
================================================================================
    MUAADH THEME - OPERATOR LAYOUT
================================================================================
    CSS GUIDELINES FOR AI AGENTS:
    -----------------------------
    1. The ONLY file for adding/modifying custom CSS is: public/assets/front/css/style.css
    2. DO NOT add <style> tags in Blade files - move all styles to style.css
    3. DO NOT create new CSS files - use style.css sections instead
    4. Use CSS variables from style.css (--theme-* or --muaadh-*)
    5. Add new styles under appropriate section comments in style.css

    FILE STRUCTURE:
    - style.css = MAIN THEME FILE (ALL CUSTOMIZATIONS HERE)
    - theme-colors.css = Generated from Admin Panel (overrides :root variables)
    - Admin CSS files in assets/operator/css = DO NOT MODIFY
================================================================================
--}}
<!doctype html>
@php
	// Get language from Session (same as frontend)
	$adminLang = Session::has('language')
		? \App\Models\Language::find(Session::get('language'))
		: \App\Models\Language::where('is_default', 1)->first();
@endphp
<html lang="en" dir="{{ $adminLang && $adminLang->rtl == 1 ? 'rtl' : 'ltr' }}">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="author" content="Muaadh">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<!-- Title -->
	<title>{{$gs->site_name}}</title>
	<!-- favicon -->
	<link rel="icon" type="image/x-icon" href="{{asset('assets/images/' . $gs->favicon)}}" />
	<!-- Bootstrap -->
	<link href="{{asset('assets/operator/css/bootstrap.min.css')}}" rel="stylesheet" />
	<!-- Fontawesome -->
	<link rel="stylesheet" href="{{asset('assets/operator/css/fontawesome.css')}}">
	<!-- icofont -->
	<link rel="stylesheet" href="{{asset('assets/operator/css/icofont.min.css')}}">
	<!-- Sidemenu Css -->
	<link href="{{asset('assets/operator/plugins/fullside-menu/css/dark-side-style.css')}}" rel="stylesheet" />
	<link href="{{asset('assets/operator/plugins/fullside-menu/waves.min.css')}}" rel="stylesheet" />

	<link href="{{asset('assets/operator/css/plugin.css')}}" rel="stylesheet" />

	<link href="{{asset('assets/operator/css/jquery.tagit.css')}}" rel="stylesheet" />
	<link rel="stylesheet" href="{{ asset('assets/operator/css/bootstrap-colorpicker.css') }}">
	<!-- Main Css -->

	<!-- stylesheet -->
	@if($adminLang && $adminLang->rtl == 1)

		<link href="{{asset('assets/operator/css/rtl/style.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/operator/css/rtl/custom.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/operator/css/rtl/responsive.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/operator/css/common.css')}}" rel="stylesheet" />

	@else

		<link href="{{asset('assets/operator/css/style.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/operator/css/custom.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/operator/css/responsive.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/operator/css/common.css')}}" rel="stylesheet" />
	@endif

	{{-- Admin uses its own CSS - no frontend CSS needed --}}

	@yield('styles')

	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	@livewireStyles

</head>

<body id="page-top">

	{{-- Admin has its own header system - no frontend header needed --}}

	<div class="page">
		<div class="page-main">
			<!-- Header Menu Area Start -->
			<div class="header">
				<div class="container-fluid">
					<div class="d-flex mobile-menu-check justify-content-between">
						<a class="admin-logo" href="{{ route('front.index') }}" target="_blank">
							<img src="{{asset('assets/images/' . $gs->logo)}}" alt="">
						</a>
						<div class="menu-toggle-button">
							<a class="nav-link" href="javascript:;" id="sidebarCollapse">
								<div class="my-toggl-icon">
									<span class="bar1"></span>
									<span class="bar2"></span>
									<span class="bar3"></span>
								</div>
							</a>
						</div>

						<div class="right-eliment">
							<ul class="list">
								<input type="hidden" id="all_event_count" value="{{ route('all-event-count') }}">
								<li class="bell-area">
									<a class="dropdown-toggle-1" target="_blank" href="{{ route('front.index') }}">
										<i class="fas fa-globe-americas"></i>
									</a>
								</li>

								<li class="bell-area">
									<a id="event_conv" class="dropdown-toggle-1" href="javascript:;">
										<i class="far fa-envelope"></i>
										<span id="conv-event-count">{{ App\Models\CatalogEvent::countChatThread() }}</span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="{{ route('conv-event-show') }}"
											id="conv-event-show">
										</div>
									</div>
								</li>

								<li class="bell-area">
									<a id="event_product" class="dropdown-toggle-1" href="javascript:;">
										<i class="icofont-cart"></i>
										<span id="catalogItem-event-count">{{ App\Models\CatalogEvent::countCatalogItem() }}</span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="{{ route('catalog-item-event-show') }}"
											id="catalog-item-event-show">
										</div>
									</div>
								</li>

								<li class="bell-area">
									<a id="event_user" class="dropdown-toggle-1" href="javascript:;">
										<i class="far fa-user"></i>
										<span id="user-event-count">{{ App\Models\CatalogEvent::countRegistration() }}</span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="{{ route('user-event-show') }}"
											id="user-event-show">
										</div>
									</div>
								</li>

								<li class="bell-area">
									<a id="event_order" class="dropdown-toggle-1" href="javascript:;">
										<i class="far fa-newspaper"></i>
										<span id="purchase-event-count">{{ App\Models\CatalogEvent::countPurchase() }}</span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="{{ route('purchase-event-show') }}"
											id="purchase-event-show">
										</div>
									</div>
								</li>

								{{-- Language Switcher --}}
								<li class="bell-area">
									<a class="dropdown-toggle-1" href="javascript:;">
										<i class="fas fa-language"></i>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper">
											<ul>
												<h5>{{ __('Select Language') }}</h5>
												@foreach($languges as $language)
													<li>
														<a href="{{ route('front.language', $language->id) }}"
															class="{{ Session::has('language') && Session::get('language') == $language->id ? 'active' : '' }}
																{{ !Session::has('language') && $language->is_default == 1 ? 'active' : '' }}">
															<i class="fas fa-globe"></i>
															{{ $language->language }}
														</a>
													</li>
												@endforeach
											</ul>
										</div>
									</div>
								</li>

								<li class="login-profile-area">
									<a class="dropdown-toggle-1" href="javascript:;">
										<div class="user-img">
											<img src="{{ Auth::guard('operator')->user()->photo ? asset('assets/images/operators/' . Auth::guard('operator')->user()->photo) : asset('assets/images/noimage.png') }}"
												alt="">
										</div>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper">
											<ul>
												<h5>{{ __('Welcome!') }}</h5>
												<li>
													<a href="{{ route('operator.profile') }}"><i class="fas fa-user"></i>
														{{ __('Edit Profile') }}</a>
												</li>
												<li>
													<a href="{{ route('operator.password') }}"><i class="fas fa-cog"></i>
														{{ __('Change Password') }}</a>
												</li>
												<li>
													<a href="{{ route('operator.logout') }}"><i
															class="fas fa-power-off"></i> {{ __('Logout') }}</a>
												</li>
											</ul>
										</div>
									</div>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<!-- Header Menu Area End -->
			<div class="wrapper">
				<!-- Side Menu Area Start -->
				<nav id="sidebar" class="nav-sidebar">
					<ul class="list-unstyled components" id="accordion">
						<li>
							<a href="{{ route('operator.dashboard') }}" class="wave-effect"><i
									class="fa fa-home mr-2"></i>{{ __('Dashboard') }}</a>
						</li>
						@if(Auth::guard('operator')->user()->IsSuper())
							@include('partials.operator-role.super')

							<li class="mt-3 text-dark text-center">
							@lang('Version 4.0')
							</li>
						@else
							@include('partials.operator-role.normal')
						@endif
					</ul>
				</nav>
				<!-- Main Content Area Start -->
				@yield('content')
				<!-- Main Content Area End -->
			</div>
		</div>
	</div>
	<script type="text/javascript">
		var mainurl = "{{url('/')}}";
		var admin_loader = {{ $gs->is_admin_loader }};
		var whole_sell = {{ $gs->wholesell }};
		var getattrUrl = ''; // Attributes feature removed
		var curr = {!! json_encode($curr) !!};
		var lang = {
			'additional_price': '{{ __('0.00 (Additional Price)') }}'
		};
	</script>

	<!-- Dashboard Core -->
	<script src="{{asset('assets/operator/js/vendors/jquery-1.12.4.min.js')}}"></script>
	<script src="{{asset('assets/operator/js/vendors/vue.js')}}"></script>
	{{-- Frontend Bootstrap 5 for dropdowns --}}
	<script src="{{asset('assets/front/js/bootstrap.bundle.min.js')}}"></script>
	<script src="{{asset('assets/operator/js/jqueryui.min.js')}}"></script>
	<!-- Fullside-menu Js-->
	<script src="{{asset('assets/operator/plugins/fullside-menu/jquery.slimscroll.min.js')}}"></script>
	<script src="{{asset('assets/operator/plugins/fullside-menu/waves.min.js')}}"></script>

	<script src="{{asset('assets/operator/js/plugin.js')}}"></script>

	{{-- DataTables Arabic Language Defaults --}}
	@if(app()->getLocale() == 'ar')
	<script>
		$.extend(true, $.fn.dataTable.defaults, {
			language: {
				emptyTable: "{{ __('No data available in table') }}",
				zeroRecords: "{{ __('No matching records found') }}",
				info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
				infoEmpty: "{{ __('Showing 0 to 0 of 0 entries') }}",
				infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
				lengthMenu: "{{ __('Show _MENU_ entries') }}",
				search: "{{ __('Search:') }}",
				paginate: {
					first: "{{ __('First') }}",
					last: "{{ __('Last') }}",
					next: "{{ __('Next') }}",
					previous: "{{ __('Previous') }}"
				}
			}
		});
	</script>
	@endif

	<script src="{{asset('assets/operator/js/Chart.min.js')}}"></script>
	<script src="{{asset('assets/operator/js/tag-it.js')}}"></script>
	<script src="{{asset('assets/operator/js/nicEdit.js')}}"></script>
	<script src="{{asset('assets/operator/js/bootstrap-colorpicker.min.js') }}"></script>
	<script src="{{asset('assets/operator/js/notify.js') }}"></script>

	<script src="{{asset('assets/operator/js/jquery.canvasjs.min.js')}}"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

	<script src="{{asset('assets/operator/js/load.js')}}"></script>
	<!-- Custom Js-->
	<script src="{{asset('assets/operator/js/custom.js')}}"></script>
	<!-- AJAX Js-->
	<script src="{{asset('assets/operator/js/myscript.js')}}"></script>

	@yield('scripts')
	@livewireScripts

	@if($gs->is_admin_loader == 0)
		<style>
			div#muaadhtable_processing {
				display: none !important;
			}
		</style>
	@endif

</body>

</html>