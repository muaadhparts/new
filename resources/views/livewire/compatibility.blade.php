<div>
    <span>@lang('')</span>
    <button type="button" class="btn zbtn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal{{$sku}}">
        <i class="fa fa-car fa-2xl"></i>
    </button>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal{{$sku}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" style="width: 100%">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">@lang('') {{$sku}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- <livewire:compatibility-tabs :catalogs="$catalogs" :sku="$sku"/> --}}
                    <livewire:compatibility-tabs :sku="$sku"/>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript for Tab Activation -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let hash = window.location.hash.substring(1);
            if (hash) {
                let tabs = document.querySelectorAll(".nav-pills .nav-link");

                tabs.forEach(tab => tab.classList.remove("active"));

                let activeTab = document.querySelector(`.nav-link[data-tab='${hash}']`);
                if (activeTab) {
                    activeTab.classList.add("active");
                } else if (tabs.length > 0) {
                    tabs[0].classList.add("active");
                }
            }
        });
    </script>
</div>