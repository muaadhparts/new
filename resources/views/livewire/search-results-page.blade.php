<div>

    <div class="gs-blog-wrapper">

        <div class="container">
            <livewire:search-box/>
            <!-- زر فتح المودال -->
            <div class="text-center mt-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vinSearchModal">
                    @lang('Search by VIN')
                </button>
            </div>

            <div class="row flex-column-reverse flex-lg-row">

                <div class="col-12 col-lg-12 col-xl-12 gs-main-blog-wrapper">

                    @php
                        // نثبت العرض على list-view حاليًا
                        $view = 'list-view';

                        // إجمالي العروض (قوائم البائعين الفعّالة) للمنتجات الأساسية
                        $totalListings = 0;
                        if (isset($prods) && $prods->count()) {
                            foreach ($prods as $p) {
                                $totalListings += $p->merchantProducts()
                                    ->where('status', 1)
                                    ->count();
                            }
                        }

                        // إجمالي العروض للبدائل (إن كانت بدائل عبارة عن منتجات)
                        if (isset($alternatives) && $alternatives instanceof \Illuminate\Support\Collection && $alternatives->count()) {
                            foreach ($alternatives as $p) {
                                $totalListings += $p->merchantProducts()
                                    ->where('status', 1)
                                    ->count();
                            }
                        }
                    @endphp

                    <!-- product nav wrapper -->
                    <div class="product-nav-wrapper">
                        <h5>
                            @lang('Total Listings Found:') {{ $totalListings }}
                            <small class="text-muted ms-2">(@lang('Query') : {{ $sku }})</small>
                        </h5>
                    </div>

                    @if (!isset($prods) || $prods->count() == 0)
                        <!-- product nav wrapper for no data found -->
                        <div class="product-nav-wrapper d-flex justify-content-center ">
                            <h5>@lang('No Product Found')</h5>
                        </div>
                    @else
                        <!-- main content -->
                        <div class="tab-content" id="myTabContent">
                            <!-- product list view start  -->
                            <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                 id="layout-list-pane" role="tabpanel" tabindex="0">
                                <div class="row gy-4 gy-lg-5 mt-20 ">

                                    {{-- لكل منتج، أعرض كل سجلات البائعين الفعّالة --}}
                                    @foreach ($prods as $product)
                                        @php
                                            $merchants = $product->merchantProducts()
                                                ->where('status', 1)
                                                ->with('user:id,is_vendor')
                                                ->get();
                                        @endphp

                                        @foreach ($merchants as $merchant)
                                            {{-- نمرر vendorId و merchant للـ partial ليُبنى الكرت وفق البائع المحدد --}}
                                            @include('includes.frontend.list_view_product', [
                                                'product'  => $product,
                                                'vendorId' => $merchant->user_id,
                                                'merchant' => $merchant,
                                            ])
                                        @endforeach
                                    @endforeach

                                </div>
                            </div>

                            {{-- بدائل القطعة: نعرض لكل بديل كل البائعين الفعّالين أيضاً --}}
                            @if(isset($alternatives) && $alternatives->count())
                                <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                     id="layout-list-pane" role="tabpanel" tabindex="0">
                                    <h4 class="mt-3"> {{ trans('Substitutions') }}</h4>
                                    <div class="row gy-4 gy-lg-5 mt-2 ">

                                        @foreach ($alternatives as $product)
                                            @php
                                                $merchants = $product->merchantProducts()
                                                    ->where('status', 1)
                                                    ->with('user:id,is_vendor')
                                                    ->get();
                                            @endphp

                                            @foreach ($merchants as $merchant)
                                                @include('includes.frontend.list_view_product', [
                                                    'product'  => $product,
                                                    'vendorId' => $merchant->user_id,
                                                    'merchant' => $merchant,
                                                ])
                                            @endforeach
                                        @endforeach

                                    </div>
                                </div>
                            @endif
                            <!-- product grid view end  -->
                        </div>
                        {{-- {{ $prods->links('includes.frontend.pagination') }} --}}
                    @endif

                </div>
            </div>
        </div>
    </div>

</div>
