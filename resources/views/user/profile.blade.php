@extends('layouts.front')
@section('content')
    <div class="gs-user-panel-review wow fadeInUp" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="gs-edit-profile-section">
                        <h3>@lang('Edit Profile')</h3>
                        <form action="{{ route('user-profile-update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="edit-profile-area">
                                <div class="row">
                                    <div class="col-lg-8 col-12 purchase-2 purchase-lg-1">
                                        <div class="multi-form-wrapper d-flex gap-4 flex-column flex-sm-row">
                                            <div class="single-form-wrapper flex-grow-1">
                                                <div class="form-group">
                                                    <label for="name">@lang('User Name')</label>
                                                    <input type="text" id="name" name="name" class="form-control"
                                                        placeholder="@lang('User Name')" value="{{ $user->name }}">
                                                </div>
                                            </div>
                                            <div class="single-form-wrapper flex-grow-1">
                                                <div class="form-group">
                                                    <label for="Email">@lang('Email')</label>
                                                    <input type="text" id="Email" class="form-control"
                                                        placeholder="@lang('Email')" value="{{ $user->email }}"
                                                        name="email">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="multi-form-wrapper d-flex gap-4 flex-column flex-sm-row">
                                            <div class="single-form-wrapper flex-grow-1">
                                                <div class="form-group">
                                                    <label for="Phone-Number">@lang('Phone Number')</label>
                                                    <input type="text" id="Phone-Number" class="form-control"
                                                        placeholder="@lang('Phone Number')" value="{{ $user->phone }}"
                                                        name="phone">
                                                </div>
                                            </div>
                                            <div class="single-form-wrapper flex-grow-1">
                                                <div class="form-group">
                                                    <label for="Fax">@lang('Fax')</label>
                                                    <input type="text" id="Fax" class="form-control"
                                                        placeholder="@lang('Fax')" value="{{ $user->fax }}"
                                                        name="fax">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="multi-form-wrapper d-flex gap-4 flex-column flex-sm-row">
                                            <div class="single-form-wrapper flex-grow-1">
                                                <div class="form-group">
                                                    <label for="select_country">@lang('Select Country')</label>
                                                    <div class="dropdown-container">
                                                        <select class="form-control nice-select form__control"
                                                            id="select_country" name="country">
                                                            @include('includes.countries')
                                                            <!-- Add more options here if needed -->
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="single-form-wrapper flex-grow-1">
                                                <div class="form-group">
                                                    <label for="customer_state">@lang('State/Region')</label>
                                                    <input type="text" id="customer_state" class="form-control"
                                                        placeholder="@lang('State/Region')"
                                                        value="{{ $user->customer_state ?? '' }}"
                                                        name="customer_state">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="multi-form-wrapper d-flex gap-4 flex-column flex-sm-row">
                                            <div class="single-form-wrapper flex-grow-1 w-50">
                                                <div class="form-group">
                                                    <label for="customer_city">@lang('City')</label>
                                                    <input type="text" id="customer_city" class="form-control"
                                                        placeholder="@lang('City')"
                                                        value="{{ $user->customer_city ?? '' }}"
                                                        name="customer_city">
                                                </div>
                                            </div>
                                            <div class="single-form-wrapper flex-grow-1 w-50">
                                                <div class="form-group">
                                                    <label for="zip">@lang('Zip')</label>
                                                    <input type="text" id="zip" class="form-control"
                                                        placeholder="@lang('Zip')" value="{{ $user->zip }}"
                                                        name="zip">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="address">@lang('Address')</label>
                                            <textarea id="address" class="form-control" name="address" placeholder="@lang('Address')" style="height: 122px">{{ $user->address }}</textarea>
                                        </div>

                                        <button class="template-btn btn-forms" type="submit">
                                            @lang('Update Profile Information')
                                        </button>
                                    </div>
                                    <div class="col-lg-4 col-12 purchase-1 purchase-lg-2">
                                        <div class="profile-img">
                                            @if ($user->is_provider == 1)
                                                <img src="{{ $user->photo ? asset($user->photo) : asset('assets/images/' . $gs->user_image) }}"
                                                    alt="">
                                            @else
                                                <img src="{{ $user->photo ? asset('assets/images/users/' . $user->photo) : asset('assets/images/' . $gs->user_image) }}"
                                                    alt="">
                                            @endif
                                            <input type="file" class="d-none" name="photo" id="photo">
                                            <label for="photo" class="template-btn dark-btn pro-btn-forms">
                                                @lang('Upload Picture')
                                            </label>
                                        </div>
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
@section('script')
    <script>
        $(document).on("change", "#photo", function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('.profile-img img').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
@endsection
