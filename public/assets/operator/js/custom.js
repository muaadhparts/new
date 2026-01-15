(function ($) {
  "use strict";

  $(document).ready(function () {
    // Drop Down Section
 $(".cp").colorpicker();
    $(".dropdown-toggle-1").on("click", function () {
      $(this).parent().siblings().find(".dropdown-menu").hide();
      $(this).next(".dropdown-menu").toggle();
    });

    $(document).on("click", function (e) {
      var container = $(".dropdown-toggle-1");

      // if the target of the click isn't the container nor a descendant of the container
      if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.next(".dropdown-menu").hide();
      }
    });
  });

  // Drop Down Section Ends

  // Side Bar Area Js
  $("#sidebarCollapse").on("click", function () {
    $("#sidebar").toggleClass("active");
  });
  Waves.init();
  Waves.attach(".wave-effect", ["waves-button"]);
  Waves.attach(".wave-effect-float", ["waves-button", "waves-float"]);
  $(".slimescroll-id").slimScroll({
    height: "auto",
  });
  $("#sidebar a").each(function () {
    var pageUrl = window.location.href.split('?')[0]; // Remove query params
    var linkUrl = this.href.split('?')[0]; // Remove query params

    // Check if URLs match (ignoring query params and trailing slashes)
    if (linkUrl.replace(/\/$/, '') == pageUrl.replace(/\/$/, '')) {
      $(this).addClass("active");
      $(this).parent().addClass("active"); // add active to li of the current link

      // For submenu items, expand the parent menu
      var $parentUl = $(this).parent().parent();
      if ($parentUl.hasClass('collapse')) {
        $parentUl.addClass("show"); // Bootstrap 5 collapse show class
        $parentUl.prev().addClass("active"); // add active to parent toggle
        $parentUl.prev().attr("aria-expanded", "true");
      }
    }
  });

  // Side Bar Area Js Ends

  // Nice Select Active js
  $(".select").niceSelect();
  //  Nice Select Ends
})(jQuery);
