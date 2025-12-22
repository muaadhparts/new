(function ($) {
  "use strict";

  // console.log('🚀 myscript.js loaded - v2.0.1 - Currency/Language Selector Fixed');

  // ============================================
  // Currency & Language Selector Handler
  // When user selects currency or language, redirect to the URL in option value
  // ============================================
  $(document).on('change', '.selectors', function() {
    var selectedValue = $(this).val();
    if (selectedValue && selectedValue.startsWith('http')) {
      window.location.href = selectedValue;
    }
  });

  // ✅ Global cart state updater function
  window.applyCartState = function(data) {
    if (!data) {
      console.warn('⚠️ applyCartState called with empty data');
      return;
    }

    const cartCount = data.cart_count || data[0] || 0;
    const cartTotal = data.cart_total || data[1];

    console.log('🔄 Applying cart state:', { cartCount, cartTotal, data });

    // Update all cart count elements with multiple methods for reliability
    const $cartCount = $("#cart-count");
    const $cartCount1 = $("#cart-count1");
    const $headerCartCount = $(".header-cart-count");

    console.log('📍 Found elements:', {
      cartCount: $cartCount.length,
      cartCount1: $cartCount1.length,
      headerCartCount: $headerCartCount.length
    });

    // Try jQuery first
    if ($cartCount.length) {
      $cartCount.html(cartCount);
      $cartCount.text(cartCount);
      console.log('✅ Updated #cart-count to:', cartCount);
    } else {
      console.warn('⚠️ #cart-count element not found with jQuery!');
    }

    if ($cartCount1.length) {
      $cartCount1.html(cartCount);
      $cartCount1.text(cartCount);
      console.log('✅ Updated #cart-count1 to:', cartCount);
    }

    if ($headerCartCount.length) {
      $headerCartCount.html(cartCount);
      $headerCartCount.text(cartCount);
      console.log('✅ Updated .header-cart-count to:', cartCount);
    }

    // Fallback: Try vanilla JavaScript
    const cartCountEl = document.getElementById('cart-count');
    const cartCount1El = document.getElementById('cart-count1');

    if (cartCountEl) {
      cartCountEl.textContent = cartCount;
      cartCountEl.innerHTML = cartCount;
      console.log('✅ Updated #cart-count via vanilla JS to:', cartCount);
    } else {
      console.error('❌ #cart-count not found even with vanilla JS!');
    }

    if (cartCount1El) {
      cartCount1El.textContent = cartCount;
      cartCount1El.innerHTML = cartCount;
      console.log('✅ Updated #cart-count1 via vanilla JS to:', cartCount);
    }

    // Also update by class
    document.querySelectorAll('.header-cart-count').forEach(el => {
      el.textContent = cartCount;
      el.innerHTML = cartCount;
      console.log('✅ Updated .header-cart-count via vanilla JS');
    });

    // Update total if provided
    if (cartTotal) {
      $("#total-cost").html(cartTotal);
    }

    // Reload cart popup
    if (typeof mainurl !== 'undefined') {
      $(".cart-popup").load(mainurl + "/carts/view");
    }
  };



  //   wishlist
  $(document).on("click", ".wishlist", function (e) {
    e.preventDefault();
    const $this = $(this);
    if ($(this).data("href")) {
      $.get($(this).data("href"), function (data) {
        if (data[0] == 1) {
          toastr.success(data["success"]);
          $("#wishlist-count").html(data[1]);
          $this.children().addClass("active");
        } else {
          toastr.error(data["error"]);
        }
      });
    }
  });

  $(document).on("click", ".removewishlist", function (e) {
    e.preventDefault();
    let $this = $(this);
    $.get($(this).attr("data-href"), function (data) {
      $("#wishlist-count").html(data[1]);
      $this.parent().parent().parent().remove();
    });
  });

  //   compare
  $(document).on("click", ".compare_product", function (e) {
    e.preventDefault();
    $.get($(this).data("href"), function (data) {
      $("#compare-count").html(data[1]);
      $("#compare-count1").html(data[1]);
      if (data[0] == 0) {
        toastr.success(data["success"]);
      } else {
        toastr.error(data["error"]);
      }
    });
  });

  // Product Add Qty
  $(document).on("click", ".qtplus", function () {
    var $tselector = $("#order-qty");
    var stock = $("#stock").val();
    var total = parseInt($($tselector).val()) || 1;

    // التحقق من المخزون قبل الزيادة
    if (stock != "" && stock != null) {
      var stk = parseInt(stock);
      if (total < stk) {
        total++;
        $($tselector).val(total);
      }
    } else {
      // إذا لم يكن هناك مخزون محدد، السماح بالزيادة (للمنتجات الرقمية أو preorder)
      total++;
      $($tselector).val(total);
    }
  });

  // Product Minus Qty
  $(document).on("click", ".qtminus", function () {
    var $tselector = $("#order-qty");
    var total = parseInt($($tselector).val()) || 1;

    // الحصول على الحد الأدنى للكمية
    var minQty = parseInt($("#product_minimum_qty").val()) || 1;

    // التحقق من الحد الأدنى للكمية قبل التنقيص
    if (total > minQty) {
      total--;
      $($tselector).val(total);
    }
  });

  $(".qttotal").keypress(function (e) {
    if (this.value.length == 0 && e.which == 48) {
      return false;
    }
    if (e.which != 8 && e.which != 32) {
      if (isNaN(String.fromCharCode(e.which))) {
        e.preventDefault();
      }
    }
  });

  // aDD TO FAVORITE
  $(document).on("click", ".favorite-prod", function () {
    var $this = $(this);
    $.get($(this).data("href"), function (data) {
      $this.attr("data-href", "");
      $this.attr("disabled", true);
      $this.removeClass("favorite-prod");
      $this.html(data["icon"] + " " + data["text"]);
    });
  });

  $(document).on("click", ".stars", function () {
    $(".stars").removeClass("active");
    $(this).addClass("active");
    $("#rating").val($(this).data("val"));
  });

  // ========== Home Product Quantity Controls ==========
  // زيادة الكمية في home_product
  $(document).on('click', '.hp-qtplus', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var targetId = $(this).data('target');
    var $input = $('[id^="qty_' + targetId + '"]');
    if (!$input.length) return;

    var stock = parseInt($(this).data('stock')) || 999;
    var preordered = parseInt($(this).data('preordered')) || 0;
    var current = parseInt($input.val()) || 1;

    // فحص المخزون
    if (stock > 0 && current >= stock && preordered == 0) {
      toastr.warning(lang.stock_limit || 'Stock limit reached');
      return;
    }
    $input.val(current + 1);
  });

  // إنقاص الكمية في home_product
  $(document).on('click', '.hp-qtminus', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var targetId = $(this).data('target');
    var $input = $('[id^="qty_' + targetId + '"]');
    if (!$input.length) return;

    var minQty = parseInt($(this).data('min')) || 1;
    var current = parseInt($input.val()) || 1;

    if (current <= minQty) {
      toastr.warning((lang.minimum_qty || 'Minimum quantity is') + ' ' + minQty);
      return;
    }
    $input.val(current - 1);
  });

  // ============================================
  // CART SYSTEM: All cart functionality uses m-cart-add class
  // Handled exclusively by cart-unified.js via POST /cart/unified
  // Required: data-merchant-product-id, data-qty-input
  // Optional: data-redirect="/cart" for Buy Now
  // ============================================

  // حذف عنصر من المقارنة (Compare)
  $(document).on('click', 'a[href*="compare/remove"]', function (e) {
    e.preventDefault();

    const $btn = $(this);
    const removeUrl = $btn.attr('href');

    if (!removeUrl) {
      console.error('Remove URL not found');
      return;
    }

    console.log('🗑️ Removing from compare:', removeUrl);

    // إزالة صامتة بدون تأكيد
    $.ajax({
      url: removeUrl,
      type: 'GET',
      dataType: 'json',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      success: function(data) {
        console.log('✅ Compare remove response:', data);

        if (data.ok || data.success) {
          toastr.success(data.success || 'Item removed from comparison');

          // Remove the column from table
          const $td = $btn.closest('td');
          const columnIndex = $td.index();

          // Remove all cells in this column
          $td.closest('table').find('tr').each(function() {
            $(this).find('td, th').eq(columnIndex).fadeOut(300, function() {
              $(this).remove();
            });
          });

          // If no items left, reload page to show empty state
          setTimeout(function() {
            if (data.compare_count === 0) {
              location.reload();
            }
          }, 400);
        } else {
          toastr.error(data.error || 'Failed to remove item');
        }
      },
      error: function(xhr, status, error) {
        console.error('❌ Compare remove failed:', error);
        toastr.error('Failed to remove item. Please try again.');
      }
    });
  });

  // زيادة الكمية (+) - للسلة القديمة فقط
  $(document).on('click', '.quantity-up', function (e) {
    e.preventDefault();

    // تخطي إذا كانت داخل السلة الجديدة (m-cart)
    if ($(this).closest('.m-cart').length) {
      return;
    }

    var $box       = $(this).closest('.cart-quantity');
    if (!$box.length) return; // ليست سلة قديمة

    var prodid     = $box.find('.prodid').val();
    var itemid     = $box.find('.itemid').val();
    var domkey     = $box.find('.domkey').val();
    var size_qty   = $box.find('.size_qty').val();
    var size_price = $box.find('.size_price').val();

    var $qtyInput  = $('#qty' + domkey);
    var $priceCell = $('#prc' + domkey);

    $.ajax({
      url: '/addbyone',
      type: 'GET',
      dataType: 'json',
      data: {
        id: prodid,
        itemid: itemid,
        size_qty: size_qty,
        size_price: size_price
      },
      success: function (resp) {
        // صامت - لا رسائل عند عدم توفر المخزون
        if (resp === 0 || resp === '0') {
          return;
        }
        $qtyInput.val(resp[1]);
        $priceCell.html(resp[2] + (resp[4] || ''));
        $('.total-cart-price').html(resp[0]);
      },
      error: function () {
        // صامت
        console.log('Qty increase failed');
      }
    });
  });


  // إنقاص الكمية (-) - للسلة القديمة فقط
  $(document).on('click', '.quantity-down', function (e) {
    e.preventDefault();

    // تخطي إذا كانت داخل السلة الجديدة (m-cart)
    if ($(this).closest('.m-cart').length) {
      return;
    }

    var $box       = $(this).closest('.cart-quantity');
    if (!$box.length) return; // ليست سلة قديمة

    var prodid     = $box.find('.prodid').val();
    var itemid     = $box.find('.itemid').val();
    var domkey     = $box.find('.domkey').val();
    var size_qty   = $box.find('.size_qty').val();
    var size_price = $box.find('.size_price').val();
    var minQty     = parseInt($box.find('.minimum_qty').val() || '1', 10);

    var $qtyInput  = $('#qty' + domkey);
    var $priceCell = $('#prc' + domkey);

    var currentQty = parseInt($qtyInput.val() || '1', 10);

    // صامت - تحقق من الحد الأدنى بدون رسائل
    if (minQty < 1) minQty = 1;
    if (currentQty <= minQty) {
      return;
    }

    $.ajax({
      url: '/reducebyone',
      type: 'GET',
      dataType: 'json',
      data: {
        id: prodid,
        itemid: itemid,
        size_qty: size_qty,
        size_price: size_price
      },
      success: function (resp) {
        // صامت
        if (resp === 0 || resp === '0') {
          return;
        }
        $qtyInput.val(resp[1]);
        $priceCell.html(resp[2] + (resp[4] || ''));
        $('.total-cart-price').html(resp[0]);
      },
      error: function () {
        // صامت
        console.log('Qty decrease failed');
      }
    });
  });

  $(document).on("click", ".cart_size", function () {
    let qty = $(this).data("qty");
    $("#stock").val(qty);
    updateProductPrice();
  });
  $(document).on("click", ".cart_color", function () {
    updateProductPrice();
  });
  $(document).on("click", ".cart_attr", function () {
    updateProductPrice();
  });

  function updateProductPrice() {
    let size_price = $(".cart_size input:checked").attr("data-price");
    let color_price = $(".cart_color input:checked").attr("data-price");
    let attr_price = $(".cart_attr:checked")
      .map(function () {
        return $(this).data("price");
      })
      .get()
      .reduce((a, b) => a + b, 0);
    let main_price = $("#product_price").val();

    if (size_price == undefined) {
      size_price = 0;
    }
    if (color_price == undefined) {
      color_price = 0;
    }

    let total =
      parseFloat(size_price) +
      parseFloat(color_price) +
      parseFloat(attr_price) +
      parseFloat(main_price);

    var pos = $("#curr_pos").val();
    var sign = $("#curr_sign").val();
    if (pos == "0") {
      $("#sizeprice").html(sign + total);
    } else {
      $("#sizeprice").html(total + sign);
    }
  }



  // ============================================
  // Product Card Gallery - Switch images on indicator hover/click
  // ============================================
  $(document).on('mouseenter click', '.m-product-card__indicator', function() {
    var $indicator = $(this);
    var $card = $indicator.closest('.m-product-card');
    var index = $indicator.data('index');

    // Update indicators
    $card.find('.m-product-card__indicator').removeClass('active');
    $indicator.addClass('active');

    // Update images
    $card.find('.m-product-card__img').removeClass('active');
    $card.find('.m-product-card__img[data-index="' + index + '"]').addClass('active');
  });

  // Auto-cycle images on card hover (optional - subtle effect)
  var cardHoverInterval = null;
  $(document).on('mouseenter', '.m-product-card__image', function() {
    var $imageContainer = $(this);
    var $card = $imageContainer.closest('.m-product-card');
    var $indicators = $card.find('.m-product-card__indicator');

    if ($indicators.length <= 1) return;

    var currentIndex = 0;
    cardHoverInterval = setInterval(function() {
      currentIndex = (currentIndex + 1) % $indicators.length;
      $indicators.eq(currentIndex).trigger('mouseenter');
    }, 2000);
  });

  $(document).on('mouseleave', '.m-product-card__image', function() {
    if (cardHoverInterval) {
      clearInterval(cardHoverInterval);
      cardHoverInterval = null;
    }
    // Reset to first image
    var $card = $(this).closest('.m-product-card');
    $card.find('.m-product-card__indicator').first().trigger('mouseenter');
  });

})(jQuery);
