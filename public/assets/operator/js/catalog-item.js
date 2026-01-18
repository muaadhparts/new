(function($) {
		"use strict";
		
	$(document).ready(function() {

        // Check Click1 :)
        $(".checkclick1").on( "change", function() {
            if(this.checked){
             $(this).parent().parent().parent().parent().next().removeClass('showbox');  
            }
            else{
             $(this).parent().parent().parent().parent().next().addClass('showbox');   
            }
        });
        // Check Click1 Ends :)


        // CatalogItem Measure :)

        $("#product_measure").on( "change" ,function() {
            var val = $(this).val();
            $('#measurement').val(val);
            if(val == "Custom")
            {
            $('#measurement').val('');
              $('#measure').show();
            }
            else{
              $('#measure').hide();      
            }
        });

        // CatalogItem Measure Ends :)

	});

// TAGIT

          $("#metatags").tagit({
          fieldName: "meta_tag[]",
          allowSpaces: true 
          });

          $("#tags").tagit({
          fieldName: "tags[]",
          allowSpaces: true 
        });
// TAGIT ENDS


// Remove White Space


  function isEmpty(el){
      return !$.trim(el.html())
  }


// Remove White Space Ends


// Feature Section

$("#feature-btn").on('click', function(){

    $("#feature-section").append(''+
                            '<div class="feature-area">'+
                                '<span class="remove feature-remove"><i class="fas fa-times"></i></span>'+
                                    '<div  class="row">'+
                                        '<div class="col-lg-6">'+
                                            '<input type="text" name="features[]" class="input-field" placeholder="Enter Your Keyword">'+
                                        '</div>'+
                                        '<div class="col-lg-6">'+
                                            '<div class="input-group colorpicker-component cp">'+
                                                '<input type="text" name="colors[]" value="#000000" class="input-field cp"/>'+
                                                '<span class="input-group-module"><i></i></span>'+
                                            '</div>'+
                                        '</div>'+
                                    '</div>'+
                            '</div>'
                            +'');
    $('.cp').colorpicker();
});

$(document).on('click','.feature-remove', function(){

    $(this.parentNode).remove();
    if (isEmpty($('#feature-section'))) {

    $("#feature-section").append(''+
                            '<div class="feature-area">'+
                                '<span class="remove feature-remove"><i class="fas fa-times"></i></span>'+
                                    '<div  class="row">'+
                                        '<div class="col-lg-6">'+
                                            '<input type="text" name="features[]" class="input-field" placeholder="Enter Your Keyword">'+
                                        '</div>'+
                                        '<div class="col-lg-6">'+
                                            '<div class="input-group colorpicker-component cp">'+
                                                '<input type="text" name="colors[]" value="#000000" class="input-field cp"/>'+
                                                '<span class="input-group-module"><i></i></span>'+
                                            '</div>'+
                                        '</div>'+
                                    '</div>'+
                            '</div>'
                            +'');
    $('.cp').colorpicker();
    }

});

// Feature Section Ends
// Type Check

$('#type_check').on('change',function(){
    var val = $(this).val();
    if(val == 1) {
    $('.row.file').css('display','flex');
    $('.row.file').find('input[type=file]').prop('required',true);
    $('.row.link').find('textarea').val('').prop('required',false);
    $('.row.link').hide();
    }
    else {
    $('.row.file').hide();
    $('.row.link').css('display','flex');
    $('.row.file').find('input[type=file]').prop('required',false);
    $('.row.link').find('textarea').prop('required',true);
    }

});

// Type Check Ends



// License Section

$("#license-btn").on('click', function(){

    $("#license-section").append(''+
                            '<div class="license-area">'+
                                '<span class="remove license-remove"><i class="fas fa-times"></i></span>'+
                                    '<div  class="row">'+
                                        '<div class="col-lg-6">'+
                                            '<input type="text" name="license[]" class="input-field" placeholder="License Key" required="">'+
                                        '</div>'+
                                        '<div class="col-lg-6">'+
                                            '<input type="number" name="license_qty[]" min="1" class="input-field" placeholder="License Quantity" value="1">'+
                                        '</div>'+
                                    '</div>'+
                            '</div>'
                            +'');
});

$(document).on('click','.license-remove', function(){

    $(this.parentNode).remove();
    if (isEmpty($('#license-section'))) {

    $("#license-section").append(''+
                            '<div class="license-area">'+
                                '<span class="remove license-remove"><i class="fas fa-times"></i></span>'+
                                    '<div  class="row">'+
                                        '<div class="col-lg-6">'+
                                            '<input type="text" name="license[]" class="input-field" placeholder="License Key" required="">'+
                                        '</div>'+
                                        '<div class="col-lg-6">'+
                                            '<input type="number" name="license_qty[]" min="1" class="input-field" placeholder="License Quantity" value="1">'+
                                        '</div>'+
                                    '</div>'+
                            '</div>'
                            +'');
    }

});

// License Section Ends

$("#whole_check").change(function() {
    if(this.checked) {
        $("#whole-section input").prop('required',true);
    }
    else {
        $("#whole-section input").prop('required',false);
    }
});


// Whole Sell Section

$("#whole-btn").on('click', function(){

    if(whole_sell > $("[name='whole_sell_qty[]']").length)
    {
    $("#whole-section").append(''+
                            '<div class="feature-area">'+
                                '<span class="remove whole-remove"><i class="fas fa-times"></i></span>'+
                                    '<div  class="row">'+
                                        '<div class="col-lg-6">'+
                                            '<input type="number" name="whole_sell_qty[]" class="input-field" placeholder="Enter Quantity" min="0" required>'+
                                        '</div>'+
                                        '<div class="col-lg-6">'+
                                            '<input type="number" name="whole_sell_discount[]" class="input-field" placeholder="Enter Discount Percentage" min="0" required>'+
                                        '</div>'+
                                    '</div>'+
                            '</div>'
                            +'');        
    }

});

$(document).on('click','.whole-remove', function(){

    $(this.parentNode).remove();
    if (isEmpty($('#whole-section'))) {

    $("#whole-section").append(''+
                            '<div class="feature-area">'+
                                '<span class="remove whole-remove"><i class="fas fa-times"></i></span>'+
                                    '<div  class="row">'+
                                        '<div class="col-lg-6">'+
                                            '<input type="number" name="whole_sell_qty[]" class="input-field" placeholder="Enter Quantity" min="0">'+
                                        '</div>'+
                                        '<div class="col-lg-6">'+
                                            '<input type="number" name="whole_sell_discount[]" class="input-field" placeholder="Enter Discount Percentage" min="0">'+
                                        '</div>'+
                                    '</div>'+
                            '</div>'
                            +'');
    }

});

// Whole Sell Section Ends


})(jQuery);


  

