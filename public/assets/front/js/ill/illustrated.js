jQuery(function($) {
    var data = $('#image').smoothZoom({
        width: 800,
        height: 500,
        responsive: true,
        container: 'zoom_container',
        responsive_maintain_ratio: true,
        max_WIDTH: '',
        max_HEIGHT: '',
        zoom_SINGLE_STEP: false,
        animation_SMOOTHNESS: 3,
        animation_SPEED_ZOOM: 3,
        animation_SPEED_PAN: 3,
        initial_POSITION: '200, 300',
        zoom_MAX: 200,
        button_SIZE: 20,
        button_AUTO_HIDE: 'YES',
        button_AUTO_HIDE_DELAY: 2,
        button_ALIGN: 'top right',
        mouse_DOUBLE_CLICK: false,
        mouse_WHEEL: true,
        use_3D_Transform: true,
        border_TRANSPARENCY: 0,
        on_IMAGE_LOAD: function() {
            console.log('on_IMAGE_LOAD')
        },
        on_IMAGE_LOAD: function() {
            console.log('on_IMAGE_LOAD')
        },
        on_ZOOM_PAN_UPDATE: function() {
            console.log('on_ZOOM_PAN_UPDATE')
        },
        on_ZOOM_PAN_COMPLETE: function() {

        },
        on_LANDMARK_STATE_CHANGE: function() {
            console.log('on_LANDMARK_STATE_CHANGE')
        }

    });
    partsData.forEach(element => {

        $('#image').smoothZoom('addLandmark', [`
                <div x-data="{ isHovered: false }"
                        data-category-id="${element.categoryId }" data-index="${ element.index }"  class="item lable lable-single pointer correct-callout" data-container="body"   data-allow-scale="true" data-size="${element.width},${element.height}" data-position="${element.x},${element.y}">
                <div data-codeonimage="${element.index}" x-on:click="$dispatch('modal', {partNumber: '${ element.index }',isLoading:true, isOpen: true ,categoryId:'${element.categoryId}' })"
                                 class="bbdover" id="part_${ element.index }" style="position: absolute; width: ${element.width}; height: ${element.height}; background-color:ransparent; opacity: 0.7;">
                </div>
                </div>`]);
    });
    $('.bbdover').hover(function() {

        var do_scroll = false;
        var code = $(this).attr('data-codeonimage');
        $(this).addClass('hovered');
        $(this).siblings().each(function() {
            if ($(this).attr('data-codeonimage') == code) {
                $(this).addClass('hovered');
            }
        });
        $.each($(this).parents('div.panel-body').find('table td.codeonimage'), function(i, val) {
            if (code == $(val).text()) {
                $(val).parent().addClass('hovered');
                if (!do_scroll) {
                    var nextDistanceTop = $(val).parent().get(0).offsetTop - 200;
                    $(val).parents('div').animate({
                        scrollTop: nextDistanceTop
                    });
                    do_scroll = true;
                }
            }
        });
    }, function() {
        var code = $(this).attr('data-codeonimage');
        $(this).removeClass('hovered');
        $(this).siblings().each(function() {
            if ($(this).attr('data-codeonimage') == code) {
                $(this).removeClass('hovered');
            }
        });
        $.each($(this).parents('div.panel-body').find('table td.codeonimage'), function(i, val) {
            if (code == $(val).text()) {
                $(val).parent().removeClass('hovered');
            }
        });
    });

    $('tr.part-search-tr').hover(function() {
        var code = $(this).find('td.codeonimage').text();

        $.each($(this).parents('div.panel-body').find('div.bbdover'), function(i, val) {
            if (code == $(val).attr('data-codeonimage')) {
                $(val).addClass('hovered');
            }
        });
    }, function() {
        var code = $(this).find('td.codeonimage').text();
        $.each($(this).parents('div.panel-body').find('div.bbdover'), function(i, val) {
            if (code == $(val).attr('data-codeonimage')) {
                $(val).removeClass('hovered');
            }
        });
    });

});

function cartItems() {
    return {
        isOpen: false,
        isLoading: false,
        partNumber: 0,
        categoryId: 0,
        products: null,
        isMany: false,
        productId: null,


        fetchCartPartItems() {
            $("#modal").modal('show');

            fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        partNumber: this.partNumber,
                        categoryId: this.categoryId,
                        productId: this.productId,

                    })
                })
                .then((response) => response.text())
                .then((data) => {
                    this.isLoading = false;
                    this.products = data;
                    $("#modal").modal('show');
                })
                .catch((err) => {
                    $("#modal").modal('hide');
                    this.isOpen = false;
                    this.isLoadingNew = false;
                    this.isLoadingMore = false;
                    this.partNumber = 0;
                    this.categoryId = 0;
                    this.products = null;
                    this.productId = null;
                    console.log("ERROR", err)
                });
        },
        closeCartPartItems() {
            this.isOpen = false;
            this.isLoading = false;
            this.partNumber = 0;
            this.categoryId = 0;
            this.products = null;
            this.productId = null;
            $("#modal").modal('hide');

        }
    };
}
