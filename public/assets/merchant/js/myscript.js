(function ($) {
  "use strict";


  $("#metatags").tagit({
    fieldName: "meta_tag[]",
    allowSpaces: true,
  });

  $("#tags").tagit({
    fieldName: "tags[]",
    allowSpaces: true,
  });

  // Display Subcategories & attributes
  $(document).on("change", "#cat", function () {
   
    var link = $(this).find(":selected").attr("data-href");

    if (link != "") {
        // Disable the select and destroy Nice Select before loading new content
       
        $("#subcat").prop("disabled", true);
    
        // Load new content into #subcat
        $("#subcat").load(link, function() {
            // Re-enable the select and reinitialize Nice Select after the content is loaded
            $("#subcat").prop("disabled", false);
            $("#subcat").niceSelect("destroy");
            $("#subcat").niceSelect();
        });
    }
    $.get(getattrUrl + "?id=" + this.value + "&type=category", function (data) {
      let attrHtml = "";
      for (var i = 0; i < data.length; i++) {
        attrHtml += `
        <div class="row mt-4">
          <div class="col-lg-12">
            <div class="input-label-wrapper mb-4">
                <label >${data[i].attribute.name} *</label>
            </div>
          </div>
          <div class="col-lg-12 d-flex flex-column gap-4">
        `;

        for (var j = 0; j < data[i].options.length; j++) {
          let priceClass = "";
          if (data[i].attribute.price_status == 0) {
            priceClass = "d-none";
          }
          attrHtml += `
            <div class="row mb-0 option-row">
              <div class="col-md-5 d-flex align-items-center">
                <div class="custom-control custom-checkbox gs-checkbox-wrapper mt-2">
                  <input type="checkbox" id="${data[i].attribute.input_name}${data[i].options[j].id}" name="${data[i].attribute.input_name}[]" value="${data[i].options[j].name}" class="custom-control-input attr-checkbox">

                  <label class="icon-label" for="${data[i].attribute.input_name}${data[i].options[j].id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 12 12" fill="none">
                                            <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </label>

                  <label class="custom-control-label" for="${data[i].attribute.input_name}${data[i].options[j].id}">${data[i].options[j].name}</label>
                </div>
              </div>
              <div class="col-md-7 ${priceClass}">
                <div class="row align-items-center">
                  <div class="col-2">
                    +
                  </div>
                  <div class="col-10">
                    <div class="price-container d-flex align-items-center">
                      <span class="price-curr">${curr.sign}</span>
                      <input type="text" class="flex-grow-1 form-control form input-field price-input" id="${data[i].attribute.input_name}${data[i].options[j].id}_price" data-name="${data[i].attribute.input_name}_price[]" placeholder="${lang.additional_price}" value="">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;
        }

        attrHtml += `
          </div>
        </div>
        `;
      }
      $("#showAttr").removeClass("d-none");
      $("#catAttributes").html(attrHtml);
      $("#subcatAttributes").html("");
      $("#childcatAttributes").html("");
    });

  
  });
  // Display Subcategories Ends

  // Display Childcategories & Attributes
  $(document).on("change", "#subcat", function () {
    var link = $(this).find(":selected").attr("data-href");
    if (link != "") {
      $("#childcat").load(link, function() {
        $("#childcat").prop("disabled", false);
        $("#childcat").niceSelect("destroy");
        $("#childcat").niceSelect();
    });
    }

 



    $.get(
      getattrUrl + "?id=" + this.value + "&type=subcategory",
      function (data) {
        let attrHtml = "";
        for (var i = 0; i < data.length; i++) {
          attrHtml += `
        <div class="row mt-4">
          <div class="col-lg-12">
          <div class="input-label-wrapper mb-4">
                <label >${data[i].attribute.name} *</label>
            </div>
          </div>
          <div class="col-lg-12 d-flex flex-column gap-4">
        `;

          for (var j = 0; j < data[i].options.length; j++) {
            let priceClass = "";
            if (data[i].attribute.price_status == 0) {
              priceClass = "d-none";
            }
            attrHtml += `
               <div class="row mb-0 option-row">
              <div class="col-md-5 d-flex align-items-center">
                <div class="custom-control custom-checkbox gs-checkbox-wrapper mt-2">
                  <input type="checkbox" id="${data[i].attribute.input_name}${data[i].options[j].id}" name="${data[i].attribute.input_name}[]" value="${data[i].options[j].name}" class="custom-control-input attr-checkbox">

                  <label class="icon-label" for="${data[i].attribute.input_name}${data[i].options[j].id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 12 12" fill="none">
                                            <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </label>

                  <label class="custom-control-label" for="${data[i].attribute.input_name}${data[i].options[j].id}">${data[i].options[j].name}</label>
                </div>
              </div>
              <div class="col-md-7 ${priceClass}">
                <div class="row align-items-center">
                  <div class="col-2">
                    +
                  </div>
                  <div class="col-10">
                    <div class="price-container d-flex align-items-center">
                      <span class="price-curr">${curr.sign}</span>
                      <input type="text" class="flex-grow-1 form-control form input-field price-input" id="${data[i].attribute.input_name}${data[i].options[j].id}_price" data-name="${data[i].attribute.input_name}_price[]" placeholder="${lang.additional_price}" value="">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;
          }

          attrHtml += `
          </div>
        </div>
        `;
        }

        $("#subcatAttributes").html(attrHtml);
        $("#childcatAttributes").html("");
      }
    );
  });
  // Display Childcateogries & Attributes Ends

  // Display Attributes for Selected Childcategory Starts
  $(document).on("change", "#childcat", function () {
    $.get(
      getattrUrl + "?id=" + this.value + "&type=childcategory",
      function (data) {
        let attrHtml = "";
        for (var i = 0; i < data.length; i++) {
          attrHtml += `
        <div class="row mt-4">
          <div class="col-lg-12">
         <div class="input-label-wrapper mb-4">
                <label >${data[i].attribute.name} *</label>
            </div>
          </div>
          <div class="col-lg-12 d-flex flex-column gap-4">
        `;

          for (var j = 0; j < data[i].options.length; j++) {
            let priceClass = "";
            if (data[i].attribute.price_status == 0) {
              priceClass = "d-none";
            }
            attrHtml += `
           <div class="row mb-0 option-row">
              <div class="col-md-5 d-flex align-items-center">
                <div class="custom-control custom-checkbox gs-checkbox-wrapper mt-2">
                  <input type="checkbox" id="${data[i].attribute.input_name}${data[i].options[j].id}" name="${data[i].attribute.input_name}[]" value="${data[i].options[j].name}" class="custom-control-input attr-checkbox">

                  <label class="icon-label" for="${data[i].attribute.input_name}${data[i].options[j].id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 12 12" fill="none">
                                            <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </label>

                  <label class="custom-control-label" for="${data[i].attribute.input_name}${data[i].options[j].id}">${data[i].options[j].name}</label>
                </div>
              </div>
              <div class="col-md-7 ${priceClass}">
                <div class="row align-items-center">
                  <div class="col-2">
                    +
                  </div>
                  <div class="col-10">
                    <div class="price-container d-flex align-items-center">
                      <span class="price-curr">${curr.sign}</span>
                      <input type="text" class="flex-grow-1 form-control form input-field price-input" id="${data[i].attribute.input_name}${data[i].options[j].id}_price" data-name="${data[i].attribute.input_name}_price[]" placeholder="${lang.additional_price}" value="">
                    </div>
                  </div>
                </div>
              </div>
            </div>

          `;
          }

          attrHtml += `
          </div>
        </div>
        `;
        }

        $("#childcatAttributes").html(attrHtml);
      }
    );
  });
  // Display Attributes for Selected Childcategory Ends

  $(document).on("change", "#type_check", function () {
    let value = $(this).val();
    if (value == "1") {
      $("#file").removeClass("d-none");
      $("#link").addClass("d-none");
      $("#file").find("input").attr("required", true);
      $("#link").find("input").attr("required", false);
    } else {
      $("#file").addClass("d-none");
      $("#link").removeClass("d-none");
      $("#file").find("input").attr("required", false);
      $("#link").find("input").attr("required", true);
    }
  });

  $(document).on("click", ".delete_button", function () {
    let url = $(this).data("href");
    $("#delete_url").attr("action", url);
  });

  $(document).on("click", "#allow-manage-stock", function () {
    if ($(this).is(":checked")) {
      $("#default_stock").addClass("d-none");
    } else {
      $("#default_stock").removeClass("d-none");
    }
  });
})(jQuery);
