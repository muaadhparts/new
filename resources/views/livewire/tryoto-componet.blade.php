<div class="row" data-vendor-id="{{ $vendor_id ?? $vendorId ?? 0 }}">
    @if($hasError)
        {{-- عرض رسالة الخطأ --}}
        <div class="col-12">
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-3" style="font-size: 24px;"></i>
                <div>
                    <strong>خدمة الشحن الذكي غير متاحة</strong>
                    <p class="mb-0 mt-1">{{ $errorMessage }}</p>
                </div>
            </div>
        </div>
    @elseif($deliveryCompany)
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
            <tr>
                <th>اختيار</th>
                <th>الخدمة</th>
                <th>السعر</th>
                <th>الشعار</th>
            </tr>
            </thead>
            <tbody>
            @foreach($deliveryCompany as $company)
                @php
                    $inputId = 'shipping-' . $company['deliveryOptionId'] . '-' . $loop->index;
                    $value   = $company['deliveryOptionId'] . '#' . $company['deliveryCompanyName'] . '#' . $company['price'];
                @endphp

                <tr>
                    <!-- Radio Input -->
                    <td class="text-center col-1">
                        <input type="radio"
                               class="shipping"
                               ref="{{ $vendor_id ?? $vendorId ?? 0 }}"
                               data-price="{{ round($company['price'] * $curr->value, 2) }}"
                               view="{{ round($company['price'] * $curr->value, 2) }} {{ $company['currency'] }}"
                               data-form="{{ $company['deliveryCompanyName'] }}"
                               data-logo="{{ $company['logo'] ?? '' }}"
                               data-service="{{ $company['avgDeliveryTime'] ?? '' }}"
                               id="{{ $inputId }}"
                               name="shipping[{{ $vendor_id ?? $vendorId ?? 0 }}]"
                               value="{{ $value }}"
                               wire:change="selectedOption($event.target.value)">
                    </td>

                    <!-- Company Name -->
                    <td>
                        <label for="{{ $inputId }}">
                            <p class="mb-1">{{ $company['deliveryCompanyName'] }}</p>
                            <small class="text-muted">{{ $company['avgDeliveryTime'] }}</small>
                        </label>
                    </td>

                    <!-- Price -->
                    <td class="col-4">
                        @if($company['price'] > 0)
                            + {{ round($company['price'] * $curr->value, 2) }} {{ $company['currency'] }}
                        @else
                            @lang('Free')
                        @endif
                    </td>

                    <!-- Company Logo -->
                    <td class="text-center col-3">
                        <img src="{{ $company['logo'] ?? asset('images/default-logo.png') }}"
                             alt="{{ $company['deliveryCompanyName'] }}"
                             class="img-fluid rounded border"
                             style="max-width: 80px; max-height: 80px; object-fit: contain;">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        {{-- لا توجد شركات شحن --}}
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                جاري تحميل شركات الشحن...
            </div>
        </div>
    @endif
</div>
