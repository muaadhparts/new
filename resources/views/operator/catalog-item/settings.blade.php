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

                            <h4 class="text-center">{{ __('PAGINATION SETTINGS') }}</h4>

                            <hr>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Category Page Items Per Page') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Category Page Items Per Page') }}" name="page_count"
                                        value="{{ $gs->page_count }}" required="" min="1">
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Favorites Page Items Per Page') }} *
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <input type="number" class="form-control"
                                        placeholder="{{ __('Favorites Page Items Per Page') }}" name="favorite_count"
                                        value="{{ $gs->favorite_count }}" required="" min="1">
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="left-area">
                                        <h4 class="heading">{{ __('Page Size Options') }} *
                                        </h4>
                                        <p class="sub-heading">{{ __('Options shown in catalog page dropdown') }}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <ul id="item_page" class="myTags">
                                        @foreach (explode(',',$gs->item_page) as $element)
                                          <li>{{ $element }}</li>
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

<script type="text/javascript">
(function($) {
    "use strict";

    $("#item_page").tagit({
        fieldName: "item_page[]",
        allowSpaces: true
    });

})(jQuery);
</script>

@endsection
