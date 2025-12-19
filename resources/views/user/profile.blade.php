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
                                    <div class="col-lg-8 col-12 order-2 order-lg-1">
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
                                                    <label for="show_state">@lang('Select State')</label>
                                                    <div class="dropdown-container">
                                                        <select class="form-control nice-select form__control"
                                                            name="state_id" id="show_state">
                                                            <option value="">@lang('Select State')</option>
                                                            @if ($user->country)
                                                                @php
                                                                    $country = App\Models\Country::where(
                                                                        'country_name',
                                                                        $user->country,
                                                                    )->first();
                                                                    $states = App\Models\State::whereCountryId(
                                                                        $country->id,
                                                                    )
                                                                        ->whereStatus(1)
                                                                        ->get();
                                                                @endphp
                                                                @foreach ($states as $state)
                                                                    <option value="{{ $state->id }}"
                                                                        {{ $user->state_id == $state->id ? 'selected' : '' }}>
                                                                        {{ $state->state }}</option>
                                                                @endforeach
                                                            @else
                                                                <option value="">@lang('Select State')</option>
                                                            @endif
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="multi-form-wrapper d-flex gap-4 flex-column flex-sm-row">
                                            <div class="single-form-wrapper flex-grow-1 w-50">
                                                <div class="form-group">
                                                    <label for="city">@lang('Select City')</label>
                                                    <div class="dropdown-container">
                                                        <select
                                                            class="form-control nice-select form__control form-control-sm"
                                                            id="show_city" name="city_id">
                                                            @if ($user->state_id)
                                                                @php
                                                                    $cities = App\Models\City::whereStateId(
                                                                        $user->state_id,
                                                                    )
                                                                        ->whereStatus(1)
                                                                        ->get();
                                                                @endphp
                                                                @foreach ($cities as $city)
                                                                    <option value="{{ $city->id }}"
                                                                        {{ $user->city_id == $city->id ? 'selected' : '' }}>
                                                                        {{ $city->city_name }}</option>
                                                                @endforeach
                                                            @else
                                                                <option value="">@lang('Select City')</option>
                                                            @endif
                                                            <!-- Add more options here if needed -->
                                                        </select>
                                                    </div>
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
                                    <div class="col-lg-4 col-12 order-1 order-lg-2">
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
        $(document).on('change', '#select_country', function() {
            let state_url = $('option:selected', this).attr('data-href');

            $.get(state_url, function(response) {
                $('#show_state').html(response.data);
                $("#show_state").niceSelect("destroy");
                $("#show_state").niceSelect();
            });
        });

        $(document).on('change', '#show_state', function() {
            let state_id = $(this).val();
            $.get("{{ route('state.wise.city.user') }}", {
                state_id: state_id
            }, function(data) {
                $('#show_city').html(data.data);
                $("#show_city").niceSelect("destroy");
                $("#show_city").niceSelect();
            });
        });

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
