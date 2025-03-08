<div>


    <!-- Button trigger modal -->
    <span>@lang('Compatibility:') </span>
    <button type="button" class="btn zbtn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal{{$sku}}">
        <i class="fa fa-car fa-2xl"></i>
    </button>
{{--    php artisan make:livewire CompatibilityTabs--}}
    <!-- Modal -->
    <div class="modal fade" id="exampleModal{{$sku}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-xl " style="width: 100%">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">@lang('Compatibility:') {{$sku}} </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <livewire:compatibility-tabs :catalogs="$catalogs"/>






                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>


    <!-- JavaScript to Set Active Tab Based on URL Hash -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Get the value after "#" in the URL
            let hash = window.location.hash.substring(1); // Removes the "#" symbol
            // alert(hash);
            if (hash) {
                let tabs = document.querySelectorAll("#catalogTabs .nav-link");

                // Remove 'active' class from all tabs
                tabs.forEach(tab => tab.classList.remove("active"));

                // Find and activate the tab matching the hash value
                let activeTab = document.getElementById(`tab-${hash}`);
                if (activeTab) {
                    activeTab.classList.add("active");
                } else {
                    // Default to the first tab if no match found
                    tabs[0].classList.add("active");
                }
            }
        });
    </script>


</div>