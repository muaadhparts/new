{{--<div>--}}


{{--    <table class="table table-bordered table-striped text-center">--}}
{{--        <thead class="table-dark">--}}
{{--        <tr>--}}
{{--            <th>شركة الشحن</th>--}}
{{--            <th>نوع الخدمة</th>--}}
{{--            <th>وقت التوصيل</th>--}}
{{--            <th>طريقة استلام الطلب</th>--}}
{{--            <th>طريقة التوصيل</th>--}}
{{--            <th>السعر</th>--}}
{{--            <th>الإجراء</th>--}}
{{--        </tr>--}}
{{--        </thead>--}}
{{--        <tbody>--}}

{{--        @foreach($deliveryCompany as $company)--}}

{{--            @dd($company['deliveryCompanyName'])--}}
{{--            "serviceType" => "pudo"--}}
{{--            "deliveryOptionName" => "SPL - PUDO"--}}
{{--            "trackingType" => "excellent"--}}
{{--            "codCharge" => 8--}}
{{--            "maxOrderValue" => 10000--}}
{{--            "maxCODValue" => 5000--}}
{{--            "deliveryOptionId" => 5436--}}
{{--            "extraWeightPerKg" => 1--}}
{{--            "deliveryCompanyName" => "saudiPost"--}}
{{--            "returnFee" => 14--}}
{{--            "maxFreeWeight" => 15--}}
{{--            "avgDeliveryTime" => "1 to 2 Working Days"--}}
{{--            "price" => 14--}}
{{--            "logo" => "https://storage.googleapis.com/tryoto-public/delivery-logo/spl.png"--}}
{{--            "currency" => "SAR"--}}
{{--            "pickupDropoff" => "dropoffOnly"--}}
{{--            "cardOnDeliveryPercentage" => "8.0 SAR plus 1.75% of the amount to be collected"--}}

{{--        <tr>--}}
{{--            <td>{{$company['deliveryCompanyName']}}</td>--}}
{{--            <td><img src="{{$company['logo']}}" alt="{{$company['deliveryCompanyName']}}" srcset="" width="100" height="100"> </td>--}}
{{--            <td>{{$company['serviceType']}}</td>--}}

{{--            <td>التسليم والاستلام من الفرع</td>--}}
{{--            <td>{{$company['avgDeliveryTime']}}</td>--}}
{{--            <td>{{$company['price']}} {{$company['currency']}}</td>--}}
{{--             <td>{{$company['pickupDropoff']}}</td>--}}
{{--            <td>من ١ إلى ٥ أيام عمل</td>--}}
{{--            <td>تسليم لفرع شركة الشحن</td>--}}
{{--            <td>الاستلام بواسطة العميل</td>--}}
{{--            <td>14 ر.س</td>--}}
{{--            <td><button class="btn btn-primary">اختر</button></td>--}}
{{--        </tr>--}}
{{--        @endforeach--}}


{{--        </tbody>--}}
{{--    </table>--}}



{{--</div>--}}

<div class="row">
    @if($deliveryCompany)
        <table class="table table-bordered table-hover xalign-middle ">
            <thead class="table-light">
            <tr>
                <th>اختيار</th>

                <th>الخدمة</th>
                <th>السعر</th>
{{--                <th>مدة التوصيل</th>--}}
                <th>الشعار</th>
            </tr>
            </thead>
            <tbody>
             @foreach($deliveryCompany as $company)
                <tr>
                    <!-- Radio Input -->
                    <td class="text-center col-1"   xcolspan="2">
                        <input type="radio" class="shipping" ref="0" data-price="{{ round($company['price'] * $curr->value, 2) }}" view="{{$company['price'] }}"

                               data-form="{{ $company['deliveryCompanyName'] }}"

                               id="free-shepping16" name="shipping[0]" value="{{'16'.'#'.$company['deliveryCompanyName'] . '#'. $company['price'] }}">

{{--                        <input type="radio"--}}
{{--                               wire:click="selectedOption('{{ $company['price']  }}  {{ $company['deliveryOptionName']  }}')"--}}

{{--                               wire:click="selectedOption('{{  $company['price'] }}#{{ $company['deliveryCompanyName'] }}')"--}}

{{--                               --}}{{--                               wire:model="selected($company)"--}}
{{--                               wire:model="selectedOption($company)"--}}

{{--                               class="shipping"--}}
{{--                               ref="{{ $company['deliveryOptionName'] }}"--}}
{{--                               data-price="{{ round($company['price'] * $curr->value, 2) }}"--}}
{{--                               view="{{ round($company['price'] * $curr->value, 2) }} {{ $company['currency'] }}"--}}
{{--                               data-form="{{ $company['deliveryCompanyName'] }}"--}}
{{--                               id="free-shipping{{ $company['deliveryOptionName'] }}"--}}
{{--                               id="free-shepping16"--}}
{{--                               name="shipping[0]" value="16" {{ ($loop->first) ?--}}
{{--                            'checked' :--}}
{{--                            ''--}}
{{--                            }}>--}}

{{--//                               name="shipping[{{$vendor_id}}]" value="16"   ?--}}
{{--                               value="{{ $company['deliveryCompanyName'] . '#'. $company['price']}}"--}}
{{--                                {{ $loop->first ? 'checked' : '' }}>--}}
                    </td>



                    <!-- Company Name -->
                    <td>
                        <label for="free-shipping16">
                            <p>{{ $company['deliveryCompanyName'] }}</p>
                            <small class="text-muted">{{ $company['avgDeliveryTime'] }}</small>
                        </label>
                    </td>

                    <!-- Price -->
                    <td class="col-6">
                        @if($company['price'] != 0)
                            + {{ round($company['price'] * $curr->value, 2) }} {{ $company['currency'] }}
                        @else
                            مجاناً
                        @endif
                    </td>

                    <!-- Company Logo -->
                    <td class="text-center col-3">
                        <img src="{{ $company['logo'] }}"
                             alt="{{ $company['deliveryCompanyName'] }}"
                             class="img-fluid rounded border"
                             style="max-width: 80px; max-height: 80px; object-fit: contain;">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>


{{--<div>--}}
{{--    @if($deliveryCompany)--}}
{{--        @foreach($deliveryCompany as $company)--}}
{{--            <div class="gs-radio-wrapper">--}}
{{--                --}}{{-- Radio Input --}}
{{--                <input type="radio"--}}
{{--                       class="shipping"--}}
{{--                       ref="{{ $company['deliveryOptionName'] }}"--}}
{{--                       data-price="{{ round($company['price'] * $curr->value, 2) }}"--}}
{{--                       view="{{ round($company['price'] * $curr->value, 2) }} {{ $company['currency'] }}"--}}
{{--                       data-form="{{ $company['deliveryOptionName'] }}"--}}
{{--                       id="free-shipping{{ $company['deliveryOptionName'] }}"--}}
{{--                       name="shipping[0]"--}}
{{--                       value="{{ $company['deliveryOptionName'] }}"--}}
{{--                        {{ $loop->first ? 'checked' : '' }}>--}}

{{--                --}}{{-- Custom Radio Label --}}
{{--                <label class="icon-label" for="free-shipping{{ $company['deliveryOptionName'] }}">--}}
{{--                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">--}}
{{--                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />--}}
{{--                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />--}}
{{--                        <circle cx="10" cy="10" r="4" fill="#EE1243" />--}}
{{--                    </svg>--}}
{{--                </label>--}}

{{--                --}}{{-- Shipping Info Label --}}
{{--                <label for="free-shipping{{ $company['deliveryOptionName'] }}">--}}
{{--                    @if($company['price'] != 0)--}}
{{--                        + {{ round($company['price'] * $curr->value, 2) }} {{ $company['currency'] }}--}}
{{--                    @endif--}}
{{--                    <small>{{ $company['deliveryOptionName'] }}</small>--}}
{{--                    <small>({{ $company['avgDeliveryTime'] }})</small>--}}
{{--                </label>--}}

{{--                <div>--}}
{{--                    <img src="{{ $company['logo'] }}"--}}
{{--                         alt="{{ $company['deliveryCompanyName'] }}"--}}
{{--                         class="img-fluid rounded border"--}}
{{--                         style="max-width: 60px; max-height: 40px; object-fit: contain;">--}}
{{--                </div>--}}

{{--                --}}{{-- Company Logo --}}

{{--            </div>--}}
{{--        @endforeach--}}
{{--    @endif--}}
{{--</div>--}}


{{--<div>--}}
{{--    @if($deliveryCompany)--}}
{{--    @foreach($deliveryCompany as $company)--}}
{{--         <div class="gs-radio-wrapper">--}}

{{--        --}}{{--    @dd($company)--}}
{{--            <input type="radio" class="shipping" ref="{{$company['deliveryOptionName']}}"--}}
{{--                   data-price="{{ round($company['price'] * $curr->value,2) }}"--}}
{{--                   view=" {{ round($company['price']  * $curr->value,2) }}  {{  $company['currency']}} "--}}
{{--                   data-form="{{ $company['deliveryOptionName'] }}" id="free-shepping{{ $company['deliveryOptionName']}}"--}}
{{--                   name="shipping[0]" value="{{$company['deliveryOptionName'] }}" {{ ($loop->first) ?--}}
{{--                                    'checked' :--}}
{{--                                    ''--}}
{{--                                    }}>--}}


{{--             <label class="icon-label" for="free-shepping{{ $company['deliveryOptionName']}}">--}}
{{--                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"--}}
{{--                             fill="none">--}}
{{--                            <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />--}}
{{--                            <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />--}}
{{--                            <circle cx="10" cy="10" r="4" fill="#EE1243" />--}}
{{--                        </svg>--}}
{{--            </label>--}}

{{--            <label for="free-shepping{{ $company['deliveryOptionName'] }}">--}}
{{--        --}}{{--         {{$company['deliveryOptionName'] }}--}}
{{--                @if($company['price'] != 0)--}}
{{--                    + {{ round($company['price']  * $curr->value,2) }} {{  $company['currency'] }}--}}
{{--                @endif--}}
{{--                <small> {{$company['deliveryOptionName'] }} </small>--}}
{{--                <small>  ({{$company['avgDeliveryTime']}})</small>--}}

{{--            </label>--}}
{{--             <img src="{{$company['logo']}}" alt="{{$company['deliveryCompanyName']}}" srcset="" width="100" height="100">--}}

{{--         </div>--}}
{{--            @endforeach--}}
{{--    @endif--}}
{{--</div>--}}