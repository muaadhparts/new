<div>



    <div class="gs-blog-wrapper">

        <div class="container">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    {{--                    @dd(request('vin'))--}}

                    <li class="breadcrumb-item "><a  class="text-black" href="{{route('front.index')}}">Home</a></li>
                    <li class="breadcrumb-item"><a class="text-black" href="{{route('catlogs.index',$brand->name)}}">{{$brand->name}}</a></li>
                    @if(request('vin'))
                        <li class="breadcrumb-item"><a class="text-black" href="{{route('tree.level1',
                                ['id'=> $brand->name ,
                                 'data'=> $vehicle ,
                                'vin'=>  request('vin')])}}">
                                {{request('vin')}}</a>
                        </li>
                    @endif
                    <li class="breadcrumb-item active"><a class="text-primary" href="{{route('tree.level1',['id'=> $brand->name ,'data'=> $vehicle ])}}">{{$vehicle}}</a></li>
                    {{--                <li class="breadcrumb-item active" aria-current="page">Data</li>--}}
                </ol>
            </nav>
            <div class="row flex-column-reverse flex-lg-row">
{{--                @dump($this)--}}
                <livewire:vehicle-search-box  :vehicle="$vehicle"/>

                <div class="col-12 col-lg-12 col-xl-12 gs-main-blog-wrapper">

                    @php
                        if (request()->input('view_check') == null || request()->input('view_check') == 'list-view') {
                            $view = 'list-view';
                        } else {
                            $view = 'list-view';
                        }
                    @endphp

{{--                    @dd($prods);--}}

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
                                    <div class="modal-body">


                                        <div class="container">
                                            {{--                        <h4>Parts List</h4>--}}
                                            <table class="table table-bordered">
                                                <thead>
                                                <tr>
                                                    <th>@lang('Part Number')</th>
                                                    <th>@lang('Name')</th>
                                                    <th>@lang('Qty ')</th>
                                                    <th>@lang('callout ')</th>
                                                    <th>@lang('applicability ')</th>
                                                    <th>@lang('begin_date ')</th>
                                                    <th>@lang('end_date ')</th>

                                                    <th>@lang('View')</th>


                                                </tr>
                                                </thead>
                                                <tbody>
                                                @forelse($prods as $product)
                                                    <tr>
                                                         <td>
                                                             <a href="{{ route('search.result', $product->partnumber) }}" target="_blank">
                                                                <span class="text-primary">{{ $product->partnumber }} </span>
                                                            </a>
                                                        </td>
                                                        <td>{{ $product->label_en }} -<br>
                                                            {{ $product->label_ar }}
                                                        </td>
                                                         <td>{{ $product->qty }}</td>
                                                        <td>{{ $product->callout  }}</td>
                                                        <td>{{ $product->applicability  }}</td>
                                                        <td>{{ $product->formattedbegindate  }}</td>
                                                        <td>{{ $product->formattedenddate  }}</td>
                                                        <td>
                                                            <a class="btn btn-outline-primary" href="{{ url("catlogs/{$this->brand->name}/$this->vehicle/$product->key1/$product->key2/$product->code") }}">
                                                                @lang('View')
                                                            </a>

                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center">No data found</td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                     </div>

                                </div>
                            </div>



                            <!-- product grid view end  -->
                        </div>
                        {{--                        {{ $prods->links('includes.frontend.pagination') }}--}}
                    @endif

                </div>
            </div>
        </div>
    </div>



</div>