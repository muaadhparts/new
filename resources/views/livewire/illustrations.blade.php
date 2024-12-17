<div>
    illustrations
    @php use Illuminate\Support\Str; @endphp



        <style>
            #zoom_container .landmarks {
                position: absolute;
                z-index: 10;
                top: 0px;
                left: 0px;
            }

            /**/
            #zoom_container .landmarks .item {
                position: absolute;
                text-align: center;
                display: none;
            }

            .hovered {
                border: 2px solid rgb(219, 16, 16) !important;
                background-color: #bce8f1 !important;
            }

            div[id*='zoom_container'] .landmarks .lable div {
                z-index: 19999;
                text-align: center;
                vertical-align: middle;
                border: 2px solid blue;
                background-color: transparent;
                display: table-cell;
                cursor: pointer;
                padding-left: 4px !important;
                padding-right: 4px !important;
                position: absolute;
                border-radius: 999px;
                font: bold 15px tahoma, arial, verdana, sans-serif;
            }


            .inner-card {
                height: 20px;
                background-color: #eee;
            }

            .card-1 {

                height: 200px;
                background-color: #eee;
            }

            .card-2 {
                height: 130px;
            }

            .h-screen {
                height: 100vh;
            }

            .animate-pulse {

                animation: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite;
            }


            @keyframes pulse {
                50% {
                    opacity: .2;
                }
            }
        </style>

<div class="container m-md-2">
    <div class="row bg-white panel-body">
        <div class="col-md-6 content mt-auto mb-auto">

            <div class="row m-0">
                <div class="block1">
                    <div class="products-view">
                        <div class="products-view__options view-options view-options--offcanvas--mobile">

                            <div class="view-options2 xview-options__body--filters ">

                                <div class="view-options__label" style="top:-10px">{{ $category->name }}

                                </div>



                                <div id="zoom_container">

                                    <img id="image" src="{{ Storage::url($category->images) }}" alt="{{ $category->name }}"
                                         width="80" />
                                    <div class="landmarks" data-show-at-zoom="0" data-allow-drag="false"></div>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-6 content m-0">

            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <table class="glow pop-vin table table-bordered-1 table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>{{ __('common.code') }}</th>
                            <th>{{ __('common.part_number') }}</th>
                            <th>{{ __('common.product') }}</th>
                            <th>{{ __('common.applicability') }}</th>


                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($products as $index => $item)
                            {{--                                    @dd($item->PartNumber)--}}
                            <tr class="part-search-tr" x-data="{ isHovered: false }" class="pointer correct-callout"
                                data-category-id="{{ $category->id }}" data-index="{{ $item->callout }}">

                                <td class="codeonimage " style="color: blue; cursor: pointer;"
                                    x-on:click="$dispatch('modal', {partNumber: '{{ $item->callout }}',isLoading:true, isOpen: true ,categoryId:'{{ $category->id }}' })"
                                    id="part_{{ $item->callout }}">{{ $item->callout }}</td>

                                <td>{{ $item->partNumber }}</td>

                                <td>  @if(app()->getLocale() ==='en')  {{ $item->label_en }} @else  {{ $item->label_ar }} @endif </td>


                                <td>    <button type="button"   class="btn btn-danger"
                                    >
                                        {{ __('common.applicability') }}

                                    </button>

                                </td>



                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>
<div x-data="cartItems();">
    <template
            x-on:modal.window="partNumber = $event.detail.partNumber; categoryId = $event.detail.categoryId; isOpen = $event.detail.isOpen;
            isLoading = $event.detail.isLoading;productId = $event.detail.productId;fetchCartPartItems();"></template>

    <div class="modal fade" id="modal">
        <div class="modal-dialog modal-xl " role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <h4 class="modal-title" id="modelHeading">
                </h4>
                <div class="modal-body" id="modalContent">
                    <template x-if="isLoading">
                        <div class="card">
                            <div class="card-1 animate-pulse">
                            </div>
                            <div class="card-2 p-3">
                                <div class="row">
                                    <div class="col-4">
                                        <div class="inner-card animate-pulse">

                                        </div>
                                    </div>

                                    <div class="col-8">
                                        <div class="inner-card animate-pulse">

                                        </div>
                                    </div>
                                </div>


                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="inner-card animate-pulse">

                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <div class="inner-card animate-pulse">

                                        </div>
                                    </div>
                                </div>


                                <div class="row mt-3">
                                    <div class="col-2">
                                        <div class="inner-card animate-pulse">

                                        </div>
                                    </div>

                                    <div class="col-10">
                                        <div class="inner-card animate-pulse">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!isLoading">
                        <div x-html="products">

                        </div>
                    </template>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" x-on:click="closeCartPartItems();" class="btn btn-danger"
                            data-dismiss="modal">
                        <h5 class="font_14 f_w_500 theme_text3 ">{{ __('common.cancel') }}
                    </button>
                </div>

                <!-- Skeleton Loader -->


            </div>
        </div>
    </div>
    {{-- </template> --}}
</div>

@push('scripts')
    {{--    @include(theme('partials.add_to_cart_script'))--}}
    <script src="{{ asset('assets/front/js/jq-zoom.js') }}"></script>
    <script src="{{ asset('assets/front/js/preview.js') }}"></script>
    <script src="{{ asset('assets/front/js/ill/illustrated.js') }}"></script>

    <script>
        @php
            $partsData = [];
            foreach ($partCallouts as $index => $Callouts) {
                foreach ($Callouts as $key => $Callout) {
                    $partsData[] = ['x' => $Callout['rectangle']['left'], 'y' => $Callout['rectangle']['top'], 'width' => Str::length($key) > 10 ? '250px' : '150px', 'height' => '30px', 'index' => $index, 'partNumber' => $index, 'categoryId' => $category->id];
                }
            }
        @endphp

        var partsData = "{{ json_encode($partsData) }}";
        var decodedString = partsData.replace(/&quot;/g, '"');
        var partsData = JSON.parse(decodedString);
    </script>
    <script>
        let csrf = "{{ csrf_token() }}";
        {{--        let route = "{{ route('parts.load.products') }}";--}}
    </script>
    @endpush


    </div>