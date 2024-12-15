@extends('layouts.load')

@section('styles')
    <link href="{{ asset('assets/admin/css/jquery-ui.css') }}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <div class="content-area">
        <div class="social-links-area">
            <div class="add-product-content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="product-description">
                            <div class="body-area">
                                @include('alerts.admin.form-error')
                                <form id="geniusformdata" action="{{ route('admin-prod-feature', $data->id) }}"
                                    method="POST" enctype="multipart/form-data">
                                    {{ csrf_field() }}



                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="left-area">
                                                <h4 class="heading">{{ __('Highlight in') }} {{ __('New Arrival') }} *</h4>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="switch">
                                                <input type="checkbox" name="latest" value="1"
                                                    {{ $data->latest == 1 ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>



                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="left-area">
                                                <h4 class="heading">{{ __('Highlight in') }} {{ __('Best Seller') }} *</h4>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="switch">
                                                <input type="checkbox" name="best" value="1"
                                                    {{ $data->best == 1 ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>



                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="left-area">
                                                <h4 class="heading">{{ __('Highlight in') }} {{ __('Featured') }} *</h4>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="switch">
                                                <input type="checkbox" name="featured" value="1"
                                                    {{ $data->featured == 1 ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="left-area">
                                                <h4 class="heading">{{ __('Highlight in') }} {{ __('Popular') }} *</h4>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="switch">
                                                <input type="checkbox" name="popular" value="1"
                                                    {{ $data->popular == 1 ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>



                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="left-area">
                                                <h4 class="heading">{{ __('Highlight in') }} {{ __('Trending') }} *</h4>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="switch">
                                                <input type="checkbox" name="trending" value="1"
                                                    {{ $data->trending == 1 ? 'checked' : '' }}>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-5">
                                            <div class="left-area">

                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <button class="addProductSubmit-btn"
                                                type="submit">{{ __('Submit') }}</button>
                                        </div>
                                    </div>


                                </form>


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
        (function($) {
            "use strict";

            $('#is_discount').on('change', function() {

                if (this.checked) {
                    $(this).parent().parent().parent().next().removeClass('showbox');
                    $('#discount').prop('required', true);
                    $('#discount_date').prop('required', true);
                } else {
                    $(this).parent().parent().parent().next().addClass('showbox');
                    $('#discount').prop('required', false);
                    $('#discount_date').prop('required', false);
                }

            });

        })(jQuery);
    </script>
@endsection
