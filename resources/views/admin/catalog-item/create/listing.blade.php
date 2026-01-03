@extends('layouts.admin')
@section('styles')

<link href="{{asset('assets/admin/css/catalog-item.css')}}" rel="stylesheet"/>
<link href="{{asset('assets/admin/css/jquery.Jcrop.css')}}" rel="stylesheet"/>
<link href="{{asset('assets/admin/css/Jcrop-style.css')}}" rel="stylesheet"/>

@endsection
@section('content')

    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                        <h4 class="heading">{{ __('Listing CatalogItem') }} <a class="add-btn" href="{{ route('admin-catalog-item-types') }}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                        <ul class="links">
                            <li>
                                <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                            </li>
                        <li>
                            <a href="javascript:;">{{ __('CatalogItems') }} </a>
                        </li>
                        <li>
                            <a href="{{ route('admin-catalog-item-index') }}">{{ __('All CatalogItems') }}</a>
                        </li>
                            <li>
                                <a href="{{ route('admin-catalog-item-types') }}">{{ __('Add CatalogItem') }}</a>
                            </li>
                            <li>
                                <a href="{{ route('admin-catalog-item-create','listing') }}">{{ __('Listing CatalogItem') }}</a>
                            </li>
                        </ul>
                </div>
            </div>
        </div>
        <form id="muaadhform" action="{{route('admin-catalog-item-store')}}" method="POST" enctype="multipart/form-data">
            {{csrf_field()}}
            @include('alerts.admin.form-both')
            <div class="row">
                <div class="col-lg-8">
                    <div class="add-catalogItem-content">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="catalogItem-description">
                                    <div class="body-area">
                                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

                                        {{-- Merchant Selection --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Merchant') }}*</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select id="merchant_id" name="user_id" required="" class="select2">
                                                    <option value="">{{ __('Select Merchant') }}</option>
                                                    @foreach (\App\Models\User::where('is_merchant', 2)->where('ban', 0)->orderBy('shop_name')->get() as $merchant)
                                                        <option value="{{ $merchant->id }}">
                                                            {{ $merchant->shop_name ?: $merchant->name }} ({{ $merchant->email }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Brand (العلامة التجارية) --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Brand') }} ({{ __('Trademark') }})</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select name="brand_id" class="form-control">
                                                    <option value="">{{ __('Select Brand') }}</option>
                                                    @foreach (\App\Models\Brand::all() as $brand)
                                                        <option value="{{ $brand->id }}">
                                                            {{ $brand->name }} {{ $brand->name_ar ? '- ' . $brand->name_ar : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Quality Brand (جودة التصنيع) --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Quality Brand') }} ({{ __('Manufacturing Quality') }})</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select name="brand_quality_id" class="form-control">
                                                    <option value="">{{ __('Select Quality Brand') }}</option>
                                                    @foreach (\App\Models\QualityBrand::all() as $qb)
                                                        <option value="{{ $qb->id }}">
                                                            {{ $qb->name_en }} {{ $qb->name_ar ? '- ' . $qb->name_ar : '' }} {{ $qb->country ? '(' . $qb->country . ')' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Name') }}* </h4>
                                                        <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control" placeholder="{{ __('Enter CatalogItem Name') }}" name="name" required="">
                                            </div>
                                        </div>

                                        {{-- Label English --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Name (English)') }}</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control"
                                                    placeholder="{{ __('Enter CatalogItem Name in English') }}"
                                                    name="label_en">
                                            </div>
                                        </div>

                                        {{-- Label Arabic --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Name (Arabic)') }}</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control" dir="rtl"
                                                    placeholder="{{ __('Enter CatalogItem Name in Arabic') }}"
                                                    name="label_ar">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Sku') }}* </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control" placeholder="{{ __('Enter CatalogItem Sku') }}" name="sku" required="" value="{{ Str::random(3).substr(time(), 6,8).Str::random(3) }}">
                                            </div>
                                        </div>


                                        {{-- Old category system removed - Categories are now linked via parts tables (TreeCategories) --}}

                                    

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="item_condition_check" type="checkbox" id="item_condition_check" value="1">
                                                        <label for="item_condition_check">{{ __('Allow CatalogItem Condition') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                            <h4 class="heading">{{ __('CatalogItem Condition') }}*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                        <select name="item_condition">
                                                            <option value="2">{{ __('New') }}</option>
                                                            <option value="1">{{ __('Used') }}</option>
                                                        </select>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="preordered_check" type="checkbox" id="preorderedCheck" value="1">
                                                        <label for="preorderedCheck">{{ __('Allow CatalogItem Preorder') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                            <h4 class="heading">{{ __('CatalogItem Preorder') }}*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                        <select name="preordered">
                                                            <option value="1">{{ __('Sale') }}</option>
                                                            <option value="2">{{ __('Preordered') }}</option>
                                                        </select>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="minimum_qty_check" type="checkbox" id="check111" value="1">
                                                        <label for="check111">{{ __('Allow Minimum Order Qty') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

            
                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Minimum Order Qty') }}* </h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <input type="number" class="form-control" min="1"
                                                        placeholder="{{ __('Minimum Order Qty') }}" name="minimum_qty">
                                                </div>
                                            </div>
            
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="shipping_time_check" type="checkbox" id="check1" value="1">
                                                        <label for="check1">{{ __('Allow Estimated Shipping Time') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>



                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Estimated Shipping Time') }}* </h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <input type="text" class="form-control" placeholder="{{ __('Estimated Shipping Time') }}" name="ship">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclickc" name="color_check" type="checkbox" id="check3" value="1">
                                                        <label for="check3">{{ __('Allow CatalogItem Colors') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="showbox">

                                            <div class="row">
                                                    <div  class="col-lg-12">
                                                        <div class="left-area">
                                                            <h4 class="heading">
                                                                {{ __('CatalogItem Colors') }}*
                                                            </h4>
                                                            <p class="sub-heading">
                                                                {{ __('(Choose Your Favorite Colors)') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div  class="col-lg-12">
                                                        <div class="select-input-color" id="color-section">
                                                            <div class="color-area">
                                                                <span class="remove color-remove"><i class="fas fa-times"></i></span>
                                                                <div class="input-group colorpicker-component cp">
                                                                    <input type="text" name="color_all[]" class="form-control cp tcolor"/>
                                                                    <span class="input-group-addon"><i></i></span>
                                                                </div>
                                                                </div>
                                                        </div>
                                                    <a href="javascript:;" id="color-btn" class="add-more mt-4 mb-3"><i class="fas fa-plus"></i>{{ __('Add More Color') }} </a>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclicks" name="size_check" type="checkbox" id="tcheck" value="1">
                                                        <label for="tcheck">{{ __('Allow CatalogItem Sizes') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="showbox">
                                            <div class="row">
                                                    <div  class="col-lg-4">
                                                        <div class="left-area">
                                                            <h4 class="heading">
                                                                {{ __('CatalogItem Size') }}*
                                                            </h4>
                                                            <p class="sub-heading">
                                                                {{ __('(eg. S,M,L,XL,XXL,3XL,4XL)') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div  class="col-lg-12">
                                                            <div class="select-input-tsize" id="tsize-section">
                                                                <div class="tsize-area">
                                                                    <span class="remove tsize-remove"><i class="fas fa-times"></i></span>
                                                                    <input  type="text" name="size_all[]" class="form-control tsize" placeholder="{{ __('Enter CatalogItem Size') }}"  >
                                                                    
                                                                    </div>
                                                            </div>
                                                        <a href="javascript:;" id="tsize-btn" class="add-more mt-4 mb-3"><i class="fas fa-plus"></i>{{ __('Add More Size') }} </a>
                                                    </div>
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="whole_check" type="checkbox" id="whole_check" value="1">
                                                        <label for="whole_check">{{ __('Allow CatalogItem Whole Sell') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">

                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="featured-keyword-area">
                                                        <div class="feature-tag-top-filds" id="whole-section">
                                                            <div class="feature-area">
                                                                <span class="remove whole-remove"><i class="fas fa-times"></i></span>
                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                    <input type="number" name="whole_sell_qty[]" class="form-control" placeholder="{{ __('Enter Quantity') }}" min="0">
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                    <input type="number" name="whole_sell_discount[]" class="form-control" placeholder="{{ __('Enter Discount Percentage') }}" min="0" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <a href="javascript:;" id="whole-btn" class="add-fild-btn"><i class="icofont-plus"></i> {{ __('Add More Field') }}</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input class="checkclick1" name="measure_check" type="checkbox" id="measure_check" value="1">
                                                        <label for="measure_check">{{ __('Allow CatalogItem Measurement') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Measurement') }}*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <select id="product_measure">
                                                        <option value="">{{ __('None') }}</option>
                                                        <option value="Gram">{{ __('Gram') }}</option>
                                                        <option value="Kilogram">{{ __('Kilogram') }}</option>
                                                        <option value="Litre">{{ __('Litre') }}</option>
                                                        <option value="Pound">{{ __('Pound') }}</option>
                                                        <option value="Custom">{{ __('Custom') }}</option>
                                                    </select>
                                                </div>
                                                {{-- <div class="col-lg-1"></div> --}}
                                                <div class="col-lg-12 hidden" id="measure">
                                                    <input name="measure" type="text" id="measurement" class="form-control" placeholder="{{ __('Enter Unit') }}">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul class="list">
                                                    <li>
                                                        <input name="stock_check" class="stock-check" type="checkbox" id="size-check" value="1">
                                                        <label for="size-check" class="stock-text">{{ __('Manage Stock') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="showbox" id="size-display">
                                            <div class="row">
                                                    <div  class="col-lg-12">
                                                    </div>
                                                    <div  class="col-lg-12">
                                                        <div class="catalogItem-size-details" id="size-section">
                                                            <div class="size-area">
                                                                <span class="remove size-remove"><i class="fas fa-times"></i></span>
                                                                <div  class="row">
                                                                    <div class="col-md-3 col-sm-6">
                                                                        <label>
                                                                            {{ __('Size Name') }} :
                                                                            <span>
                                                                                {{ __('(eg. S,M,L,XL,3XL,4XL)') }}
                                                                            </span>
                                                                        </label>
                                                                        <select name="size[]" class="form-control size-name"></select>
                                                                    </div>
                                                                    <div class="col-md-3 col-sm-6">
                                                                        <label>
                                                                            {{ __('Size Qty') }} :
                                                                            <span>
                                                                                {{ __('(Quantity of this size)') }}
                                                                            </span>
                                                                        </label>
                                                                        <input type="number" name="size_qty[]" class="form-control" placeholder="{{ __('Size Qty') }}" value="1" min="1">
                                                                    </div>
                                                                    <div class="col-md-3 col-sm-6">
                                                                        <label>
                                                                            {{ __('Size Price') }} :
                                                                            <span>
                                                                                {{ __('(Added with base price)') }}
                                                                            </span>
                                                                        </label>
                                                                        <input type="number" name="size_price[]" class="form-control" placeholder="{{ __('Size Price') }}" value="0" min="0">
                                                                    </div>
                                                                    <div class="col-md-3 col-sm-6">
                                                                        <label>
                                                                            {{ __('Size Color') }} :
                                                                            <span>
                                                                                {{ __('(Select color of this size)') }}
                                                                            </span>
                                                                        </label>
                                                                        <select name="color[]" class="form-control color-name"></select>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>

                                                        <a href="javascript:;" id="size-btn" class="add-more"><i class="fas fa-plus"></i>{{ __('Add More') }} </a>
                                                    </div>
                                            </div>
                                        </div>

                                        <div class="row" id="default_stock">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Stock') }}*</h4>
                                                    <p class="sub-heading">{{ __('(Leave Empty will Show Always Available)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="stock"  type="number" class="form-control" placeholder="e.g 20" value="" min="0">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        {{ __('CatalogItem Description') }}*
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-editor">
                                                    <textarea class="nic-edit" name="details"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        {{ __('CatalogItem Buy/Return Policy') }}*
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="text-editor">
                                                    <textarea class="nic-edit" name="policy"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="checkbox-wrapper">
                                                    <input type="checkbox" name="seo_check" value="1" class="checkclick" id="allowProductSEO" value="1">
                                                    <label for="allowProductSEO">{{ __('Allow CatalogItem SEO') }}</label>
                                                </div>
                                            </div>
                                        </div>



                                    <div class="showbox">
                                        <div class="row">
                                            <div class="col-lg-12">
                                            <div class="left-area">
                                                <h4 class="heading">{{ __('Meta Tags') }} *</h4>
                                            </div>
                                            </div>
                                            <div class="col-lg-12">
                                            <ul id="metatags" class="myTags">
                                            </ul>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                            <div class="left-area">
                                                <h4 class="heading">
                                                    {{ __('Meta Description') }} *
                                                </h4>
                                            </div>
                                            </div>
                                            <div class="col-lg-12">
                                            <div class="text-editor">
                                                <textarea name="meta_description" class="form-control" placeholder="{{ __('Meta Description') }}"></textarea>
                                            </div>
                                            </div>
                                        </div>
                                        </div>

                                        <input type="hidden" name="type" value="Listing">
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <div class="col-lg-4">
                    <div class="add-catalogItem-content">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="catalogItem-description">
                                    <div class="body-area">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Feature Image') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                    <div class="panel panel-body">
                                                        <div class="span4 cropme text-center" id="landscape"
                                                            class="m-upload-zone">
                                                            <a href="javascript:;" id="crop-image" class="btn btn-primary" style="">
                                                                <i class="icofont-upload-alt"></i> {{ __('Upload Image Here') }}
                                                            </a>
                                                        </div>
                                                    </div>
                                            </div>
                                        </div>
                                        <input type="hidden" id="feature_photo" name="photo" value="">
                                        <input type="file" name="gallery[]" class="hidden" id="uploadgallery" accept="image/*"
                                            multiple>
                                        <div class="row mb-4">
                                            <div class="col-lg-12 mb-2">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        {{ __('CatalogItem Gallery Images') }} *
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <a href="#" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery">
                                                    <i class="icofont-plus"></i> {{ __('Set Gallery') }}
                                                </a>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        {{ __('CatalogItem Current Price') }}*
                                                    </h4>
                                                    <p class="sub-heading">
                                                        ({{ __('In') }} {{$sign->name}})
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="price" type="number" class="form-control" placeholder="{{ __('e.g 20') }}" step="0.1" required="" min="0">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Discount Price') }}*</h4>
                                                        <p class="sub-heading">{{ __('(Optional)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="previous_price" step="0.1" type="number" class="form-control" placeholder="{{ __('e.g 20') }}" min="0">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Youtube Video URL') }}*</h4>
                                                    <p class="sub-heading">{{ __('(Optional)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input  name="youtube" type="text" class="form-control" placeholder="{{ __('Enter Youtube Video URL') }}">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="featured-keyword-area">
                                                    <div class="heading-area">
                                                        <h4 class="title">{{ __('Feature Tags') }}</h4>
                                                    </div>

                                                    <div class="feature-tag-top-filds" id="feature-section">
                                                        <div class="feature-area">
                                                            <span class="remove feature-remove"><i class="fas fa-times"></i></span>
                                                            <div class="row">
                                                                <div class="col-lg-6">
                                                                <input type="text" name="features[]" class="form-control" placeholder="{{ __('Enter Your Keyword') }}">
                                                                </div>

                                                                <div class="col-lg-6">
                                                                    <div class="input-group colorpicker-component cp">
                                                                        <input type="text" name="colors[]" value="#000000" class="form-control cp"/>
                                                                        <span class="input-group-addon"><i></i></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <a href="javascript:;" id="feature-btn" class="add-fild-btn"><i class="icofont-plus"></i> {{ __('Add More Field') }}</a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Tags') }} *</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <ul id="tags" class="myTags">
                                                </ul>
                                            </div>
                                            </div>

                                            <div class="row text-center">
                                            <div class="col-6 offset-3">
                                                <button class="btn btn-primary" type="submit">{{ __('Create CatalogItem') }}</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="setgallery" tabindex="-1" role="dialog" aria-labelledby="setgallery" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalCenterTitle">{{ __('Image Gallery') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                
                </button>
            </div>
            <div class="modal-body">
                <div class="top-area">
                    <div class="row">
                        <div class="col-sm-6 text-right">
                            <div class="upload-img-btn">
                                        <label for="image-upload" id="prod_gallery"><i class="icofont-upload-alt"></i>{{ __('Upload File') }}</label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <a href="javascript:;" class="upload-done" data-bs-dismiss="modal"> <i class="fas fa-check"></i> {{ __('Done') }}</a>
                        </div>
                        <div class="col-sm-12 text-center">( <small>{{ __('You can upload multiple Images.') }}</small> )</div>
                    </div>
                </div>
                <div class="gallery-images">
                    <div class="selected-image">
                        <div class="row">


                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

		<script src="{{asset('assets/admin/js/jquery.Jcrop.js')}}"></script>
		<script src="{{asset('assets/admin/js/jquery.SimpleCropper.js')}}"></script>

<script type="text/javascript">

(function($) {
		"use strict";

// Gallery Section Insert

  $(document).on('click', '.remove-img' ,function() {
    var id = $(this).find('input[type=hidden]').val();
    $('#galval'+id).remove();
    $(this).parent().parent().remove();
  });

  $(document).on('click', '#prod_gallery' ,function() {
    $('#uploadgallery').click();
     $('.selected-image .row').html('');
    $('#muaadhform').find('.removegal').val(0);
  });


  $("#uploadgallery").change(function(){
     var total_file=document.getElementById("uploadgallery").files.length;
     for(var i=0;i<total_file;i++)
     {
      $('.selected-image .row').append('<div class="col-sm-6">'+
                                        '<div class="img gallery-img">'+
                                            '<span class="remove-img"><i class="fas fa-times"></i>'+
                                            '<input type="hidden" value="'+i+'">'+
                                            '</span>'+
                                            '<a href="'+URL.createObjectURL(event.target.files[i])+'" target="_blank">'+
                                            '<img src="'+URL.createObjectURL(event.target.files[i])+'" alt="gallery image">'+
                                            '</a>'+
                                        '</div>'+
                                  '</div> '
                                      );
      $('#muaadhform').append('<input type="hidden" name="galval[]" id="galval'+i+'" class="removegal" value="'+i+'">')
     }

  });

// Gallery Section Insert Ends

})(jQuery);

</script>

<script type="text/javascript">
(function($) {
		"use strict";

		$(document).ready(function() {
			$('.cropme').simpleCropper();
		});

})(jQuery);


$(document).on('click','#size-check',function(){
	if($(this).is(':checked')){
		$('#default_stock').addClass('d-none')
	}else{
		$('#default_stock').removeClass('d-none');
	}
})
</script>


@include('partials.admin.catalogItem.catalogItem-scripts')
@endsection
