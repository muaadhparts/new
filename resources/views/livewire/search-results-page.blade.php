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
                        if (request()->input('view_check') == null || request()->input('view_check') == 'list-view') {
                            $view = 'list-view';
                        } else {
                            $view = 'list-view';
                        }
                    @endphp

                            <!-- product nav wrapper -->
                    <div class=" product-nav-wrapper">
                        <h5>@lang('Total Products Found:')  {{$sku}}</h5>

                    </div>



                    @if ($prods->count() == 0)
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
                                    @foreach ($prods as $product)
                                        @include('includes.frontend.list_view_product')
                                    @endforeach
                                </div>
                            </div>


                            @if($alternatives)
                            <div class="tab-pane fade {{ $view == 'list-view' ? 'show active' : '' }}"
                                 id="layout-list-pane" role="tabpanel" tabindex="0">
                                <h4 class="mt-3"> {{trans('Substitutions')}}</h4>
                                <div class="row gy-4 gy-lg-5 mt-2 ">
                                    @foreach ($alternatives as $product)
                                        @include('includes.frontend.list_view_product')
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- product grid view end  -->
                        </div>
{{--                        {{ $prods->links('includes.frontend.pagination') }}--}}
                    @endif

                </div>
            </div>
        </div>
    </div>



</div>