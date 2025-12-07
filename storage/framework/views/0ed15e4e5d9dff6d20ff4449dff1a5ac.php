
<!doctype html>
<?php
	// Get language from Session (same as frontend)
	$adminLang = Session::has('language')
		? \App\Models\Language::find(Session::get('language'))
		: \App\Models\Language::where('is_default', 1)->first();
?>
<html lang="en" dir="<?php echo e($adminLang && $adminLang->rtl == 1 ? 'rtl' : 'ltr'); ?>">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="author" content="Muaadh">
	<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
	<!-- Title -->
	<title><?php echo e($gs->title); ?></title>
	<!-- favicon -->
	<link rel="icon" type="image/x-icon" href="<?php echo e(asset('assets/images/' . $gs->favicon)); ?>" />
	<!-- Bootstrap -->
	<link href="<?php echo e(asset('assets/admin/css/bootstrap.min.css')); ?>" rel="stylesheet" />
	<!-- Fontawesome -->
	<link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/fontawesome.css')); ?>">
	<!-- icofont -->
	<link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/icofont.min.css')); ?>">
	<!-- Sidemenu Css -->
	<link href="<?php echo e(asset('assets/admin/plugins/fullside-menu/css/dark-side-style.css')); ?>" rel="stylesheet" />
	<link href="<?php echo e(asset('assets/admin/plugins/fullside-menu/waves.min.css')); ?>" rel="stylesheet" />

	<link href="<?php echo e(asset('assets/admin/css/plugin.css')); ?>" rel="stylesheet" />

	<link href="<?php echo e(asset('assets/admin/css/jquery.tagit.css')); ?>" rel="stylesheet" />
	<link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/bootstrap-colorpicker.css')); ?>">
	<!-- Main Css -->

	<!-- stylesheet -->
	<?php if($adminLang && $adminLang->rtl == 1): ?>

		<link href="<?php echo e(asset('assets/admin/css/rtl/style.css')); ?>" rel="stylesheet" />
		<link href="<?php echo e(asset('assets/admin/css/rtl/custom.css')); ?>" rel="stylesheet" />
		<link href="<?php echo e(asset('assets/admin/css/rtl/responsive.css')); ?>" rel="stylesheet" />
		<link href="<?php echo e(asset('assets/admin/css/common.css')); ?>" rel="stylesheet" />

	<?php else: ?>

		<link href="<?php echo e(asset('assets/admin/css/style.css')); ?>" rel="stylesheet" />
		<link href="<?php echo e(asset('assets/admin/css/custom.css')); ?>" rel="stylesheet" />
		<link href="<?php echo e(asset('assets/admin/css/responsive.css')); ?>" rel="stylesheet" />
		<link href="<?php echo e(asset('assets/admin/css/common.css')); ?>" rel="stylesheet" />
	<?php endif; ?>

	
	<link rel="stylesheet" href="<?php echo e(asset('assets/front/css/bootstrap.min.css')); ?>">
	
	<link rel="stylesheet" href="<?php echo e(asset('assets/front/css/style.css')); ?>?v=<?php echo e(time()); ?>">
	
	<link rel="stylesheet" href="<?php echo e(asset('assets/front/css/theme-colors.css')); ?>?v=<?php echo e(@filemtime(public_path('assets/front/css/theme-colors.css'))); ?>">

	
	<style>
		.frontend-header-wrapper .header-top {
			display: none !important;
		}
		.frontend-header-wrapper {
			position: relative;
			z-index: 99999;
		}

		/* Fix Admin Notifications Dropdown - Override Frontend CSS Conflicts */
		.page .header .right-eliment .list li .dropdown-menu {
			border: 0px !important;
			width: 280px !important;
			padding: 0px !important;
			left: auto !important;
			top: 97% !important;
			right: -15px !important;
			border-radius: 0px !important;
			box-shadow: 0px 3px 25px rgba(0, 0, 0, 0.15) !important;
			background: #fff !important;
			z-index: 99999 !important;
			position: absolute !important;
			display: none !important;
			min-width: auto !important;
			text-align: left !important;
		}

		.page .header .right-eliment .list li .dropdown-menu.show {
			display: block !important;
		}

		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper {
			padding: 7px 25px 20px !important;
			text-align: left !important;
		}

		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper a,
		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper p,
		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper span {
			color: #143250 !important;
		}

		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper ul {
			padding-left: 0px !important;
			list-style: none !important;
		}

		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper ul li {
			display: block !important;
			border-bottom: none !important;
		}

		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper ul li a {
			margin-bottom: 0px !important;
			padding: 0px !important;
			font-size: 14px !important;
			line-height: 28px !important;
			height: auto !important;
			border-bottom: none !important;
			display: block !important;
			font-family: inherit !important;
		}

		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper ul li a:hover {
			background: transparent !important;
		}

		.page .header .right-eliment .list li .dropdown-menu .dropdownmenu-wrapper h5 {
			color: #143250 !important;
			font-size: 16px !important;
			margin-bottom: 10px !important;
		}
	</style>

	<?php echo $__env->yieldContent('styles'); ?>

	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


</head>

<body id="page-top">

	<?php
		$categories = App\Models\Category::with('subs')->where('status', 1)->get();
		$pages = App\Models\Page::get();
		$currencies = App\Models\Currency::all();
		$languges = App\Models\Language::all();
	?>

	<div class="frontend-header-wrapper">
		
		<?php echo $__env->make('includes.frontend.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

		
		<?php echo $__env->make('includes.frontend.mobile_menu', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
	</div>

	<!-- overlay for mobile menu -->
	<div class="overlay"></div>

	<div style="margin-top:20px;"></div>

	<div class="page">
		<div class="page-main">
			<!-- Header Menu Area Start -->
			<div class="header">
				<div class="container-fluid">
					<div class="d-flex mobile-menu-check justify-content-between">
						<a class="admin-logo" href="<?php echo e(route('front.index')); ?>" target="_blank">
							<img src="<?php echo e(asset('assets/images/' . $gs->logo)); ?>" alt="">
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
								<input type="hidden" id="all_notf_count" value="<?php echo e(route('all-notf-count')); ?>">
								<li class="bell-area">
									<a class="dropdown-toggle-1" target="_blank" href="<?php echo e(route('front.index')); ?>">
										<i class="fas fa-globe-americas"></i>
									</a>
								</li>

								<li class="bell-area">
									<a id="notf_conv" class="dropdown-toggle-1" href="javascript:;">
										<i class="far fa-envelope"></i>
										<span id="conv-notf-count"><?php echo e(App\Models\Notification::countConversation()); ?></span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="<?php echo e(route('conv-notf-show')); ?>"
											id="conv-notf-show">
										</div>
									</div>
								</li>

								<li class="bell-area">
									<a id="notf_product" class="dropdown-toggle-1" href="javascript:;">
										<i class="icofont-cart"></i>
										<span id="product-notf-count"><?php echo e(App\Models\Notification::countProduct()); ?></span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="<?php echo e(route('product-notf-show')); ?>"
											id="product-notf-show">
										</div>
									</div>
								</li>

								<li class="bell-area">
									<a id="notf_user" class="dropdown-toggle-1" href="javascript:;">
										<i class="far fa-user"></i>
										<span id="user-notf-count"><?php echo e(App\Models\Notification::countRegistration()); ?></span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="<?php echo e(route('user-notf-show')); ?>"
											id="user-notf-show">
										</div>
									</div>
								</li>

								<li class="bell-area">
									<a id="notf_order" class="dropdown-toggle-1" href="javascript:;">
										<i class="far fa-newspaper"></i>
										<span id="order-notf-count"><?php echo e(App\Models\Notification::countOrder()); ?></span>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper" data-href="<?php echo e(route('order-notf-show')); ?>"
											id="order-notf-show">
										</div>
									</div>
								</li>

								<li class="login-profile-area">
									<a class="dropdown-toggle-1" href="javascript:;">
										<div class="user-img">
											<img src="<?php echo e(Auth::guard('admin')->user()->photo ? asset('assets/images/admins/' . Auth::guard('admin')->user()->photo) : asset('assets/images/noimage.png')); ?>"
												alt="">
										</div>
									</a>
									<div class="dropdown-menu">
										<div class="dropdownmenu-wrapper">
											<ul>
												<h5><?php echo e(__('Welcome!')); ?></h5>
												<li>
													<a href="<?php echo e(route('admin.profile')); ?>"><i class="fas fa-user"></i>
														<?php echo e(__('Edit Profile')); ?></a>
												</li>
												<li>
													<a href="<?php echo e(route('admin.password')); ?>"><i class="fas fa-cog"></i>
														<?php echo e(__('Change Password')); ?></a>
												</li>
												<li>
													<a href="<?php echo e(route('admin.logout')); ?>"><i
															class="fas fa-power-off"></i> <?php echo e(__('Logout')); ?></a>
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
							<a href="<?php echo e(route('admin.dashboard')); ?>" class="wave-effect"><i
									class="fa fa-home mr-2"></i><?php echo e(__('Dashboard')); ?></a>
						</li>
						<?php if(Auth::guard('admin')->user()->IsSuper()): ?>
							<?php echo $__env->make('partials.admin-role.super', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

							<li class="mt-3 text-dark text-center">
							<?php echo app('translator')->get('Version 4.0'); ?>
							</li>
						<?php else: ?>
							<?php echo $__env->make('partials.admin-role.normal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
						<?php endif; ?>
					</ul>
				</nav>
				<!-- Main Content Area Start -->
				<?php echo $__env->yieldContent('content'); ?>
				<!-- Main Content Area End -->
			</div>
		</div>
	</div>
	<script type="text/javascript">
		var mainurl = "<?php echo e(url('/')); ?>";
		var admin_loader = <?php echo e($gs->is_admin_loader); ?>;
		var whole_sell = <?php echo e($gs->wholesell); ?>;
		var getattrUrl = '<?php echo e(route('admin-prod-getattributes')); ?>';
		var curr = <?php echo json_encode($curr); ?>;
		var lang = {
			'additional_price': '<?php echo e(__('0.00 (Additional Price)')); ?>'
		};
	</script>

	<!-- Dashboard Core -->
	<script src="<?php echo e(asset('assets/admin/js/vendors/jquery-1.12.4.min.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/js/vendors/vue.js')); ?>"></script>
	
	<script src="<?php echo e(asset('assets/front/js/bootstrap.bundle.min.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/js/jqueryui.min.js')); ?>"></script>
	<!-- Fullside-menu Js-->
	<script src="<?php echo e(asset('assets/admin/plugins/fullside-menu/jquery.slimscroll.min.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/plugins/fullside-menu/waves.min.js')); ?>"></script>

	<script src="<?php echo e(asset('assets/admin/js/plugin.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/js/Chart.min.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/js/tag-it.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/js/nicEdit.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/js/bootstrap-colorpicker.min.js')); ?>"></script>
	<script src="<?php echo e(asset('assets/admin/js/notify.js')); ?>"></script>

	<script src="<?php echo e(asset('assets/admin/js/jquery.canvasjs.min.js')); ?>"></script>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

	<script src="<?php echo e(asset('assets/admin/js/load.js')); ?>"></script>
	<!-- Frontend Mobile Menu Js (loaded before custom.js to avoid conflicts)-->
	<script src="<?php echo e(asset('assets/front/js/script.js')); ?>?v=<?php echo e(time()); ?>"></script>
	<!-- Custom Js-->
	<script src="<?php echo e(asset('assets/admin/js/custom.js')); ?>"></script>
	<!-- AJAX Js-->
	<script src="<?php echo e(asset('assets/admin/js/myscript.js')); ?>"></script>

	<!-- Admin Dropdown Fix - Ensure Admin dropdowns work correctly -->
	<script>
		$(document).ready(function() {
			// Re-bind Admin dropdown functionality to override any conflicts
			$(".page .header .right-eliment .list .dropdown-toggle-1").off('click').on("click", function (e) {
				e.preventDefault();
				e.stopPropagation();

				// Hide all other dropdowns
				$(this).parent().siblings().find(".dropdown-menu").removeClass('show').hide();

				// Toggle current dropdown
				$(this).next(".dropdown-menu").toggleClass('show').toggle();
			});

			// Close dropdowns when clicking outside
			$(document).on("click", function (e) {
				var container = $(".page .header .right-eliment .list .dropdown-toggle-1");

				if (!container.is(e.target) && container.has(e.target).length === 0) {
					$(".page .header .right-eliment .list .dropdown-menu").removeClass('show').hide();
				}
			});
		});
	</script>

	<?php echo $__env->yieldContent('scripts'); ?>
	<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>


	<?php if($gs->is_admin_loader == 0): ?>
		<style>
			div#muaadhtable_processing {
				display: none !important;
			}
		</style>
	<?php endif; ?>

</body>

</html><?php /**PATH C:\Users\hp\Herd\new\resources\views/layouts/admin.blade.php ENDPATH**/ ?>