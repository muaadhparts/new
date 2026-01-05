@extends('layouts.operator')

@section('content')

<div class="content-area">

    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('CatalogItem Settings') }}</h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('CatalogItems') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('CatalogItem Settings') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="add-catalogItem-content1 add-catalogItem-content2">
        <div class="row">
            <div class="col-lg-12">
                <div class="catalogItem-description">
                    <div class="body-area">
                        <div class="gocover"
                            style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                        </div>
                        <form action="{{ route('operator-gs-catalog-item-settings-update') }}" id="muaadhform" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            @include('alerts.operator.form-both')

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">
                                            {{ __('Display Stock Number') }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="action-list">
                                        <select
                                            class="process select droplinks {{ $gs->show_stock == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['show_stock',1])}}"
                                                {{ $gs->show_stock == 1 ? 'selected' : '' }}>{{ __('Activated') }}
                                            </option>
                                            <option data-val="0" value="{{route('operator-gs-status',['show_stock',0])}}"
                                                {{ $gs->show_stock == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('CatalogItem Whole Sale Max Quantity') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('CatalogItem Whole Sale Max Quantity') }}" name="wholesell"
                                        value="{{ $gs->wholesell }}" required="" min="0">
                                </div>
                            </div>

                            <hr>

                            <h4 class="text-center">{{ __('HOME PAGE SECTION') }}</h4>
      
                            <hr>



                    


                  

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display New CatalogItems') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Display New CatalogItems') }}" name="new_count"
                                        value="{{ $gs->new_count }}" required="" min="0">
                                </div>
                            </div>



                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display Best Seller CatalogItems') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Display Best Seller CatalogItems') }}"
                                        name="best_seller_count" value="{{ $gs->best_seller_count }}" required=""
                                        min="0">
                                </div>
                            </div>


                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display Popular CatalogItems') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Display Popular CatalogItems') }}" name="popular_count"
                                        value="{{ $gs->popular_count }}" required="" min="0">
                                </div>
                            </div>



                       

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display Trending CatalogItems') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Display Trending CatalogItems') }}" name="trending_count"
                                        value="{{ $gs->trending_count }}" required="" min="0">
                                </div>
                            </div>


                            <hr>

                            <h4 class="text-center">{{ __('CATEGORY PAGE SECTION') }}</h4>
      
                            <hr>


                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display CatalogItems Per Page') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Display CatalogItems Per Page') }}" name="page_count"
                                        value="{{ $gs->page_count }}" required="" min="0">
                                </div>
                            </div>

                            <hr>

                            <h4 class="text-center">{{ __('MERCHANT PAGE SECTION') }}</h4>

                            <hr>


                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display CatalogItems Per Page') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Display CatalogItems Per Page') }}" name="merchant_page_count"
                                        value="{{ $gs->merchant_page_count }}" required="" min="0">
                                </div>
                            </div>


                            <hr>

                            <h4 class="text-center">{{ __('CATALOGITEM DETAILS PAGE SECTION') }}</h4>
      
                            <hr>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">
                                            {{ __('Display Contact Seller') }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="action-list">
                                        <select
                                            class="process select droplinks {{ $gs->is_contact_seller == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['is_contact_seller',1])}}"
                                                {{ $gs->is_contact_seller == 1 ? 'selected' : '' }}>{{ __('Activated') }}
                                            </option>
                                            <option data-val="0" value="{{route('operator-gs-status',['is_contact_seller',0])}}"
                                                {{ $gs->is_contact_seller == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>



                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Display CatalogItem By Seller') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Display CatalogItems Per Page') }}" name="seller_item_count"
                                        value="{{ $gs->seller_item_count }}" required="" min="0">
                                </div>
                            </div>


                            <hr>
                            <h4 class="text-center">{{ __('MERCHANT CATALOGITEM CREATE ENABLE & DISABLE') }}</h4>
      
                            <hr>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">
                                            {{ __('Merchant Physical CatalogItems') }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="action-list">
                                        <select
                                            class="process select droplinks {{ $gs->physical == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['physical',1])}}"
                                                {{ $gs->physical == 1 ? 'selected' : '' }}>{{ __('Activated') }}
                                            </option>
                                            <option data-val="0" value="{{route('operator-gs-status',['physical',0])}}"
                                                {{ $gs->physical == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">
                                            {{ __('Merchant Digital CatalogItems') }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="action-list">
                                        <select
                                            class="process select droplinks {{ $gs->digital == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['digital',1])}}"
                                                {{ $gs->digital == 1 ? 'selected' : '' }}>{{ __('Activated') }}
                                            </option>
                                            <option data-val="0" value="{{route('operator-gs-status',['digital',0])}}"
                                                {{ $gs->digital == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">
                                            {{ __('Merchant License CatalogItems') }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="action-list">
                                        <select
                                            class="process select droplinks {{ $gs->license == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['license',1])}}"
                                                {{ $gs->license == 1 ? 'selected' : '' }}>{{ __('Activated') }}
                                            </option>
                                            <option data-val="0" value="{{route('operator-gs-status',['license',0])}}"
                                                {{ $gs->license == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">
                                            {{ __('Merchant Listing CatalogItems') }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="action-list">
                                        <select
                                            class="process select droplinks {{ $gs->listing == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['listing',1])}}"
                                                {{ $gs->listing == 1 ? 'selected' : '' }}>{{ __('Activated') }}
                                            </option>
                                            <option data-val="0" value="{{route('operator-gs-status',['listing',0])}}"
                                                {{ $gs->listing == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">
                                            {{ __('Merchant Affiliate CatalogItems') }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="action-list">
                                        <select
                                            class="process select droplinks {{ $gs->affilite == 1 ? 'drop-success' : 'drop-danger' }}">
                                            <option data-val="1" value="{{route('operator-gs-status',['affilite',1])}}"
                                                {{ $gs->affilite == 1 ? 'selected' : '' }}>{{ __('Activated') }}
                                            </option>
                                            <option data-val="0" value="{{route('operator-gs-status',['affilite',0])}}"
                                                {{ $gs->affilite == 0 ? 'selected' : '' }}>{{ __('Deactivated') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>




                            <hr>

                            <h4 class="text-center">{{ __('FAVORITES PAGE SECTION') }}</h4>
      
                            <hr>


                                <div class="row justify-content-center">
                                    <div class="col-lg-3">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('Display CatalogItems Per Page') }} *
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="number" class="form-control"
                                            placeholder="{{ __('Display CatalogItems Per Page') }}" name="favorite_count"
                                            value="{{ $gs->favorite_count }}" required="" min="0">
                                    </div>
                                </div>
                                <div class="row justify-content-center">
                                    <div class="col-lg-3">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('View Favorites CatalogItem Per Page') }} *
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <ul id="favorite_page" class="myTags">
                                            @foreach (explode(',',$gs->favorite_page) as $element)
                                              <li>{{  $element }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>




                                <hr>

                                <h4 class="text-center">{{ __('CATALOG & FILTER SECTION') }}</h4>
          
                                <hr>
    
                                <div class="row justify-content-center">
                                    <div class="col-lg-3">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('Minimum Price') }} *
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="number" class="form-control"
                                            placeholder="{{ __('Minimum Price') }}" name="min_price"
                                            value="{{ $gs->min_price }}" required="" min="0">
                                    </div>
                                </div>

                                <div class="row justify-content-center">
                                    <div class="col-lg-3">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('Maximum Price') }} *
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="number" class="form-control"
                                            placeholder="{{ __('Maximum Price') }}" name="max_price"
                                            value="{{ $gs->max_price }}" required="" min="0">
                                    </div>
                                </div>


                                <div class="row justify-content-center">
                                    <div class="col-lg-3">
                                        <div class="left-area">
                                            <h4 class="heading">{{ __('View CatalogItem Per Page') }} *
                                            </h4>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <ul id="item_page" class="myTags">
                                            @foreach (explode(',',$gs->item_page) as $element)
                                              <li>{{  $element }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">

                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection


@section('scripts')

{{-- TAGIT --}}

<script type="text/javascript">

(function($) {
		"use strict";

          $("#item_page").tagit({
            fieldName: "item_page[]",
            allowSpaces: true
          });
          $("#favorite_page").tagit({
            fieldName: "favorite_page[]",
            allowSpaces: true 
          });

})(jQuery);

</script>

{{-- TAGIT ENDS--}}

@endsection

