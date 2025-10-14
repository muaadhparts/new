@extends('layouts.front')
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.rider.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="gs-edit-profile-section">
                        <div class="ud-page-title-box gap-4">
                            <!-- mobile sidebar trigger btn -->
                            <a href="{{ url()->previous() }}" class="back-btn">
                                <i class="fa-solid fa-arrow-left-long"></i>
                            </a>
    
                            <h3 class="ud-page-title">@lang('Edit Service Area')</h3>
                        </div>
                        <form id="userform" action="{{ route('rider-service-area-update', $service_area->id) }}"
                            method="POST">
                            @csrf
                            <div class="edit-profile-area">
                                <div class="row">
                                    <div class="col-lg-8 col-12 order-2 order-lg-1">
                                        <div class="multi-form-wrapper gap-4">
                                            <div class="single-form-wrapper">
                                                <div class="form-group">
                                                    <label for="name">@lang('Service Area')</label>
                                                    <select
                                                        class="service_area form-control nice-select form__control form-control-sm"
                                                        name="service_area_id" id="service_area_id">
                                                        @foreach ($cities as $city)
                                                            <option value="{{ $city->id }}"
                                                                {{ $service_area->city_id == $city->id ? 'selected' : '' }}>
                                                                {{ $city->city_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('service_area_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="single-form-wrapper">
                                                <div class="form-group">
                                                    <label for="price">@lang('Delivery Cost') ({{ $curr->name }})</label>
                                                    <input type="text" id="price" class="form-control"
                                                        placeholder="@lang('price')"
                                                        value="{{ round($service_area->price * $curr->value, 2) }}" required
                                                        name="price">
                                                </div>
                                            </div>
                                            @error('price')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror

                                        </div>


                                        <button class="template-btn btn-forms" type="submit">
                                            @lang('Update Service Area')
                                        </button>
                                    </div>

                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
