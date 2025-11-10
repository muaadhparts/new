@extends('layouts.unified')
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
                        @include('includes.form-success')
                        <form action="{{ route('user-profile-update') }}" method="POST" enctype="multipart/form-data" id="profile-form">
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
                                                        <select class="form-control form__control"
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
                                                        <select class="form-control form__control"
                                                            name="state_id" id="show_state">
                                                            <option value="">@lang('Select State')</option>
                                                            @if ($user->country)
                                                                @php
                                                                    $country = App\Models\Country::where(
                                                                        'country_name',
                                                                        $user->country,
                                                                    )->first();
                                                                    if ($country) {
                                                                        $states = App\Models\State::whereCountryId(
                                                                            $country->id,
                                                                        )
                                                                            ->whereStatus(1)
                                                                            ->get();
                                                                    } else {
                                                                        $states = collect();
                                                                    }
                                                                @endphp
                                                                @foreach ($states as $state)
                                                                    @php
                                                                        $stateDisplayName = (app()->getLocale() == 'ar')
                                                                            ? ($state->state_ar ?: $state->state)
                                                                            : $state->state;
                                                                    @endphp
                                                                    <option value="{{ $state->id }}"
                                                                        {{ $user->state_id == $state->id ? 'selected' : '' }}>
                                                                        {{ $stateDisplayName }}</option>
                                                                @endforeach
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
                                                            class="form-control form__control form-control-sm"
                                                            id="show_city" name="city_id">
                                                            <option value="">@lang('Select City')</option>
                                                            @if ($user->state_id)
                                                                @php
                                                                    $cities = App\Models\City::whereStateId(
                                                                        $user->state_id,
                                                                    )
                                                                        ->whereStatus(1)
                                                                        ->get();
                                                                @endphp
                                                                @foreach ($cities as $city)
                                                                    @php
                                                                        $cityDisplayName = (app()->getLocale() == 'ar')
                                                                            ? ($city->city_name_ar ?: $city->city_name)
                                                                            : $city->city_name;
                                                                    @endphp
                                                                    <option value="{{ $city->id }}"
                                                                        {{ $user->city_id == $city->id ? 'selected' : '' }}>
                                                                        {{ $cityDisplayName }}</option>
                                                                @endforeach
                                                            @endif
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

                                        <!-- Hidden fields for latitude and longitude -->
                                        <input type="hidden" name="latitude" id="latitude" value="{{ $user->latitude ?? '' }}">
                                        <input type="hidden" name="longitude" id="longitude" value="{{ $user->longitude ?? '' }}">

                                        <!-- Google Maps Button -->
                                        <div class="form-group">
                                            <button type="button" class="template-btn dark-btn" data-bs-toggle="modal" data-bs-target="#mapModal">
                                                <i class="fas fa-map-marker-alt"></i> @lang('Select Location from Map')
                                            </button>
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

    <!-- Google Maps Modal -->
    @include('partials.google-maps-modal')
@endsection
@section('script')
    <script>
    // Global flag to track if NiceSelect updates are from map selection
    window.isMapSelection = false;

    // Helper function to safely initialize NiceSelect
    function safeInitNiceSelect(elementId, options) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.warn('⚠️ Element not found:', elementId);
            return false;
        }

        // Check if already bound
        if (element.dataset.nsBound === "1") {
            console.log('ℹ️ NiceSelect already bound for:', elementId);
            return false;
        }

        // Check if element has meaningful options (more than just placeholder)
        const hasOptions = element.options.length > 1;
        const hasSelectedValue = element.value && element.value !== '';

        console.log(`🔍 ${elementId}:`, {
            totalOptions: element.options.length,
            currentValue: element.value,
            hasOptions,
            hasSelectedValue
        });

        // Only initialize if has options OR has selected value
        if (hasOptions || hasSelectedValue) {
            try {
                NiceSelect.bind(element, options);
                element.dataset.nsBound = "1";
                console.log(`✅ NiceSelect initialized for ${elementId}:`, {
                    value: element.value,
                    text: element.options[element.selectedIndex]?.text
                });
                return true;
            } catch (error) {
                console.error(`❌ Error initializing NiceSelect for ${elementId}:`, error);
                return false;
            }
        } else {
            console.log(`⏭️ Skipping ${elementId} (no options or value)`);
            return false;
        }
    }

    // Initialize NiceSelect for all dropdowns on page load
    document.addEventListener("DOMContentLoaded", function () {
        var options = { searchable: true };

        console.log('🔧 Initializing NiceSelect for saved values...');
        console.log('📊 Saved values from database:');
        console.log('  Country:', '{{ $user->country ?? "Not set" }}');
        console.log('  State ID:', '{{ $user->state_id ?? "Not set" }}');
        console.log('  City ID:', '{{ $user->city_id ?? "Not set" }}');
        console.log('  Address:', '{{ Str::limit($user->address ?? "Not set", 50) }}');
        console.log('  Latitude:', '{{ $user->latitude ?? "Not set" }}');
        console.log('  Longitude:', '{{ $user->longitude ?? "Not set" }}');

        // Initialize dropdowns
        safeInitNiceSelect("select_country", options);
        safeInitNiceSelect("show_state", options);
        safeInitNiceSelect("show_city", options);

        console.log('✅ NiceSelect initialization complete');
    });

    // Global function to reinitialize state NiceSelect
    window.reinitStateNiceSelect = function(valueToSelect) {
        var options = { searchable: true };
        const stateEl = document.getElementById("show_state");
        if (!stateEl) {
            console.warn('⚠️ State element not found');
            return;
        }

        console.log('🔄 Reinitializing state NiceSelect with value:', valueToSelect);

        // Set the value BEFORE initializing NiceSelect
        if (valueToSelect) {
            stateEl.value = valueToSelect;
            console.log('✓ State value set:', stateEl.value);
        }

        // Destroy old NiceSelect instance if exists
        const oldNiceSelect = stateEl.nextElementSibling;
        if (oldNiceSelect && oldNiceSelect.classList.contains('nice-select')) {
            oldNiceSelect.remove();
            stateEl.style.display = '';
            stateEl.dataset.nsBound = null;
            console.log('✓ Old NiceSelect removed');
        }

        // Verify we have a value or options before initializing
        const hasValue = stateEl.value && stateEl.value !== '';
        const hasOptions = stateEl.options.length > 1;

        console.log('State element status:', {
            value: stateEl.value,
            selectedText: stateEl.options[stateEl.selectedIndex]?.text,
            hasValue,
            hasOptions,
            totalOptions: stateEl.options.length
        });

        // Create new NiceSelect instance
        if (typeof NiceSelect !== 'undefined' && (hasValue || hasOptions)) {
            NiceSelect.bind(stateEl, options);
            stateEl.dataset.nsBound = "1";
            console.log('✅ State NiceSelect re-initialized successfully');
            console.log('   Final value:', stateEl.value);
            console.log('   Final display:', $('#show_state option:selected').text());
        } else {
            console.warn('⚠️ Skipping NiceSelect init - no value or options');
        }
    };

    // Global function to reinitialize city NiceSelect
    window.reinitCityNiceSelect = function(valueToSelect) {
        var options = { searchable: true };
        const cityEl = document.getElementById("show_city");
        if (!cityEl) {
            console.warn('⚠️ City element not found');
            return;
        }

        console.log('🔄 Reinitializing city NiceSelect with value:', valueToSelect);

        // Set the value BEFORE initializing NiceSelect
        if (valueToSelect) {
            cityEl.value = valueToSelect;
            console.log('✓ City value set:', cityEl.value);
        }

        // Destroy old NiceSelect instance if exists
        const oldNiceSelect = cityEl.nextElementSibling;
        if (oldNiceSelect && oldNiceSelect.classList.contains('nice-select')) {
            oldNiceSelect.remove();
            cityEl.style.display = '';
            cityEl.dataset.nsBound = null;
            console.log('✓ Old NiceSelect removed');
        }

        // Verify we have a value or options before initializing
        const hasValue = cityEl.value && cityEl.value !== '';
        const hasOptions = cityEl.options.length > 1;

        console.log('City element status:', {
            value: cityEl.value,
            selectedText: cityEl.options[cityEl.selectedIndex]?.text,
            hasValue,
            hasOptions,
            totalOptions: cityEl.options.length
        });

        // Create new NiceSelect instance
        if (typeof NiceSelect !== 'undefined' && (hasValue || hasOptions)) {
            NiceSelect.bind(cityEl, options);
            cityEl.dataset.nsBound = "1";
            console.log('✅ City NiceSelect re-initialized successfully');
            console.log('   Final value:', cityEl.value);
            console.log('   Final display:', $('#show_city option:selected').text());
        } else {
            console.warn('⚠️ Skipping NiceSelect init - no value or options');
        }
    };

    $(document).on('change', '#select_country', function() {
        let state_url = $('option:selected', this).attr('data-href');

        if (!state_url) return;

        console.log('📡 Country changed, loading states from:', state_url);

        $.get(state_url, function(response) {
            var options = { searchable: true };

            // Store the old state value if exists (for preserving selection)
            const oldStateValue = $('#show_state').val();

            $('#show_state').html(response.data);
            console.log('📥 States loaded, count:', $('#show_state option').length);

            // Clear city dropdown when country changes (unless from map)
            if (!window.isMapSelection) {
                $('#show_city').html('<option value="">@lang("Select City")</option>');
            }

            // Handle map selection case
            if (window.isMapSelection && window.onStatesLoaded) {
                console.log('🎯 Map selection: calling onStatesLoaded callback...');
                window.onStatesLoaded(window.reinitStateNiceSelect);
            } else {
                // Normal change: preserve old value if it exists in new options
                if (oldStateValue && $('#show_state option[value="' + oldStateValue + '"]').length) {
                    window.reinitStateNiceSelect(oldStateValue);
                } else {
                    window.reinitStateNiceSelect(null);
                }
            }
        });
    });

    $(document).on('change', '#show_state', function() {
        let state_id = $(this).val();

        if (!state_id) return;

        console.log('📡 State changed, loading cities for state_id:', state_id);

        $.get("{{ route('state.wise.city.user') }}", {
            state_id: state_id
        }, function(data) {
            var options = { searchable: true };

            // Store the old city value if exists (for preserving selection)
            const oldCityValue = $('#show_city').val();

            $('#show_city').html(data.data);
            console.log('📥 Cities loaded, count:', $('#show_city option').length);

            // Handle map selection case
            if (window.isMapSelection && window.onCitiesLoaded) {
                console.log('🎯 Map selection: calling onCitiesLoaded callback...');
                window.onCitiesLoaded(window.reinitCityNiceSelect);
            } else {
                // Normal change: preserve old value if it exists in new options
                if (oldCityValue && $('#show_city option[value="' + oldCityValue + '"]').length) {
                    window.reinitCityNiceSelect(oldCityValue);
                } else {
                    window.reinitCityNiceSelect(null);
                }
            }
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

    // Monitor form submission
    $('#profile-form').on('submit', function(e) {
        console.log('📤 Form submission - Values being sent:');
        console.log('  Country:', $('#select_country').val(), '(', $('#select_country option:selected').text(), ')');
        console.log('  State ID:', $('#show_state').val(), '(', $('#show_state option:selected').text(), ')');
        console.log('  City ID:', $('#show_city').val(), '(', $('#show_city option:selected').text(), ')');
        console.log('  Address:', $('#address').val());
        console.log('  Latitude:', $('#latitude').val());
        console.log('  Longitude:', $('#longitude').val());

        // Log all form data
        const formData = new FormData(this);
        console.log('📋 Complete form data:');
        for (let [key, value] of formData.entries()) {
            if (key !== '_token' && key !== 'photo') {
                console.log(`  ${key}:`, value);
            }
        }
    });
    </script>
@endsection
