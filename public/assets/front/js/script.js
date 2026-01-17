$(document).ready(function () {
  /*
  TABLE OF CONTENTS:
  1. MUAADH ELEGANT HEADER (Mobile menu, tabs, accordions)
  2. DATA BACKGROUND SET
  3. MOBILE MENU (Legacy - Vendor/Admin/User/Courier)
  4. WOW JS (Animations)
  5. HIDE & SHOW PASSWORD
  6. PRODUCT DETAILS CAROUSEL
  7. SIDEBAR ACTIVE STATE
  8. TOGGLE MERCHANT DASHBOARD SIDEBAR
  9. PRODUCT CARDS CAROUSEL
  10. COUNTER UP
  11. SUBMENU & NOTIFICATIONS
  12. NICEDIT RESIZE
  */

  //****** 1. MUAADH ELEGANT HEADER ******//
  (function() {
    // Elements - Check which menu type exists on this page
    const $muaadhMobileMenu = $('.muaadh-mobile-menu');
    const $muaadhOverlay = $('.muaadh-mobile-overlay');

    // Legacy menu elements (for User/Vendor/Courier pages that use .mobile-menu)
    const $legacyMenu = $('.mobile-menu');
    const $legacyOverlay = $('.overlay');

    // Determine which menu system is available on this page
    const hasMuaadhMenu = $muaadhMobileMenu.length > 0;
    const hasLegacyMenu = $legacyMenu.length > 0;

    // Mobile Menu Toggle - Support both old and new triggers
    // SMART FALLBACK: If .muaadh-mobile-menu doesn't exist, open .mobile-menu instead
    $('.muaadh-mobile-toggle').on('click', function(e) {
      e.preventDefault();

      if (hasMuaadhMenu) {
        // Open new Muaadh mobile menu
        $muaadhMobileMenu.addClass('active');
        $muaadhOverlay.addClass('active');
        $('body').css('overflow', 'hidden');
      } else if (hasLegacyMenu) {
        // Fallback: Open legacy mobile menu (for user pages like favorites)
        $legacyMenu.addClass('active');
        $legacyOverlay.addClass('active');
        $('body').css('overflow', 'hidden');
      }
    });

    // Close Mobile Menu (Muaadh menu only)
    // Using event delegation for robust handling (works in LTR and RTL)
    function closeMuaadhMenu(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      $('.muaadh-mobile-menu').removeClass('active');
      $('.muaadh-mobile-overlay').removeClass('active');
      $('body').css('overflow', '');
    }

    // Use delegated event handlers for reliable click detection
    $(document).on('click', '.muaadh-mobile-close', closeMuaadhMenu);
    $(document).on('click', '.muaadh-mobile-overlay', closeMuaadhMenu);

    // Also close menu when clicking any navigation link inside the mobile menu
    $(document).on('click', '.muaadh-mobile-nav-item > a:not(.muaadh-accordion-toggle)', function() {
      // Only close if the link actually navigates (not accordion toggles)
      const href = $(this).attr('href');
      if (href && href !== '#' && href !== 'javascript:void(0)') {
        closeMuaadhMenu();
      }
    });

    // Mobile Menu Tabs
    $('.muaadh-mobile-tab').on('click', function() {
      const target = $(this).data('target');

      // Update tab states
      $('.muaadh-mobile-tab').removeClass('active');
      $(this).addClass('active');

      // Update content states
      $('.muaadh-mobile-tab-pane').removeClass('active');
      $('#' + target).addClass('active');
    });

    // Mobile Menu Accordions
    $('.muaadh-accordion-toggle').on('click', function(e) {
      e.preventDefault();
      const $accordion = $(this).closest('.muaadh-mobile-nav-accordion');
      const $content = $accordion.find('.muaadh-accordion-content').first();

      $(this).toggleClass('active');
      $content.toggleClass('active');
    });
  })();

  //****** 2. DATA BACKGROUND SET ******//
  $("[data-color-code]").each(function () {
    $(this).css("background-color", $(this).attr("data-color-code"));
  });
  $("[data-outline-color-code]").each(function () {
    $(this).css("outline-color", $(this).attr("data-outline-color-code"));
  });

  $("[data-background]").each(function () {
    $(this).css(
      "background-image",
      "url(" + $(this).attr("data-background") + ")"
    );
  });

  //****** 3. MOBILE MENU (Unified Handler) ******//
  // This handles BOTH types of mobile menus:
  // 1. .muaadh-mobile-menu (Frontend/Store) - opened by .muaadh-mobile-toggle
  // 2. .mobile-menu (Vendor/Admin/User/Courier dashboards) - opened by .header-toggle, .mobile-menu-toggle
  //
  // Each menu type works independently - no conflicts between them.

  const $overlay = $(".overlay");
  const $legacyMobileMenu = $(".mobile-menu");

  // Legacy toggles (.header-toggle, .mobile-menu-toggle) - for Vendor/Admin/User/Courier
  // These should ONLY open .mobile-menu (legacy), NOT .muaadh-mobile-menu
  $(".header-toggle, .mobile-menu-toggle").on("click", function (e) {
    e.preventDefault();

    // Only open legacy mobile menu (.mobile-menu)
    // Do NOT open .muaadh-mobile-menu - that's handled separately by .muaadh-mobile-toggle
    if ($legacyMobileMenu.length) {
      $legacyMobileMenu.toggleClass("active");
      $overlay.addClass("active");
      $('body').css('overflow', 'hidden');
    }
  });

  // Close legacy mobile menu (.mobile-menu)
  $(".mobile-menu .btn-close").on("click", function (e) {
    e.preventDefault();
    $legacyMobileMenu.removeClass("active");
    $overlay.removeClass("active");
    $('body').css('overflow', '');
  });

  // Close on overlay click - handles both menu types
  $overlay.on("click", function () {
    // Close legacy menu
    $legacyMobileMenu.removeClass("active");
    $overlay.removeClass("active");
    $('body').css('overflow', '');
  });

  //****** 4. WOW JS ******//
  if (typeof WOW !== 'undefined') {
    new WOW().init();
  }

  //****** 5. HIDE & SHOW PASSWORD ******//
  const $passwordInput = $("#create-password");
  const $eyeOffIcon = $(".eye-off");
  const $eyeOnIcon = $(".eye-on");

  $eyeOffIcon.on("click", function () {
    $passwordInput.attr("type", "text");
    $eyeOffIcon.hide();
    $eyeOnIcon.show();
  });

  $eyeOnIcon.on("click", function () {
    $passwordInput.attr("type", "password");
    $eyeOnIcon.hide();
    $eyeOffIcon.show();
  });

  // change pass input
  const $confirmPasswordInput = $("#confirm-password");
  const $confirmEyeOffIcon = $(".confirm-eye-off");
  const $confirmEyeOnIcon = $(".confirm-eye-on");

  $confirmEyeOffIcon.on("click", function () {
    $confirmPasswordInput.attr("type", "text");
    $confirmEyeOffIcon.hide();
    $confirmEyeOnIcon.show();
  });

  $confirmEyeOnIcon.on("click", function () {
    $confirmPasswordInput.attr("type", "password");
    $confirmEyeOnIcon.hide();
    $confirmEyeOffIcon.show();
  });

  // Detect RTL mode (used for all carousels)
  var isRTL = $('html').attr('dir') === 'rtl' || $('body').attr('dir') === 'rtl';

  //****** 6. PRODUCT DETAILS CAROUSEL ******//

  if (typeof $.fn.slick !== 'undefined' && $(".catalogItem-main-carousel").length > 0) {
    $(".catalogItem-main-carousel").slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: false,
      rtl: isRTL,
      asNavFor: ".catalogItem-nav-carousel",
    });
  }
  if (typeof $.fn.slick !== 'undefined' && $(".catalogItem-nav-carousel").length > 0) {
    $(".catalogItem-nav-carousel").slick({
      slidesToShow: 3,
      slidesToScroll: 1,
      asNavFor: ".catalogItem-main-carousel",
      dots: false,
      focusOnSelect: true,
      variableWidth: true,
      rtl: isRTL,
    });
  }

  //****** 7. SIDEBAR ACTIVE STATE ******//
  $(".gs-dashboard-user-sidebar-wrapper a").on("click", function () {
    // Remove the .active class from all <a> tags
    $(".gs-dashboard-user-sidebar-wrapper a").parent().removeClass("active");

    // Add the .active class to the clicked <a> tag
    $(this).parent().addClass("active");
  });

  //****** 8. TOGGLE MERCHANT DASHBOARD SIDEBAR ******//
  $(".gs-merchant-toggle-btn").on("click", function () {
    $(".gs-merchant-sidebar-wrapper").toggleClass("collapsed");
    $(".gs-merchant-header-outlet-wrapper").toggleClass("increased-width");
  });

  //****** 9. PRODUCT CARDS CAROUSEL ******//
  // Updated to match grid layout: col-6 col-md-4 col-lg-3 (4 cards on lg, 3 on md, 2 on sm)
  if (typeof $.fn.slick !== 'undefined' && $(".catalogItem-cards-carousel").length > 0) {
    $(".catalogItem-cards-carousel").slick({
      dots: false,
      infinite: true,
      speed: 300,
      slidesToShow: 4,
      slidesToScroll: 1,
      autoplay: false,
      arrows: true,
      rtl: isRTL,
      prevArrow:
        '<button class="slick-prev slick-arrow" aria-label="Previous" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
      nextArrow:
        '<button class="slick-next slick-arrow" aria-label="Next" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
      responsive: [
        {
          breakpoint: 992,
          settings: {
            slidesToShow: 3,
          },
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
          },
        },
        {
          breakpoint: 400,
          settings: {
            slidesToShow: 1,
          },
        },
      ],
    });
  }

  //****** 10. COUNTER UP ******//
  if (typeof $.fn.counterUp !== 'undefined' && $(".counter").length > 0) {
    $(".counter").counterUp({
      delay: 10,
      time: 1000,
    });
  }

  //****** 11. SUBMENU & NOTIFICATIONS ******//
  // Hide all other collapses
  $(".has-sub-menu a").on("click", function () {
    $(".collapse").not($(this).next(".collapse")).collapse("hide");
  });

  // merchant notification
  $("#toggle-merchant-noti").on("click", function () {
    $(".gs-merchant-header-noti").toggleClass("active");
  });

  $(document).on("click", function (event) {
    if (!$(event.target).closest(".gs-merchant-header-noti, #toggle-merchant-noti").length) {
      $(".gs-merchant-header-noti").removeClass("active");
    }
  });

  //****** 12. NICEDIT RESIZE ******//
  $(window).on('resize', function() {
    $(".nicEdit-panelContain").parent().width("100%");
    $(".nicEdit-panelContain").parent().next().width("99.6%");
  });

});
