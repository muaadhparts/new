@extends('layouts.admin')

@section('content')
    <div class="content-area">
        <div class="mr-breadcrumb">
            <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __('Home Page') }}</h4>
                    <ul class="links">
                        <li>
                            <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li>
                            <a href="javascript:;">{{ __('Home Page Setting') }}</a>
                        </li>
                        <li>
                            <a href="{{ route('admin-home-page-index') }}">{{ __('Home Page') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="add-product-content1 add-product-content2">
            <div class="row">
                <div class="col-lg-12">
                    <div class="product-description">
                        <div class="body-area p-4">
                            <div class="gocover"
                                style="background: url({{ asset('assets/images/' . $gs->admin_loader) }}) no-repeat scroll center center rgba(45, 45, 45, 0.5);">
                            </div>

                            @include('alerts.form-success')

                            <div class="row  justify-content-center">
                                <div class="col-lg-4 mb-4 mb-lg-0">
                                    <form action="{{ route('admin-gs-update-theme') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="theme" value="theme1">
                                        <div class="d-flex flex-column align-items-center" >
                                            <label for="">Home Page 1</label>
                                            <div class="img-upload scroll-container"
                                                style="max-height: 240px; overflow-y: hidden;">
                                                <div id="image-preview" class="img-preview text-center"
                                                    style="background: url('{{ asset('assets/admin/theme1.png') }}') no-repeat center; background-size: cover; height: 400px;">
                                                </div>
                                            </div>
                                            <div class="row justify-content-center mt-3">
                                                <button class="addProductSubmit-btn" type="submit">
                                                    @if ($gs->theme == 'theme1')
                                                        {{ __('Active') }}
                                                    @else
                                                        {{ __('Theme 1') }}
                                                    @endif
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4 mb-4 mb-lg-0">
                                    <form action="{{ route('admin-gs-update-theme') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="theme" value="theme2">

                                        <div class="d-flex flex-column align-items-center" >
                                            <label for="">Home Page 2</label>
                                            <div class="img-upload scroll-container"
                                                style="max-height: 240px; overflow-y: hidden;">
                                                <div id="image-preview" class="img-preview text-center"
                                                    style="background: url('{{ asset('assets/admin/theme2.png') }}') no-repeat center; background-size: cover; height: 400px;">
                                                </div>
                                            </div>
                                            <div class="row justify-content-center mt-3">
                                                <button class="addProductSubmit-btn" type="submit">
                                                    @if ($gs->theme == 'theme2')
                                                        {{ __('Active') }}
                                                    @else
                                                        {{ __('Theme 2') }}
                                                    @endif
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4 mb-4 mb-lg-0">
                                    <form action="{{ route('admin-gs-update-theme') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="theme" value="theme3">
                                        <div class="d-flex flex-column align-items-center" >
                                            <label for="">Home Page 3</label>
                                            <div class="img-upload scroll-container"
                                                style="max-height: 240px; overflow-y: hidden;">
                                                <div id="image-preview" class="img-preview text-center"
                                                    style="background: url('{{ asset('assets/admin/theme3.png') }}') no-repeat center; background-size: cover; height: 400px;">
                                                </div>
                                            </div>
                                            <div class="row justify-content-center mt-3">
                                                <button class="addProductSubmit-btn" type="submit">
                                                    @if ($gs->theme == 'theme3')
                                                        {{ __('Active') }}
                                                    @else
                                                        {{ __('Theme 3') }}
                                                    @endif
                                                </button>
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
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scrollContainers = document.querySelectorAll('.scroll-container');

            scrollContainers.forEach(scrollContainer => {
                let isScrolling;

                scrollContainer.addEventListener('mouseenter', function() {
                    isScrolling = setInterval(function() {
                        scrollContainer.scrollTop += 1;
                        if (scrollContainer.scrollTop + scrollContainer.clientHeight >=
                            scrollContainer.scrollHeight) {
                            scrollContainer.scrollTop = 0;
                        }
                    }, 30); // Adjust speed here (lower number = faster scroll)
                });

                scrollContainer.addEventListener('mouseleave', function() {
                    clearInterval(isScrolling);
                });
            });
        });
    </script>
@endsection
