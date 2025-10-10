<div class="modal fade gs-modal" id="vendor_shipping{{$vendor_id}}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog xsend-message-modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content send-message-modal-content form-group">
            <div class="modal-header w-100">
                <h4 class="title" id="exampleModalLongTitle">@lang('Shipping')</h4>
                <button type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                </button>
            </div>
            <div class="packeging-area">
                <div class="summary-inner-box">
                    <div class="inputs-wrapper">
                        @php
                            // تجميع طرق الشحن حسب provider
                            $manualShipping = $shipping->where('provider', 'manual');
                            $tryotoShipping = $shipping->where('provider', 'tryoto');
                            $hasAnyShipping = $manualShipping->count() > 0 || $tryotoShipping->count() > 0;
                        @endphp

                        @if(!$hasAnyShipping)
                            <p>@lang('No Shipping Method Available')</p>
                        @else

                            {{-- قسم Manual Shipping --}}
                            @if($manualShipping->count() > 0)
                                <div class="shipping-provider-section manual-shipping-section mb-3">
                                    <h6 class="provider-section-title mb-2" style="font-weight: 600; color: #4C3533;">
                                        @lang('Manual Shipping Methods')
                                    </h6>
                                    <div class="provider-methods-wrapper">
                                        @foreach($manualShipping as $index => $data)
                                            <div class="gs-radio-wrapper">
                                                <input type="radio" class="shipping" ref="{{$vendor_id}}"
                                                       data-price="{{ round($data->price * $curr->value,2) }}"
                                                       data-free-above="{{ round(($data->free_above ?? 0) * $curr->value,2) }}"
                                                       view="{{ $curr->sign }}{{ round($data->price * $curr->value,2) }}"
                                                       data-form="{{$data->title}}" id="manual-shipping-{{ $vendor_id }}-{{ $data->id }}"
                                                       name="shipping[{{$vendor_id}}]" value="{{ $data->id }}"
                                                       {{ ($index === 0 && $tryotoShipping->count() === 0) ? 'checked' : '' }}>

                                                <label class="icon-label" for="manual-shipping-{{ $vendor_id }}-{{ $data->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                                        <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                                    </svg>
                                                </label>

                                                <label for="manual-shipping-{{ $vendor_id }}-{{ $data->id }}">
                                                    <span class="shipping-title">{{ $data->title }}</span>
                                                    <span class="shipping-price-display">
                                                        @if($data->price != 0)
                                                            + {{ $curr->sign }}{{ round($data->price * $curr->value,2) }}
                                                        @endif
                                                    </span>
                                                    <small class="d-block">{{ $data->subtitle }}</small>
                                                    @if(($data->free_above ?? 0) > 0)
                                                        <small class="text-success d-block free-shipping-hint">
                                                            @lang('Free shipping if order above') {{ $curr->sign }}{{ round($data->free_above * $curr->value,2) }}
                                                        </small>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- قسم Tryoto Shipping --}}
                            @if($tryotoShipping->count() > 0)
                                <div class="shipping-provider-section tryoto-shipping-section">
                                    <h6 class="provider-section-title mb-2" style="font-weight: 600; color: #4C3533;">
                                        @lang('Smart Shipping (Tryoto)')
                                    </h6>
                                    <div class="provider-methods-wrapper">
                                        <livewire:tryoto-componet :products="$array_product" :vendor-id="$vendor_id" />
                                    </div>
                                </div>
                            @endif

                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>