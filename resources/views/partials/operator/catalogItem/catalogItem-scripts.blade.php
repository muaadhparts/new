<script>
    (function($) {
        "use strict";

        $(document).ready(function() {
            // Catalog Item Measure
            $("#product_measure").on("change", function() {
                var val = $(this).val();
                $('#measurement').val(val);
                if (val == "Custom") {
                    $('#measurement').val('');
                    $('#measure').removeClass('hidden').show();
                } else {
                    $('#measure').addClass('hidden').hide();
                }
            });
        });

        // Tags
        $("#metatags").tagit({
            fieldName: "meta_tag[]",
            allowSpaces: true
        });

        $("#tags").tagit({
            fieldName: "tags[]",
            allowSpaces: true
        });

    })(jQuery);
</script>
