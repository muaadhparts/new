(function ($) {
  "use strict";

  // console.log('🚀 myscript.js loaded - v2.0.0 - Cart Update Fixed');

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

  // add to card
  $(document).on("click", ".add_cart_click", function (e) {
    e.preventDefault();

    // Get merchant product ID from data attribute
    const mpId = $(this).data('merchant-product');
    const href = $(this).attr("data-href");
    const qtyPrefix = $(this).data('qty-prefix');

    // جلب الكمية من حقل الكمية إذا كان موجوداً
    let qty = 1;
    if (qtyPrefix) {
      var $qtyInput = $('[id^="qty_' + qtyPrefix + '"]');
      if ($qtyInput.length) {
        qty = parseInt($qtyInput.val()) || 1;
      }
    }

    // بناء URL مع الكمية
    let requestUrl = href;
    if (href.indexOf('?') > -1) {
      requestUrl += '&qty=' + qty;
    } else {
      requestUrl += '?qty=' + qty;
    }

    console.log('🛒 Adding to cart:', requestUrl, 'qty:', qty);

    $.get(requestUrl, function (data) {
      console.log('📦 Cart response:', data);

      if (data == "digital") {
        toastr.error(lang.cart_already);
      } else if (data.ok === false || data[0] == 0) {
        toastr.error(data.msg || data.error || lang.cart_out);
      } else {
        // Use global cart state updater
        window.applyCartState(data);

        const successMsg = data.success || lang.cart_success;
        toastr.success(successMsg);
      }
    }).fail(function(xhr, status, error) {
      console.error('❌ Cart add failed:', error, xhr.responseText);
      toastr.error('Failed to add to cart. Please try again.');
    });
    return true;
  });

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

  // زيادة الكمية (+)
  $(document).on('click', '.quantity-up', function (e) {
    e.preventDefault();

    var $box       = $(this).closest('.cart-quantity');
    var prodid     = $box.find('.prodid').val();
    var itemid     = $box.find('.itemid').val();     // مفتاح السلة الحقيقي (id:u{vendor}:{size}:{color}:{values})
    var domkey     = $box.find('.domkey').val();     // نسخة آمنة للـ DOM
    var size_qty   = $box.find('.size_qty').val();
    var size_price = $box.find('.size_price').val();

    var $qtyInput  = $('#qty' + domkey);
    var $priceCell = $('#prc' + domkey);

    // نعتمد على الـ server للتحقق من المخزون - لا فحص محلي
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
        if (resp === 0 || resp === '0') {
          if (typeof toastr !== 'undefined') { toastr.error('غير متوفر في المخزون'); }
          else { alert('غير متوفر في المخزون'); }
          return;
        }

        // resp[1] = qty, resp[2] = row price (formatted), resp[0] = cart total (formatted), resp[4] = خصم إن وُجد
        $qtyInput.val(resp[1]);
        $priceCell.html(resp[2] + (resp[4] || ''));
        $('.total-cart-price').html(resp[0]);
      },
      error: function () {
        if (typeof toastr !== 'undefined') { toastr.error('حدث خطأ غير متوقع'); }
        else { alert('حدث خطأ غير متوقع'); }
      }
    });
  });


  // إنقاص الكمية (-)
  $(document).on('click', '.quantity-down', function (e) {
    e.preventDefault();

    var $box       = $(this).closest('.cart-quantity');
    var prodid     = $box.find('.prodid').val();
    var itemid     = $box.find('.itemid').val();     // مفتاح السلة الحقيقي
    var domkey     = $box.find('.domkey').val();     // نسخة آمنة للـ DOM
    var size_qty   = $box.find('.size_qty').val();
    var size_price = $box.find('.size_price').val();
    var minQty     = parseInt($box.find('.minimum_qty').val() || '1', 10);

    var $qtyInput  = $('#qty' + domkey);
    var $priceCell = $('#prc' + domkey);

    var currentQty = parseInt($qtyInput.val() || '1', 10);

    // فحص محلي سريع للحد الأدنى
    if (minQty < 1) minQty = 1;
    if (currentQty <= minQty) {
      if (typeof toastr !== 'undefined') { toastr.warning('الحد الأدنى للكمية هو ' + minQty); }
      else { alert('الحد الأدنى للكمية هو ' + minQty); }
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
        if (resp === 0 || resp === '0') {
          if (typeof toastr !== 'undefined') { toastr.warning('وصلت للحد الأدنى للكمية'); }
          else { alert('وصلت للحد الأدنى للكمية'); }
          return;
        }

        $qtyInput.val(resp[1]);
        $priceCell.html(resp[2] + (resp[4] || ''));
        $('.total-cart-price').html(resp[0]);
      },
      error: function () {
        if (typeof toastr !== 'undefined') { toastr.error('حدث خطأ غير متوقع'); }
        else { alert('حدث خطأ غير متوقع'); }
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

  $(document).on("click", "#addtodetailscart", function (e) {
    let pid = "";
    let mpId = "";
    let qty = "";
    let size_key = "";
    let size = "";
    let size_qty = "";
    let size_price = "";
    let color = "";
    let color_price = "";
    let values = "";
    let keys = "";
    let prices = "";

    // get all the input values
    pid = $("#product_id").val();
    mpId = $("#merchant_product_id").val();
    qty = $("#order-qty").val();
    size_key = $(".cart_size input:checked").val();
    size = $(".cart_size input:checked").attr("data-key");
    size_qty = $(".cart_size input:checked").attr("data-qty");
    size_price = $(".cart_size input:checked").attr("data-price");
    color = $(".cart_color input:checked").attr("data-color");
    color_price = $(".cart_color input:checked").attr("data-price");
    values = $(".cart_attr:checked")
      .map(function () {
        return $(this).val();
      })
      .get();
    keys = $(".cart_attr:checked")
      .map(function () {
        return $(this).attr("data-key");
      })
      .get();
    prices = $(".cart_attr:checked")
      .map(function () {
        return $(this).attr("data-price");
      })
      .get();

    // إذا كان هناك merchant_product_id، استخدم النظام الجديد
    if (mpId && mpId !== '') {
      $.ajax({
        type: "GET",
        url: mainurl + "/cart/v2/add/" + mpId,
        data: {
          qty: qty,
          size: size || '',
          color: color ? color.replace('#', '') : '',
          values: values.join(','),
          keys: keys.join(',')
        },
        success: function (data) {
          if (data.ok === false) {
            toastr.error(data.msg || data.error || "Out Of Stock");
          } else {
            // Use global cart state updater
            window.applyCartState(data);
            toastr.success(data.success || "Successfully Added To Cart");
          }
        },
        error: function(xhr) {
          var msg = "Out Of Stock";
          if (xhr.responseJSON && xhr.responseJSON.msg) {
            msg = xhr.responseJSON.msg;
          }
          toastr.error(msg);
        }
      });
    } else {
      // Fallback للنظام القديم
      $.ajax({
        type: "GET",
        url: mainurl + "/addnumcart",
        data: {
          id: pid,
          qty: qty,
          size: size,
          color: color,
          color_price: color_price,
          size_qty: size_qty,
          size_price: size_price,
          size_key: size_key,
          keys: keys,
          values: values,
          prices: prices,
        },
        success: function (data) {
          if (data == "digital") {
            toastr.error("Already Added To Cart");
          } else if (data == 0 || data.ok === false) {
            toastr.error(data.msg || data.error || "Out Of Stock");
          } else if (data[3]) {
            toastr.error(lang.minimum_qty_error + " " + data[4]);
          } else {
            // Use global cart state updater
            window.applyCartState(data);
            toastr.success(data.success || "Successfully Added To Cart");
          }
        },
      });
    }
  });






  $(document).on("click", "#addtobycard", function () {
    let pid = "";
    let mpId = "";
    let qty = "";
    let size_key = "";
    let size = "";
    let size_qty = "";
    let size_price = "";
    let color = "";
    let color_price = "";
    let values = "";
    let keys = "";
    let prices = "";

    // get all the input values
    pid = $("#product_id").val();
    mpId = $("#merchant_product_id").val();
    qty = $("#order-qty").val();
    size_key = $(".cart_size input:checked").val();
    size = $(".cart_size input:checked").attr("data-key");
    size_qty = $(".cart_size input:checked").attr("data-qty");
    size_price = $(".cart_size input:checked").attr("data-price");
    color = $(".cart_color input:checked").attr("data-color");

    if (size_key == undefined) {
      size_key = "";
    }
    if (size == undefined) {
      size = "";
    }
    if (size_qty == undefined) {
      size_qty = "";
    }

    if (color != undefined) {
      color = color.replace("#", "");
    } else {
      color = "";
    }

    color_price = $(".cart_color input:checked").attr("data-price");
    values = $(".cart_attr:checked")
      .map(function () {
        return $(this).val();
      })
      .get();
    keys = $(".cart_attr:checked")
      .map(function () {
        return $(this).attr("data-key");
      })
      .get();
    prices = $(".cart_attr:checked")
      .map(function () {
        return $(this).attr("data-price");
      })
      .get();

    // إذا كان هناك merchant_product_id، استخدم النظام الجديد
    if (mpId && mpId !== '') {
      // إضافة للسلة ثم الانتقال للدفع
      $.ajax({
        type: "GET",
        url: mainurl + "/cart/v2/add/" + mpId,
        data: {
          qty: qty,
          size: size || '',
          color: color || '',
          values: values.join ? values.join(',') : values,
          keys: keys.join ? keys.join(',') : keys
        },
        success: function (data) {
          if (data.ok === false) {
            toastr.error(data.msg || data.error || "Out Of Stock");
          } else {
            // الانتقال للدفع
            window.location = mainurl + "/cart";
          }
        },
        error: function(xhr) {
          var msg = "Out Of Stock";
          if (xhr.responseJSON && xhr.responseJSON.msg) {
            msg = xhr.responseJSON.msg;
          }
          toastr.error(msg);
        }
      });
    } else {
      // Fallback للنظام القديم
      window.location =
        mainurl +
        "/addtonumcart?id=" +
        pid +
        "&qty=" +
        qty +
        "&size=" +
        size +
        "&color=" +
        color +
        "&color_price=" +
        color_price +
        "&size_qty=" +
        size_qty +
        "&size_price=" +
        size_price +
        "&size_key=" +
        size_key +
        "&keys=" +
        keys +
        "&values=" +
        values +
        "&prices=" +
        prices;
    }
  });
})(jQuery);
