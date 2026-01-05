@extends('layouts.operator')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading"> {{ __('Edit CatalogItem') }}<a class="add-btn" href="{{ url()->previous() }}"><i
                                class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="{{ route('operator-catalog-item-index') }}">{{ __('Catalog Items') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Physical CatalogItem') }}</a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Edit') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <form id="muaadhform" action="{{ route('operator-catalog-item-update', $merchantItem->id) }}" method="POST"
            enctype="multipart/form-data">
            {{ csrf_field() }}
            @include('alerts.operator.form-both')
            <div class="row">
                <div class="col-lg-8">
                    <div class="add-catalogItem-content">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="catalogItem-description">
                                    <div class="body-area">
                                        <div class="gocover"
                                            style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                                        </div>

                                        {{-- Merchant Selection --}}
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('Merchant') }}*</h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <select name="merchant_id" required="">
                                                    <option value="">{{ __('Select Merchant') }}</option>
                                                    @foreach ($merchants as $merchant)
                                                        <option value="{{ $merchant->id }}"
                                                            {{ $merchantItem->user_id == $merchant->id ? 'selected' : '' }}>
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
                                                        <option value="{{ $brand->id }}"
                                                            {{ $data->brand_id == $brand->id ? 'selected' : '' }}>
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
                                                    @foreach ($qualityBrands as $qb)
                                                        <option value="{{ $qb->id }}"
                                                            {{ $merchantItem->brand_quality_id == $qb->id ? 'selected' : '' }}>
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
                                                <input type="text" class="form-control"
                                                    placeholder="{{ __('Enter CatalogItem Name') }}"
                                                    name="name" required="" value="{{ $data->name }}">
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
                                                    name="label_en" value="{{ $data->label_en }}">
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
                                                    name="label_ar" value="{{ $data->label_ar }}">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Part_Number') }}* </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input type="text" class="form-control"
                                                    placeholder="{{ __('Enter CatalogItem Part_Number') }}" name="part_number"
                                                    required="" value="{{ $data->part_number }}">
                                            </div>
                                        </div>

                                        {{-- Old category system removed - Categories are now linked via parts tables (TreeCategories) --}}

                                        <div class="{{ !empty($data->size) ? ' showbox' : '' }}" id="stckprod">
                                            <div class="row">

                                                <div class="col-lg-12">
                                                    <div class="checkbox-wrapper">
                                                        <input type="checkbox" name="measure_check" class="checkclick1"
                                                            id="allowProductMeasurement" value="1"
                                                            {{ $data->measure == null ? '' : 'checked' }}>
                                                        <label
                                                            for="allowProductMeasurement">{{ __('Allow CatalogItem Measurement') }}</label>
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
                                                        <input class="checkclick1" name="item_condition_check"
                                                            type="checkbox" id="conditionCheck" value="1"
                                                            {{ $data->item_condition != 0 ? 'checked' : '' }}>
                                                        <label
                                                            for="conditionCheck">{{ __('Allow CatalogItem Condition') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="{{ $data->item_condition == 0 ? ' showbox' : '' }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Condition') }}*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <select name="item_condition">
                                                        <option value="2"
                                                            {{ $data->item_condition == 2 ? 'selected' : '' }}>
                                                            {{ __('New') }}</option>
                                                        <option value="1"
                                                            {{ $data->item_condition == 1 ? 'selected' : '' }}>
                                                            {{ __('Used') }}</option>
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
                                                        <input class="checkclick1" name="preordered_check"
                                                            type="checkbox" id="preorderedCheck" value="1"
                                                            {{ $data->preordered != 0 ? 'checked' : '' }}>
                                                        <label
                                                            for="preorderedCheck">{{ __('Allow CatalogItem Preorder') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="{{ $data->preordered == 0 ? ' showbox' : '' }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Preorder') }}*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <select name="preordered">
                                                        <option value="1"
                                                            {{ $data->preordered == 1 ? 'selected' : '' }}>
                                                            {{ __('Sale') }}</option>
                                                        <option value="2"
                                                            {{ $data->preordered == 2 ? 'selected' : '' }}>
                                                            {{ __('Preordered') }}</option>
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
                                                        <input class="checkclick1" name="minimum_qty_check"
                                                            type="checkbox" id="check111" value="1"
                                                            {{ $data->minimum_qty != null ? 'checked' : '' }}>
                                                        <label for="check111">{{ __('Allow Minimum Purchase Qty') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="{{ $data->minimum_qty != null ? '' : ' showbox' }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Minimum Purchase Qty') }}* </h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <input type="number" class="form-control" min="1"
                                                        placeholder="{{ __('Minimum Purchase Qty') }}" name="minimum_qty"
                                                        value="{{ $data->minimum_qty == null ? '' : $data->minimum_qty }}">
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
                                                        <input class="checkclick1" name="shipping_time_check"
                                                            type="checkbox" id="check1" value="1"
                                                            {{ $data->ship != null ? 'checked' : '' }}>
                                                        <label
                                                            for="check1">{{ __('Allow Estimated Shipping Time') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>


                                        <div class="{{ $data->ship != null ? '' : ' showbox' }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Estimated Shipping Time') }}*
                                                        </h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <input type="text" class="form-control"
                                                        placeholder="{{ __('Estimated Shipping Time') }}" name="ship"
                                                        value="{{ $data->ship == null ? '' : $data->ship }}">
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
                                                        <input class="checkclickc" name="color_check" type="checkbox"
                                                            id="check3" value="1"
                                                            {{ is_array($data->color_all) ? 'checked' : '' }}>
                                                        <label for="check3">{{ __('Allow CatalogItem Colors') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        @php
                                            
                                            
                                           
                                           
                                        @endphp
                                        <div class="{{is_array($data->color_all) ? '' : ' showbox' }}">
                                            <div class="row">

                                                <div class="col-lg-12">
                                                    <div class="select-input-color" id="color-section">
                                                        @if (is_array($data->color_all))
                                                            
                                                        @foreach ($data->color_all as $key => $color)
                                                        <div class="size-area">
                                                            <span class="remove size-remove"><i
                                                                    class="fas fa-times"></i></span>
                                                            <div class="row">
                                                                <div class="col-md-12 col-sm-12">
                                                                    <label>
                                                                        {{ __('Color') }} :
                                                                    </label>
                                                                    <div class="color-area">
                                                                        <div
                                                                            class="input-group colorpicker-component cp">
                                                                            <input type="text" name="color_all[]"
                                                                                value="{{ $color }}"
                                                                                class="form-control cp tcolor" />
                                                                            <span
                                                                                class="input-group-module"><i></i></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    @endforeach

                                                        @endif
                                                        
                                                    </div>
                                                    <a href="javascript:;" id="color-btn" class="add-more mt-4 mb-3"><i
                                                            class="fas fa-plus"></i>{{ __('Add More Color') }} </a>
                                                </div>

                                            </div>
                                        </div>


                                        <div class="{{ $data->measure == null ? 'showbox' : '' }}">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('CatalogItem Measurement') }}*</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <select id="product_measure">
                                                        <option value=""
                                                            {{ $data->measure == null ? 'selected' : '' }}>
                                                            {{ __('None') }}</option>
                                                        <option value="Gram"
                                                            {{ $data->measure == 'Gram' ? 'selected' : '' }}>
                                                            {{ __('Gram') }}</option>
                                                        <option value="Kilogram"
                                                            {{ $data->measure == 'Kilogram' ? 'selected' : '' }}>
                                                            {{ __('Kilogram') }}</option>
                                                        <option value="Litre"
                                                            {{ $data->measure == 'Litre' ? 'selected' : '' }}>
                                                            {{ __('Litre') }}</option>
                                                        <option value="Pound"
                                                            {{ $data->measure == 'Pound' ? 'selected' : '' }}>
                                                            {{ __('Pound') }}</option>
                                                        <option value="Custom"
                                                            {{ in_array($data->measure, explode(',', 'Gram,Kilogram,Litre,Pound')) ? '' : 'selected' }}>
                                                            {{ __('Custom') }}</option>
                                                    </select>
                                                </div>
                                                {{-- <div class="col-lg-1"></div> --}}
                                                <div class="col-lg-6 {{ in_array($data->measure, explode(',', 'Gram,Kilogram,Litre,Pound')) ? 'hidden' : '' }}"
                                                    id="measure">
                                                    <input name="measure" type="text" id="measurement"
                                                        class="form-control" placeholder="Enter Unit"
                                                        value="{{ $data->measure }}">
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
                                                        <input name="stock_check" class="stock-check" type="checkbox"
                                                            id="size-check" value="1"
                                                            {{ !empty($data->size) ? 'checked' : '' }}>
                                                        <label for="size-check"
                                                            class="stock-text">{{ __('Manage Stock') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="row {{ !empty($data->size) ? ' d-none' : '' }}" id="default_stock">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">{{ __('CatalogItem Stock') }}*</h4>
                                                    <p class="sub-heading">
                                                        {{ __('(Leave Empty will Show Always Available)') }}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="stock" type="number" class="form-control"
                                                    placeholder="e.g 20" value="{{ $data->stock }}" min="0">
                                            </div>
                                        </div>


                                        @php
                                        if(is_array($data->size)){
                                            $sizes = $data->size;
                                        }else{
                                            $sizes = array_filter(explode(',', $data->size));
                                        }
                                        @endphp

                                        <div class="{{ !empty($sizes) ? '' : ' showbox' }}" id="size-display">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="catalogItem-size-details" id="size-section">


                                                        @foreach ($sizes as $key => $size)
                                                            <div class="size-area">
                                                                <span class="remove size-remove"><i
                                                                        class="fas fa-times"></i></span>
                                                                <div class="row">
                                                                    <div class="col-md-4 col-sm-4">
                                                                        <label>
                                                                            {{ __('Size Name') }} :
                                                                            <span>
                                                                                {{ __('(eg. S,M,L,XL,3XL,4XL)') }}
                                                                            </span>
                                                                        </label>
                                                                        <input type="text" name="size[]"
                                                                            class="form-control tsize"
                                                                            placeholder="{{ __('Enter CatalogItem Size') }}"
                                                                            value="{{ $size }}" required="">
                                                                    </div>
                                                                    <div class="col-md-4 col-sm-4">
                                                                        <label>
                                                                            {{ __('Size Qty') }} :
                                                                            <span>
                                                                                {{ __('(Quantity of this size)') }}
                                                                            </span>
                                                                        </label>
                                                                        <input type="number" name="size_qty[]" required
                                                                            class="form-control"
                                                                            placeholder="{{ __('Size Qty') }}"
                                                                            value="{{ $data->size_qty[$key] }}"
                                                                            min="1">
                                                                    </div>
                                                                    <div class="col-md-4 col-sm-4">
                                                                        <label>
                                                                            {{ __('Size Price') }} :
                                                                            <span>
                                                                                {{ __('(Added with base price)') }}
                                                                            </span>
                                                                        </label>
                                                                        <input type="number" name="size_price[]" required
                                                                            class="form-control"
                                                                            placeholder="{{ __('Size Price') }}"
                                                                            value="{{ round($data->size_price[$key] * $curr->value, 2) }}"
                                                                            min="0">
                                                                    </div>


                                                                </div>
                                                            </div>
                                                        @endforeach


                                                    </div>

                                                    <a href="javascript:;" id="size-btn" class="add-more"><i
                                                            class="fas fa-plus"></i>{{ __('Add More') }} </a>
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
                                                        <input class="checkclick1" name="whole_check" type="checkbox"
                                                            id="whole_check" value="1"
                                                            {{ !empty($data->whole_sell_qty) ? 'checked' : '' }}>
                                                        <label
                                                            for="whole_check">{{ __('Allow CatalogItem Whole Sell') }}</label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="{{ !empty($data->whole_sell_qty) ? '' : ' showbox' }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">

                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <div class="featured-keyword-area">
                                                        <div class="feature-tag-top-filds" id="whole-section">
                                                            @if (!empty($data->whole_sell_qty))

                                                                @foreach ($data->whole_sell_qty as $key => $data1)
                                                                    <div class="feature-area">
                                                                        <span class="remove whole-remove"><i
                                                                                class="fas fa-times"></i></span>
                                                                        <div class="row">
                                                                            <div class="col-lg-6">
                                                                                <input type="number"
                                                                                    name="whole_sell_qty[]"
                                                                                    class="form-control"
                                                                                    placeholder="{{ __('Enter Quantity') }}"
                                                                                    min="0"
                                                                                    value="{{ $data->whole_sell_qty[$key] }}"
                                                                                    required="">
                                                                            </div>

                                                                            <div class="col-lg-6">
                                                                                <input type="number"
                                                                                    name="whole_sell_discount[]"
                                                                                    class="form-control"
                                                                                    placeholder="{{ __('Enter Discount Percentage') }}"
                                                                                    min="0"
                                                                                    value="{{ $data->whole_sell_discount[$key] }}"
                                                                                    required="">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <div class="feature-area">
                                                                    <span class="remove whole-remove"><i
                                                                            class="fas fa-times"></i></span>
                                                                    <div class="row">
                                                                        <div class="col-lg-6">
                                                                            <input type="number" name="whole_sell_qty[]"
                                                                                class="form-control"
                                                                                placeholder="{{ __('Enter Quantity') }}"
                                                                                min="0">
                                                                        </div>

                                                                        <div class="col-lg-6">
                                                                            <input type="number"
                                                                                name="whole_sell_discount[]"
                                                                                class="form-control"
                                                                                placeholder="{{ __('Enter Discount Percentage') }}"
                                                                                min="0" />
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                            @endif
                                                        </div>

                                                        <a href="javascript:;" id="whole-btn" class="add-fild-btn"><i
                                                                class="icofont-plus"></i> {{ __('Add More Field') }}</a>
                                                    </div>
                                                </div>
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
                                                    <textarea name="details" class="nic-edit">{{ $data->details }}</textarea>
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
                                                    <textarea name="policy" class="nic-edit">{{ $data->policy }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="checkbox-wrapper">
                                                    <input type="checkbox" name="seo_check" value="1"
                                                        class="checkclick" id="allowProductSEO"
                                                        {{ $data->meta_tag != null || strip_tags($data->meta_description) != null ? 'checked' : '' }}>
                                                    <label for="allowProductSEO">{{ __('Allow CatalogItem SEO') }}</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="{{ $data->meta_tag == null && strip_tags($data->meta_description) == null
                                                ? "
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    										showbox"
                                                : '' }}">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="left-area">
                                                        <h4 class="heading">{{ __('Meta Tags') }} *</h4>
                                                    </div>
                                                </div>
                                                <div class="col-lg-12">
                                                    <ul id="metatags" class="myTags">
                                                        @if (!empty($data->meta_tag))
                                                            @foreach ($data->meta_tag as $element)
                                                                <li>{{ $element }}</li>
                                                            @endforeach
                                                        @endif
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
                                                        <textarea name="meta_description" class="form-control" placeholder="{{ __('Details') }}">{{ $data->meta_description }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

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
                                                        <a href="javascript:;" id="crop-image"
                                                            class="d-inline-block btn btn-primary">
                                                            <i class="icofont-upload-alt"></i>
                                                            {{ __('Upload Image Here') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" id="feature_photo" name="photo"
                                            value="{{ $data->photo }}" accept="image/*">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">
                                                    <h4 class="heading">
                                                        {{ __('CatalogItem Gallery Images') }} *
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <a href="javascript" class="set-gallery" data-bs-toggle="modal"
                                                    data-bs-target="#setgallery">
                                                    <input type="hidden" value="{{ $data->id }}">
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
                                                        ({{ __('In') }} {{ $sign->name }})
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <input name="price" type="number" class="form-control"
                                                    placeholder="e.g 20" step="0.1" min="0"
                                                    value="{{ round($data->price * $sign->value, 2) }}" required="">
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
                                                <input name="previous_price" step="0.1" type="number"
                                                    class="form-control" placeholder="e.g 20"
                                                    value="{{ round($data->previous_price * $sign->value, 2) }}"
                                                    min="0">
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
                                                <input name="youtube" type="text" class="form-control"
                                                    placeholder="Enter Youtube Video URL" value="{{ $data->youtube }}">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="left-area">

                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="featured-keyword-area">
                                                    <div class="left-area">
                                                        <h4 class="title">{{ __('Feature Tags') }}</h4>
                                                    </div>

                                                    <div class="feature-tag-top-filds" id="feature-section">
                                                        @if (!empty($data->features))

                                                            @foreach ($data->features as $key => $data1)
                                                                <div class="feature-area">
                                                                    <span class="remove feature-remove"><i
                                                                            class="fas fa-times"></i></span>
                                                                    <div class="row">
                                                                        <div class="col-lg-6">
                                                                            <input type="text" name="features[]"
                                                                                class="form-control"
                                                                                placeholder="{{ __('Enter Your Keyword') }}"
                                                                                value="{{ $data->features[$key] }}">
                                                                        </div>

                                                                        <div class="col-lg-6">
                                                                            <div
                                                                                class="input-group colorpicker-component cp">
                                                                                <input type="text" name="colors[]"
                                                                                    value="{{ $data->colors[$key] }}"
                                                                                    class="form-control cp" />
                                                                                <span
                                                                                    class="input-group-module"><i></i></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="feature-area">
                                                                <span class="remove feature-remove"><i
                                                                        class="fas fa-times"></i></span>
                                                                <div class="row">
                                                                    <div class="col-lg-6">
                                                                        <input type="text" name="features[]"
                                                                            class="form-control"
                                                                            placeholder="{{ __('Enter Your Keyword') }}">
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="input-group colorpicker-component cp">
                                                                            <input type="text" name="colors[]"
                                                                                value="#000000" class="form-control cp" />
                                                                            <span class="input-group-module"><i></i></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        @endif
                                                    </div>

                                                    <a href="javascript:;" id="feature-btn" class="add-fild-btn"><i
                                                            class="icofont-plus"></i> {{ __('Add More Field') }}</a>
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
                                                    @if (!empty($data->tags))
                                                        @foreach ($data->tags as $element)
                                                            <li>{{ $element }}</li>
                                                        @endforeach
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="row text-center">
                                            <div class="col-6 offset-3">
                                                <button class="btn btn-primary"
                                                    type="submit">{{ __('Save') }}</button>
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

    <div class="modal fade" id="setgallery" tabindex="-1" role="dialog" aria-labelledby="setgallery"
        aria-hidden="true">
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
                                    <form method="POST" enctype="multipart/form-data" id="form-gallery">
                                        @csrf
                                        <input type="hidden" id="pid" name="catalog_item_id" value="">
                                        <input type="file" name="gallery[]" class="hidden" id="uploadgallery"
                                            accept="image/*" multiple>
                                        <label for="image-upload" id="prod_gallery"><i
                                                class="icofont-upload-alt"></i>{{ __('Upload File') }}</label>
                                    </form>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <a href="javascript:;" class="upload-done" data-bs-dismiss="modal"> <i
                                        class="fas fa-check"></i> {{ __('Done') }}</a>
                            </div>
                            <div class="col-sm-12 text-center">(
                                <small>{{ __('You can upload multiple Images.') }}</small>
                                )
                            </div>
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
    <script type="text/javascript">
        // Gallery Section Update



        $(document).on("click", ".set-gallery", function() {
            var pid = $(this).find('input[type=hidden]').val();
            $('#pid').val(pid);
            $('.selected-image .row').html('');
            $.ajax({
                type: "GET",
                url: "{{ route('operator-gallery-show') }}",
                data: {
                    id: pid
                },
                success: function(data) {
                    if (data[0] == 0) {
                        $('.selected-image .row').addClass('justify-content-center');
                        $('.selected-image .row').html('<h3>{{ __('No Images Found.') }}</h3>');
                    } else {
                        $('.selected-image .row').removeClass('justify-content-center');
                        $('.selected-image .row h3').remove();
                        var arr = $.map(data[1], function(el) {
                            return el
                        });

                        for (var k in arr) {
                            $('.selected-image .row').append('<div class="col-sm-6">' +
                                '<div class="img gallery-img">' +
                                '<span class="remove-img"><i class="fas fa-times"></i>' +
                                '<input type="hidden" value="' + arr[k]['id'] + '">' +
                                '</span>' +
                                '<a href="' + '{{ asset('assets/images/merchant-photos') . '/' }}' +
                                arr[
                                    k]['photo'] + '" target="_blank">' +
                                '<img src="' + '{{ asset('assets/images/merchant-photos') . '/' }}' +
                                arr[
                                    k]['photo'] + '" alt="gallery image">' +
                                '</a>' +
                                '</div>' +
                                '</div>');
                        }
                    }

                }
            });
        });


        $(document).on('click', '.remove-img', function() {
            var id = $(this).find('input[type=hidden]').val();
            $(this).parent().parent().remove();
            $.ajax({
                type: "GET",
                url: "{{ route('operator-gallery-delete') }}",
                data: {
                    id: id
                }
            });
        });

        $(document).on('click', '#prod_gallery', function() {
            $('#uploadgallery').click();
        });


        $("#uploadgallery").change(function() {
            $("#form-gallery").submit();
        });

        $('#form-gallery').on('submit', function() {
            $.ajax({
                url: "{{ route('operator-gallery-store') }}",
                method: "POST",
                data: new FormData(this),
                dataType: 'JSON',
                contentType: false,
                cache: false,
                processData: false,
                success: function(data) {
                    if (data != 0) {
                        $('.selected-image .row').removeClass('justify-content-center');
                        $('.selected-image .row h3').remove();
                        var arr = $.map(data, function(el) {
                            return el
                        });
                        for (var k in arr) {
                            $('.selected-image .row').append('<div class="col-sm-6">' +
                                '<div class="img gallery-img">' +
                                '<span class="remove-img"><i class="fas fa-times"></i>' +
                                '<input type="hidden" value="' + arr[k]['id'] + '">' +
                                '</span>' +
                                '<a href="' + '{{ asset('assets/images/merchant-photos') . '/' }}' +
                                arr[
                                    k]['photo'] + '" target="_blank">' +
                                '<img src="' + '{{ asset('assets/images/merchant-photos') . '/' }}' +
                                arr[
                                    k]['photo'] + '" alt="gallery image">' +
                                '</a>' +
                                '</div>' +
                                '</div>');
                        }
                    }

                }

            });
            return false;
        });


        $('.cp').colorpicker();

        // Gallery Section Update Ends
    </script>

    <script src="{{ asset('assets/admin/js/jquery.Jcrop.js') }}"></script>

    <script src="{{ asset('assets/admin/js/jquery.SimpleCropper.js') }}"></script>
    <script src="{{ asset('assets/admin/js/select2.js') }}"></script>

    <script type="text/javascript">
        (function($) {
            "use strict";

            $('.cropme').simpleCropper();

            $(document).ready(function() {
                $('.select2').select2({
                    placeholder: "Select Catalog Items",
                    maximumSelectionLength: 4,
                });
            });

        })(jQuery);
    </script>


    <script type="text/javascript">
        (function($) {
            "use strict";

            $(document).ready(function() {

                let html =
                    `<img src="{{ empty($data->photo) ? asset('assets/images/noimage.png') : (filter_var($data->photo, FILTER_VALIDATE_URL) ? $data->photo : ($data->photo ? \Illuminate\Support\Facades\Storage::url($data->photo) : asset('assets/images/noimage.png'))) }}" alt="">`;
                $(".span4.cropme").html(html);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

            });


            $('.ok').on('click', function() {

                setTimeout(
                    function() {


                        var img = $('#feature_photo').val();

                        $.ajax({
                            url: "{{ route('operator-catalog-item-upload-update', $data->id) }}",
                            type: "POST",
                            data: {
                                "image": img
                            },
                            success: function(data) {
                                if (data.status) {
                                    $('#feature_photo').val(data.file_name);
                                }
                                if ((data.errors)) {
                                    for (var error in data.errors) {
                                        $.notify(data.errors[error], "danger");
                                    }
                                }
                            }
                        });

                    }, 1000);



            });

        })(jQuery);
    </script>

    <script type="text/javascript">
        (function($) {
            "use strict";

            $('#imageSource').on('change', function() {
                var file = this.value;
                if (file == "file") {
                    $('#f-file').show();
                    $('#f-link').hide();
                }
                if (file == "link") {
                    $('#f-file').hide();
                    $('#f-link').show();
                }
            });



            $(document).on('click', '#size-check', function() {
                if ($(this).is(':checked')) {
                    $('#default_stock').addClass('d-none')
                } else {
                    $('#default_stock').removeClass('d-none');
                }
            })



        })(jQuery);
    </script>


    @include('partials.operator.catalogItem.catalogItem-scripts')
@endsection
