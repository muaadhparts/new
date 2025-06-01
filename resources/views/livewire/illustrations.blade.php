<div>

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




    <div class="container p-4 mt-3">
        <div class="product-nav-wrapper">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="text-black" href="{{route('front.index')}}">Home</a></li>
                    <li class="breadcrumb-item"><a class="text-black" href="{{route('catlogs.index',$brand->name)}}">{{$brand->name}}</a></li>
                    <li class="breadcrumb-item"><a class="text-black" href="{{route('tree.level1',['id'=> $brand->name ,'data'=> $vehicle ])}}">{{$vehicle}}</a></li>
                    <li class="breadcrumb-item"><a class="text-black" href="{{route('tree.level2',['id'=> $brand->name ,'data'=> $vehicle ,'key1' => $category->key1 ])}}">{{$category->value1}}</a></li>
                    <li class="breadcrumb-item"><a class="text-black" href="{{route('tree.level2',['id'=> $brand->name ,'data'=> $vehicle ,'key1' => $category->key1 ,'key2'=> $category->key2 ])}}">{{$category->value2}}</a></li>
                    <li class="breadcrumb-item active"><a class="text-primary" href="{{route('illustrations',['id'=> $brand->name ,'data'=> $vehicle ,'key1' => $category->key1 ,'key2'=> $category->key2 ,'code'=>$category->code])}}">{{ preg_replace('/\s*\(.*?\)/', '', $category->label) }}</a></li>
                </ol>
            </nav>
        </div>
        <livewire:vehicle-search-box :vehicle="$vehicle"/>
    </div>

    <div class="container m-md-2 d-flex justify-content-center">
        <div class="col-md-8 bg-white panel-body text-center">
            <div class="products-view">
                <div class="products-view__options view-options view-options--offcanvas--mobile">
                    <div class="view-options2 xview-options__body--filters ">
                        <div class="view-options__label" style="top:-10px">{{ $category->name }}</div>
                        <div id="zoom_container">
                            <img id="image" src="{{ Storage::url($category->images) }}" alt="{{ $category->name }}" width="80" />
                            <div class="landmarks" data-show-at-zoom="0" data-allow-drag="false"></div>
                        </div>
                    </div>
                </div>
            </div>
{{-- 
            <div class="mt-4">
                <table class="glow pop-vin table table-bordered-1 table-hover table-condensed">
                    <thead>
                    <tr>
                        <th>{{ __('Part Code') }}</th>
                        <th>{{ __('Part Number') }}</th>
                        <th>{{ __('Name Part') }}</th>
                        <th>{{ __('Applicability') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($products as $index => $item)
                        <tr class="part-search-tr" x-data="{ isHovered: false }" class="pointer correct-callout"
                            data-category-id="{{ $category->id }}" data-index="{{ $item->callout }}">
                            <td class="codeonimage callout-code-click"
                                data-callout="{{ $item->callout }}"
                                style="color: rgba(16, 16, 219, 0.473); cursor: pointer;"
                                id="part_{{ $item->callout }}">
                                {{ $item->callout }}
                            </td>
                            <td>{{ $item->partnumber }}</td>
                             <td>
                                {{ app()->getLocale() === 'ar'
                                    ? ($item->label_ar ?? $item->label_en)
                                    : $item->label_en }}
                            </td>
                            <td>
                                    {{ $item->applicability }}
                             
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div> --}}
        </div>
    </div>

    <div x-data="cartItems();">
        <template x-on:modal.window="partNumber = $event.detail.partNumber; categoryId = $event.detail.categoryId; isOpen = $event.detail.isOpen; isLoading = $event.detail.isLoading;productId = $event.detail.productId;fetchCartPartItems();"></template>
        <div class="modal fade" id="modal">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <h4 class="modal-title" id="modelHeading"></h4>
                    <div class="modal-body" id="modalContent">
                        <template x-if="isLoading">
                            <div class="card">
                                <div class="card-1 animate-pulse"></div>
                                <div class="card-2 p-3">
                                    <div class="row">
                                        <div class="col-4"><div class="inner-card animate-pulse"></div></div>
                                        <div class="col-8"><div class="inner-card animate-pulse"></div></div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-6"><div class="inner-card animate-pulse"></div></div>
                                        <div class="col-6"><div class="inner-card animate-pulse"></div></div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-2"><div class="inner-card animate-pulse"></div></div>
                                        <div class="col-10"><div class="inner-card animate-pulse"></div></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!isLoading">
                            <div x-html="products"></div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" x-on:click="closeCartPartItems();" class="btn btn-danger" data-dismiss="modal">
                            <h5 class="font_14 f_w_500 theme_text3">{{ __('common.cancel') }}</h5>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


@push('scripts')

<script>
    window.vehicleCode = @js($vehicle);
    window.categoryCode = @js($category->code);
</script>

<script src="{{ asset('assets/front/js/jq-zoom.js') }}"></script>
<script src="{{ asset('assets/front/js/preview.js') }}"></script>
<script src="{{ asset('assets/front/js/ill/illustrated.js') }}"></script>

<script>
    @php
        $partsData = [];
        foreach ($partCallouts as $index => $Callouts) {
            foreach ($Callouts as $key => $Callout) {
                $partsData[] = [
                    'x' => $Callout['rectangle']['left'],
                    'y' => $Callout['rectangle']['top'],
                    'width' => Str::length($key) > 10 ? '250px' : '150px',
                    'height' => '30px',
                    'index' => $index,
                    'partNumber' => $index,
                    'callout' => $index, 
                    'categoryId' => $category->id
                ];
            }
        }
    @endphp

   var partsData = "{{ json_encode($partsData) }}";
    var decodedString = partsData.replace(/&quot;/g, '"');
    var partsData = JSON.parse(decodedString);
</script>

<script>
    let csrf = "{{ csrf_token() }}";
</script>
@endpush

<livewire:callout-modal />

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        setTimeout(() => {
            // ✅ الضغط على المربعات فوق الصورة
            document.querySelectorAll(".callout-label").forEach(label => {
                label.addEventListener("click", function () {
                    const part = JSON.parse(this.dataset.part);
                    window.livewire.emit("openCalloutModal", {
                        data: window.vehicleCode,
                        code: window.categoryCode,
                        callout: part.callout
                    });
                });
            });

            // ✅ الضغط على Part Code داخل الجدول
            document.querySelectorAll(".callout-code-click").forEach(cell => {
                cell.addEventListener("click", function () {
                    const callout = this.dataset.callout;
                    window.livewire.emit("openCalloutModal", {
                        data: window.vehicleCode,
                        code: window.categoryCode,
                        callout: callout
                    });
                });
            });

        }, 500);
    });
</script>
@endpush
