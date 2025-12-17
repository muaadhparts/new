@php
    $view = Session::get('view', 'grid-view');
@endphp

@if (count($cards ?? $prods) > 0)
<div class="col-lg-12">
    <div class="tab-content" id="myTabContent">
        {{-- LIST VIEW --}}
        <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}" id="layout-list-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4 gy-lg-5 mt-20">
                @foreach ($cards ?? $prods as $card)
                    @include('includes.frontend.product_card_dto', ['card' => $card, 'layout' => 'list'])
                @endforeach
            </div>
        </div>

        {{-- GRID VIEW --}}
        <div class="tab-pane fade {{ $view == 'grid-view' ? 'show active' : '' }}" id="layout-grid-pane" role="tabpanel" tabindex="0">
            <div class="row gy-4 gy-lg-5 mt-20">
                @foreach ($cards ?? $prods as $card)
                    @include('includes.frontend.product_card_dto', ['card' => $card, 'layout' => 'grid', 'class' => 'col-sm-6 col-md-6 col-xl-4'])
                @endforeach
            </div>
        </div>
    </div>
    {{ ($cards ?? $prods)->links('includes.frontend.pagination') }}
</div>
@else
<div class="col-lg-12">
    <div class="page-center">
        <h4 class="text-center">{{ __('No Product Found.') }}</h4>
    </div>
</div>
@endif

<script>
    $('[data-bs-toggle="tooltip"]').tooltip({});
    $('[rel-toggle="tooltip"]').tooltip();

    $('[data-bs-toggle="tooltip"]').on('click', function () {
        $(this).tooltip('hide');
    });

    $('[rel-toggle="tooltip"]').on('click', function () {
        $(this).tooltip('hide');
    });
</script>