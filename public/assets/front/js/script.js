$(document).ready(function () {
  /*
  1. DATA BACKGROUND SET
  2. MOBILE MENU
  3. STICKY HEADER
  4. SEARCH BAR
  5. NICE SELECT
  6. WOW JS
  7. HIDE & SHOW PASSWORD
  8. COUNTDOWN STARTS
  9. HERO SECTION SLIDER
  10. HOME CATE SLIDER
  11. PRODUCT DETAILS SLIDER
  12. PRICE RANGE SLIDER
  13. ADD DYNAMIC CLASS FOR SVG
  14. TOGGLE VENDOR DASHBOARD SIDEBAR
  15. DATA TABLE
  16. PRODUCT CARDS SLIDER
  17. COUNTER UP
  18. CHANGE FILE NAME OF FILE INPUT
  19. TOGGLING ADD PRODUCT FORM  BASED ON SELECTED PRODUCT TYPE
  20. APEXCHART
  21. MUAADH MODERN HEADER

    */

  //****** 21. MUAADH ELEGANT HEADER ******//
  (function() {
    // Elements - Check which menu type exists on this page
    const $muaadhMobileMenu = $('.muaadh-mobile-menu');
    const $muaadhOverlay = $('.muaadh-mobile-overlay');
    const $mobileClose = $('.muaadh-mobile-close');

    // Legacy menu elements (for User/Vendor/Courier pages that use .mobile-menu)
    const $legacyMenu = $('.mobile-menu');
    const $legacyOverlay = $('.overlay');

    // Determine which menu system is available on this page
    const hasMuaadhMenu = $muaadhMobileMenu.length > 0;
    const hasLegacyMenu = $legacyMenu.length > 0;

    // Mobile Menu Toggle - Support both old and new triggers
    // SMART FALLBACK: If .muaadh-mobile-menu doesn't exist, open .mobile-menu instead
    $('.muaadh-mobile-toggle, .muaadh-menu-trigger').on('click', function(e) {
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

    $('.muaadh-accordion-toggle-btn').on('click', function() {
      const $accordion = $(this).closest('.muaadh-mobile-nav-accordion');
      const $content = $accordion.find('> .muaadh-accordion-content');

      $(this).toggleClass('active');
      $content.toggleClass('active');
    });

    // Header sticky on scroll
    $(window).on('scroll', function() {
      if ($(this).scrollTop() > 100) {
        $('.muaadh-header').addClass('scrolled');
      } else {
        $('.muaadh-header').removeClass('scrolled');
      }
    });
  })();


  //****** 1. DATA BACKGROUND SET ******//
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

  //****** 2. MOBILE MENU (Unified Handler) ******//
  // This handles BOTH types of mobile menus:
  // 1. .muaadh-mobile-menu (Frontend/Store) - opened by .muaadh-mobile-toggle
  // 2. .mobile-menu (Vendor/Admin/User/Courier dashboards) - opened by .header-toggle, .mobile-menu-toggle
  //
  // Each menu type works independently - no conflicts between them.

  const $overlay = $(".overlay");
  const $legacyMobileMenu = $(".mobile-menu");
  const $muaadhMobileMenu = $('.muaadh-mobile-menu');
  const $muaadhOverlay = $('.muaadh-mobile-overlay');

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
  $(".mobile-menu .close, .mobile-menu .btn-close").on("click", function (e) {
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

  //****** 3. STICKY HEADER ******//
  const $header = $(".header-top");
  $(window).on("scroll", function () {
    if ($(this).scrollTop() > 65) {
      $header.addClass("sticky");
    } else {
      $header.removeClass("sticky");
    }
  });

  //******  4. SEARCH BAR ******//
  const $searchIcon = $("#searchIcon");
  const $searchBar = $("#searchBar");

  $searchIcon.on("click", function () {
    $searchBar.addClass("show");
    $overlay.addClass("active");
  });

  // Note: overlay click handler is already defined in section 2 (MOBILE MENU)
  // Adding search bar close functionality to the same overlay
  $overlay.on("click", function () {
    $searchBar.removeClass("show");
  });

  //******  5. NICE SELECT ******//
  // $(".nice-select").niceSelect();

  //****** 6. WOW JS ******//
  if (typeof WOW !== 'undefined') {
    new WOW().init();
  }

  //******  7. HIDE & SHOW PASSWORD ******//
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

  //******  8. COUNTDOWN STARTS ******//
  const cd = document.getElementById("countdown-date");
  if (cd) {
    var countdownDate = new Date(cd.value).getTime();

    var countdownInterval = setInterval(function () {
      var now = new Date().getTime();
      var distance = countdownDate - now;
      var days = Math.floor(distance / (1000 * 60 * 60 * 24));
      var hours = Math.floor(
        (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
      );
      var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      var seconds = Math.floor((distance % (1000 * 60)) / 1000);

      document.getElementById("days").textContent = days;
      document.getElementById("hours").textContent = hours;
      document.getElementById("minutes").textContent = minutes;
      document.getElementById("seconds").textContent = seconds;

      if (distance < 0) {
        clearInterval(countdownInterval);
        document.getElementById("countdown").innerHTML = "EXPIRED";
      }
    }, 1000);
  }

  // Detect RTL mode (used for all sliders)
  var isRTL = $('html').attr('dir') === 'rtl' || $('body').attr('dir') === 'rtl';

  //****** 9. HERO SECTION SLIDER ******//
  if (typeof $.fn.slick !== 'undefined' && $(".hero-slider-wrapper").length > 0) {
    $(".hero-slider-wrapper").slick({
      arrows: true,
      dots: false,
      infinite: true,
      speed: 300,
      slidesToShow: 1,
      autoplay: true,
      prevArrow:
        '<button class="slick-prev slick-arrow" aria-label="Previous" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
      nextArrow:
        '<button class="slick-next slick-arrow" aria-label="Next" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
    });
  }

  //****** 10. HOME CATE SLIDER ******//
  if (typeof $.fn.slick !== 'undefined' && $(".home-category-slider").length > 0) {
    $(".home-category-slider").slick({
      dots: false,
      infinite: true,
      speed: 300,
      slidesToShow: 6,
      slidesToScroll: 1,
      autoplay: false,
      arrows: true,
      prevArrow:
        '<button class="slick-prev slick-arrow" aria-label="Previous" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
      nextArrow:
        '<button class="slick-next slick-arrow" aria-label="Next" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
      responsive: [
        {
          breakpoint: 1200,
          settings: {
            slidesToShow: 5,
          },
        },
        {
          breakpoint: 992,
          settings: {
            slidesToShow: 4,
          },
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
          },
        },
        {
          breakpoint: 425,
          settings: {
            slidesToShow: 1,
          },
        },
      ],
    });
  }

  //****** 11. PRODUCT DETAILS SLIDER ******//

  if (typeof $.fn.slick !== 'undefined' && $(".catalogItem-main-slider").length > 0) {
    $(".catalogItem-main-slider").slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: false,
      // fade: true,
      asNavFor: ".catalogItem-nav-slider",
    });
  }
  if (typeof $.fn.slick !== 'undefined' && $(".catalogItem-nav-slider").length > 0) {
    $(".catalogItem-nav-slider").slick({
      slidesToShow: 3,
      slidesToScroll: 1,
      asNavFor: ".catalogItem-main-slider",
      dots: false,
      focusOnSelect: true,
      variableWidth: true,
    });
  }

  //****** 12. ADD DYNAMIC CLASS FOR SVG ******//
  $(".gs-dashboard-user-sidebar-wrapper a").on("click", function () {
    // Remove the .active class from all <a> tags
    $(".gs-dashboard-user-sidebar-wrapper a").parent().removeClass("active");

    // Add the .active class to the clicked <a> tag
    $(this).parent().addClass("active");
  });

  $(
    ".gs-dashboard-user-sidebar-wrapper svg, .user-dropdown-wrapper svg"
  ).each(function () {
    var $img = $(this);
    var imgID = $img.attr("id");
    var imgClass = $img.attr("class");
    var imgURL = $img.attr("src");
    $.get(
      imgURL,
      function (data) {
        // Get the SVG tag, ignore the rest
        var $svg = $(data).find("svg");
        // Add replaced image's ID to the new SVG
        if (typeof imgID !== "undefined") {
          $svg = $svg.attr("id", imgID);
        }
        // Add replaced image's classes to the new SVG
        if (typeof imgClass !== "undefined") {
          $svg = $svg.attr("class", imgClass + " replaced-svg");
        }
        // Remove any invalid XML tags as per http://validator.w3.org
        $svg = $svg.removeAttr("xmlns:a");
        // Replace image with new SVG
        $img.replaceWith($svg);
      },
      "xml"
    );
  });

  //******  14. TOGGLE MERCHANT DASHBOARD SIDEBAR ******//
  $(".gs-merchant-toggle-btn").on("click", function () {
    $(".gs-merchant-sidebar-wrapper").toggleClass("collapsed");
    $(".gs-merchant-header-outlet-wrapper").toggleClass("increased-width");
  });

  //******  15. DATA TABLE ******//
  // new DataTable("#example", {
  //   layout: {
  //     bottomEnd: {
  //       paging: {
  //         boundaryNumbers: false,
  //       },
  //     },
  //   },
  // });

  //****** 16. PRODUCT CARDS SLIDER ******//
  // Updated to match grid layout: col-6 col-md-4 col-lg-3 (4 cards on lg, 3 on md, 2 on sm)
  if (typeof $.fn.slick !== 'undefined' && $(".catalogItem-cards-slider").length > 0) {
    $(".catalogItem-cards-slider").slick({
      dots: false,
      infinite: true,
      speed: 300,
      slidesToShow: 4,
      slidesToScroll: 1,
      autoplay: false,
      arrows: true,
      prevArrow:
        '<button class="slick-prev slick-arrow" aria-label="Previous" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
      nextArrow:
        '<button class="slick-next slick-arrow" aria-label="Next" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>',
      responsive: [
        {
          breakpoint: 1200,
          settings: {
            slidesToShow: 4,
          },
        },
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
          breakpoint: 576,
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

  //****** 17. COUNTER UP ******//
  if (typeof $.fn.counterUp !== 'undefined' && $(".counter").length > 0) {
    $(".counter").counterUp({
      delay: 10,
      time: 1000,
    });
  }

  //****** 18. CHANGE FILE NAME OF FILE INPUT ******//
  $('.custom-file-input').on('change', function () {
    var filename = $(this).val().split('\\').pop();
    $(this).siblings('.fileName').text(filename || "No file chosen"); // Update the corresponding label text
  });




  //****** 19. TOGGLING ADD PRODUCT FORM  BASED ON SELECTED PRODUCT TYPE ******//
  const $physicalProductInputesWrapper = $(".physical-catalogItem-inputes-wrapper");
  const $digitalProductInputesWrapper = $(".digital-catalogItem-inputes-wrapper");

  $(".physical-catalogItem-radio").on("click", function () {
    $physicalProductInputesWrapper.addClass("show");
    $digitalProductInputesWrapper.removeClass("show");
  });
  $(".digital-catalogItem-radio").on("click", function () {
    $digitalProductInputesWrapper.addClass("show");
    $physicalProductInputesWrapper.removeClass("show");
  });

  //****** 20. APEXCHART ******//
  // var options = {
  //   series: [{
  //     name: 'Inflation',
  //     data: [2.3, 3.1, 4.0, 10.1, 4.0, 3.6, 3.2, 2.3, 1.4, 0.8, 0.5, 0.2]
  //   }],
  //   chart: {
  //     height: 350,
  //     type: 'bar',
  //   },
  //   plotOptions: {
  //     bar: {
  //       borderRadius: 10,
  //       dataLabels: {
  //         position: 'top', // top, center, bottom
  //       },
  //     }
  //   },
  //   dataLabels: {
  //     enabled: true,
  //     formatter: function (val) {
  //       return val + "%";
  //     },
  //     offsetY: -20,
  //     style: {
  //       fontSize: '12px',
  //       colors: ["#304758"]
  //     }
  //   },

  //   xaxis: {
  //     categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
  //     position: 'top',
  //     axisBorder: {
  //       show: false
  //     },
  //     axisTicks: {
  //       show: false
  //     },
  //     crosshairs: {
  //       fill: {
  //         type: 'gradient',
  //         gradient: {
  //           colorFrom: '#D8E3F0',
  //           colorTo: '#BED1E6',
  //           stops: [0, 100],
  //           opacityFrom: 0.4,
  //           opacityTo: 0.5,
  //         }
  //       }
  //     },
  //     tooltip: {
  //       enabled: true,
  //     }
  //   },
  //   yaxis: {
  //     axisBorder: {
  //       show: false
  //     },
  //     axisTicks: {
  //       show: false,
  //     },
  //     labels: {
  //       show: false,
  //       formatter: function (val) {
  //         return val + "%";
  //       }
  //     }

  //   },
  //   name: {
  //     text: 'Monthly Inflation in Argentina, 2002',
  //     floating: true,
  //     offsetY: 330,
  //     align: 'center',
  //     style: {
  //       color: '#444'
  //     }
  //   }
  // };

  // var chart = new ApexCharts($("#chart")[0], options);
  // chart.render();

  // var options = {
  //   series: [{
  //     name: 'series1',
  //     data: [31, 40, 28, 51, 42, 109, 100]
  //   }, {
  //     name: 'series2',
  //     data: [11, 32, 45, 32, 34, 52, 41]
  //   }],
  //   chart: {
  //     height: 350,
  //     type: 'area'
  //   },
  //   dataLabels: {
  //     enabled: false
  //   },
  //   stroke: {
  //     curve: 'smooth'
  //   },
  //   xaxis: {
  //     type: 'datetime',
  //     categories: ["2018-09-19T00:00:00.000Z", "2018-09-19T01:30:00.000Z", "2018-09-19T02:30:00.000Z", "2018-09-19T03:30:00.000Z", "2018-09-19T04:30:00.000Z", "2018-09-19T05:30:00.000Z", "2018-09-19T06:30:00.000Z"]
  //   },
  //   tooltip: {
  //     x: {
  //       format: 'dd/MM/yy HH:mm'
  //     },
  //   },
  // };

  // var chart = new ApexCharts(document.querySelector("#chart"), options);
  // chart.render();

  // var options = {
  //   series: [{
  //     name: 'Net Profit',
  //     data: [44, 55, 57, 56, 61, 58, 63, 60, 66, 55, 40, 77, 55, 77, 55]
  //   }, {
  //     name: 'Revenue',
  //     data: [76, 85, 101, 98, 87, 105, 91, 114, 94, 105, 101, 98, 87, 76, 85]
  //   }],
  //   chart: {
  //     type: 'bar',
  //     height: 350
  //   },
  //   plotOptions: {
  //     bar: {
  //       horizontal: false,
  //       columnWidth: '55%',
  //       endingShape: 'rounded'
  //     },
  //   },
  //   dataLabels: {
  //     enabled: false
  //   },
  //   stroke: {
  //     show: true,
  //     width: 2,
  //     colors: ['transparent']
  //   },
  //   xaxis: {
  //     categories: ['01 Jun', '03 Jun', '05 Jun', '07 Jun', '09 Jun', '11 Jun', '13 Jun', '15 Jun', '17 Jun', '19 Jun', '21 Jun', '23 Jun', '25 Jun', '27 Jun', '29 Jun',],
  //   },
  //   fill: {
  //     opacity: 1
  //   },
  //   tooltip: {
  //     y: {
  //       formatter: function (val) {
  //         return "$ " + val + " thousands"
  //       }
  //     }
  //   }
  // };
  // var chart = new ApexCharts($("#chart")[0], options);
  // chart.render();


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



  $(window).on('resize', function() {
    $(".nicEdit-panelContain").parent().width("100%");
    $(".nicEdit-panelContain").parent().next().width("99.6%");
});


});
