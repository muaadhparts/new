{{-- AJAX Response for Merchant Products --}}
{{-- $view pre-computed in MerchantController (DATA_FLOW_POLICY) --}}

{{-- Products Content --}}
<div id="ajax-catalogItems-content">
    <div class="tab-content" id="myTabContent">
        <!-- catalogItem list view -->
        <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
            id="layout-list-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($vprods as $card)
                    @include('includes.frontend.home_catalog_item', ['layout' => 'list', 'card' => $card])
                @endforeach
            </div>
        </div>

        <!-- catalogItem grid view -->
        <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}"
            id="layout-grid-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4">
                @foreach ($vprods as $card)
                    @include('includes.frontend.home_catalog_item', [
                        'layout' => 'grid',
                        'class' => 'col-sm-6 col-md-6 col-xl-4',
                        'card' => $card,
                    ])
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Pagination Data (JSON for JavaScript) --}}
<script type="application/json" id="ajax-pagination-data">
{
    "currentPage": {{ $vprods->currentPage() }},
    "lastPage": {{ $vprods->lastPage() }},
    "total": {{ $vprods->total() }},
    "hasMorePages": {{ $vprods->hasMorePages() ? 'true' : 'false' }},
    "onFirstPage": {{ $vprods->onFirstPage() ? 'true' : 'false' }}
}
</script>
