<div class="modal fade gs-modal" id="merchant_shipping{{$merchant_id}}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterName" aria-hidden="true">
    <div class="modal-dialog send-message-modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content send-message-modal-content form-group">
            <div class="modal-header w-100">
                <h4 class="name" id="exampleModalLongName">@lang('Shipping')</h4>
                <button type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                </button>
            </div>
            <div class="packeging-area">
                <div class="summary-inner-box">
                    <div class="inputs-wrapper">
                        @forelse($shipping as $data)
                        <div class="gs-radio-wrapper">
                            <input type="radio" class="shipping" ref="{{$merchant_id}}"
                                data-price="{{ round($data->price * $curr->value,2) }}"
                                view="{{ $curr->sign }}{{ round($data->price * $curr->value,2) }}"
                                data-form="{{$data->name}}" id="free-shepping{{ $data->id }}"
                                name="shipping[{{$merchant_id}}]" value="{{ $data->id }}" {{ ($loop->first) ?
                            'checked' :
                            ''
                            }}>
                            <label class="icon-label" for="free-shepping{{ $data->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                    fill="none">
                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                    <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                </svg>
                            </label>

                            <label for="free-shepping{{ $data->id }}">
                                {{ $data->name }}
                                @if($data->price != 0)
                                + {{ $curr->sign }}{{ round($data->price * $curr->value,2) }}
                                @endif
                                <small>{{ $data->subname }}</small>
                            </label>
                        </div>
                        @empty
                        <p>
                            @lang('No Shipping Method Available')
                        </p>
                        @endforelse

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>