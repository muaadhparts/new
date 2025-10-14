(function ($) {
  "use strict";



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
    var total = $($tselector).val();
    if (stock != "") {
      var stk = parseInt(stock);
      if (total < stk) {
        total++;
        $($tselector).val(total);
      }
    } else {
      total++;
    }

    $($tselector).val(total);
  });

  // Product Minus Qty
  $(document).on("click", ".qtminus", function () {
    var $tselector = $("#order-qty");
    var total = $($tselector).val();
    if (total > 1) {
      total--;
    }
    $($tselector).val(total);
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

  // add to card
  $(document).on("click", ".add_cart_click", function (e) {
    e.preventDefault();

    // Get merchant product ID from data attribute
    const mpId = $(this).data('merchant-product');
    const href = $(this).attr("data-href");

    // Use merchant product ID if available, otherwise fallback to href
    const requestUrl = mpId ? href : href;

    $.get(requestUrl, function (data) {
      if (data == "digital") {
        toastr.error(lang.cart_already);
      } else if (data[0] == 0) {
        toastr.error(lang.cart_out);
      } else {
        $("#cart-count").html(data[0]);
        $("#cart-count1").html(data[0]);
        $("#total-cost").html(data[1]);
        $(".cart-popup").load(mainurl + "/carts/view");
        toastr.success(lang.cart_success);
      }
    });
    return true;
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
    var minQty     = parseInt($box.find('.minimum_qty').val() || '0', 10);

    var $qtyInput  = $('#qty' + domkey);
    var $priceCell = $('#prc' + domkey);
    var $stock     = $('#stock' + domkey);

    var currentQty = parseInt($qtyInput.val() || '0', 10);
    var maxStock   = parseInt($stock.val() || '0', 10);

    // تحقّق محلي سريع قبل الطلب (الخادم سيتحقق أيضًا)
    if (maxStock > 0 && (currentQty + 1) > maxStock) {
      if (typeof $.notify === 'function') { $.notify('غير متوفر', 'error'); }
      else if (typeof toastr !== 'undefined') { toastr.error('غير متوفر'); }
      else { alert('غير متوفر'); }
      return;
    }

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
          if (typeof $.notify === 'function') { $.notify('غير متوفر', 'error'); }
          else if (typeof toastr !== 'undefined') { toastr.error('غير متوفر'); }
          else { alert('غير متوفر'); }
          return;
        }

        // resp[1] = qty, resp[2] = row price (formatted), resp[0] = cart total (formatted), resp[4] = خصم إن وُجد
        $qtyInput.val(resp[1]);
        $priceCell.html(resp[2] + (resp[4] || ''));
        $('.total-cart-price').html(resp[0]);
      },
      error: function () {
        if (typeof $.notify === 'function') { $.notify('حدث خطأ غير متوقع', 'error'); }
        else if (typeof toastr !== 'undefined') { toastr.error('حدث خطأ غير متوقع'); }
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
    var minQty     = parseInt($box.find('.minimum_qty').val() || '0', 10);

    var $qtyInput  = $('#qty' + domkey);
    var $priceCell = $('#prc' + domkey);

    var currentQty = parseInt($qtyInput.val() || '0', 10);

    // احترم الحد الأدنى إن وُجد
    if (minQty > 0 && (currentQty - 1) < minQty) {
      if (typeof $.notify === 'function') { $.notify('لا يمكن النزول عن الحد الأدنى', 'warning'); }
      else if (typeof toastr !== 'undefined') { toastr.warning('لا يمكن النزول عن الحد الأدنى'); }
      else { alert('لا يمكن النزول عن الحد الأدنى'); }
      return;
    }

    // إن كان سيصبح 0، اترك منطق الحذف الحالي يتولى (إن كان عندك زر حذف)
    if ((currentQty - 1) < 1) {
      // يمكنك هنا الاكتفاء بعدم الاستدعاء أو الاعتماد على زر الحذف
      // return;
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
          if (typeof $.notify === 'function') { $.notify('غير متوفر', 'error'); }
          else if (typeof toastr !== 'undefined') { toastr.error('غير متوفر'); }
          else { alert('غير متوفر'); }
          return;
        }

        $qtyInput.val(resp[1]);
        $priceCell.html(resp[2] + (resp[4] || ''));
        $('.total-cart-price').html(resp[0]);
      },
      error: function () {
        if (typeof $.notify === 'function') { $.notify('حدث خطأ غير متوقع', 'error'); }
        else if (typeof toastr !== 'undefined') { toastr.error('حدث خطأ غير متوقع'); }
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

    //return true;

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
        } else if (data == 0) {
          toastr.error("Out Of Stock");
        } else if (data[3]) {
          toastr.error(lang.minimum_qty_error + " " + data[4]);
        } else {
          $("#cart-count").html(data[0]);
          $("#cart-count1").html(data[0]);
          $(".cart-popup").load(mainurl + "/carts/view");
          $("#cart-items").load(mainurl + "/carts/view");
          toastr.success("Successfully Added To Cart");
        }
      },
    });
  });






  $(document).on("click", "#addtobycard", function () {
    let pid = "";
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
  });
})(jQuery);
