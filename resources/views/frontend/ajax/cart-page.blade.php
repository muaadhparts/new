<div class="container gs-cart-container">
    <div class="row gs-cart-row">


        @if (Session::has('cart'))

            @php
                $discount = 0;
            @endphp



            <div class="col-lg-8">
                @if(isset($productsByVendor) && !empty($productsByVendor))
                    {{-- عرض سلة منفصلة لكل تاجر --}}
                    @foreach($productsByVendor as $vendorId => $vendorData)
                        <div class="vendor-cart-section mb-4" style="border: 2px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                            {{-- معلومات التاجر --}}
                            <div class="vendor-header p-3 mb-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <h5 class="mb-1" style="color: white; font-weight: 600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; margin-right: 8px;">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                    {{ $vendorData['vendor_name'] }}
                                </h5>
                                <small style="color: rgba(255,255,255,0.9); font-size: 0.85em;">
                                    {{ $vendorData['count'] }} @lang('Items') •
                                    @lang('Total'): {{ App\Models\Product::convertPrice($vendorData['total']) }}
                                </small>
                            </div>

                            {{-- جدول المنتجات لهذا التاجر --}}
                            <div class="cart-table table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr class="">
                                            <th scope="col">@lang('Product Name')</th>
                                            <th scope="col">@lang('Price')</th>
                                            <th scope="col">@lang('Quantity')</th>
                                            <th scope="col">@lang('Subtotal')</th>
                                            <th scope="col">@lang('Action')</th>
                                        </tr>
                                    </thead>
                                    <tbody class="t_body">
                                        @foreach ($vendorData['products'] as $product)
                                @php
                                    // Safe conversion of potentially array variables to strings for item identification
                                    $sizeStr = is_array($product['size']) ? implode('-', $product['size']) : ($product['size'] ?? '');
                                    $colorStr = is_array($product['color']) ? implode('-', $product['color']) : ($product['color'] ?? '');
                                    $valuesStr = is_array($product['values']) ? implode('-', $product['values']) : ($product['values'] ?? '');
                                    $valuesStr = str_replace([' ', ','], '', $valuesStr);

                                    // Create unique item identifier
                                    $itemIdentifier = $product['item']['id'] . $sizeStr . $colorStr . $valuesStr;

                                    if ($product['discount'] != 0) {
                                        $total_itemprice = $product['item_price'] * $product['qty'];
                                        $tdiscount = ($total_itemprice * $product['discount']) / 100;
                                        $discount += $tdiscount;
                                    }

                                @endphp


                                <tr class="">
                                    <td class="cart-product-area">
                                        <div class="cart-product d-flex">
                                            <img src="{{ $product['item']['photo'] ? \Illuminate\Support\Facades\Storage::url($product['item']['photo']) : asset('assets/images/noimage.png') }}"
                                                alt="">
                                            <div class="cart-product-info">
                                                @php
                                                    $cartProductUrl = '#';
                                                    if (isset($product['item']['slug']) && isset($product['user_id']) && isset($product['merchant_product_id'])) {
                                                        $cartProductUrl = route('front.product', [
                                                            'slug' => $product['item']['slug'],
                                                            'vendor_id' => $product['user_id'],
                                                            'merchant_product_id' => $product['merchant_product_id']
                                                        ]);
                                                    } elseif (isset($product['item']['slug'])) {
                                                        $cartProductUrl = route('front.product.legacy', $product['item']['slug']);
                                                    }
                                                @endphp

                                                <a class="cart-title d-inline-block"
                                                    href="{{ $cartProductUrl }}">{{ mb_strlen($product['item']['name'], 'UTF-8') > 35
                                                        ? mb_substr($product['item']['name'], 0, 35, 'UTF-8') . '...'
                                                        : $product['item']['name'] }}</a>

                                                <div class="d-flex align-items-center gap-2">
                                                    @if (!empty($product['color']))
                                                        @lang('Color') : <p
                                                            class="cart-color d-inline-block rounded-2"
                                                            style="border: 10px solid #{{ $product['color'] == '' ? 'white' : $product['color'] }};">
                                                        </p>
                                                    @endif
                                                    @if (!empty($product['size']))
                                                        @lang('Size') : <p class="d-inline-block">
                                                            {{ $product['size'] }}
                                                        </p>
                                                    @endif
                                                </div>

                                            </div>
                                        </div>
                                    </td>
                                    <td class="cart-price">
                                        {{ App\Models\Product::convertPrice($product['item_price']) }}</td>

                                    @if ($product['item']['type'] == 'Physical')
                                        <td>
                                            <div class="cart-quantity">
                                                <button class="cart-quantity-btn quantity-down">-</button>
                                                <input type="text"
                                                    id="qty{{ $itemIdentifier }}"
                                                    value="{{ $product['qty'] }}" class="borderless" readonly>
                                                <input type="hidden" class="prodid"
                                                    value="{{ $product['item']['id'] }}">
                                                <input type="hidden" class="itemid"
                                                    value="{{ $itemIdentifier }}">
                                                <input type="hidden" class="size_qty"
                                                    value="{{ $product['size_qty'] }}">
                                                <input type="hidden" class="size_price"
                                                    value="{{ $product['size_price'] }}">
                                                <input type="hidden" class="minimum_qty"
                                                    value="{{ $product['item']['minimum_qty'] == null ? '0' : $product['item']['minimum_qty'] }}">

                                                <button class="cart-quantity-btn quantity-up">+</button>
                                            </div>
                                        </td>
                                    @else
                                        <td class="product-quantity">
                                            1
                                        </td>
                                    @endif


                                    @if ($product['size_qty'])
                                        <input type="hidden"
                                            id="stock{{ $itemIdentifier }}"
                                            value="{{ $product['size_qty'] }}">
                                    @elseif($product['item']['type'] != 'Physical')
                                        <input type="hidden"
                                            id="stock{{ $itemIdentifier }}"
                                            value="1">
                                    @else
                                        <input type="hidden"
                                            id="stock{{ $itemIdentifier }}"
                                            value="{{ $product['stock'] }}">
                                    @endif



                                    <td class="cart-price"
                                        id="prc{{ $itemIdentifier }}">
                                        {{ App\Models\Product::convertPrice($product['price']) }}
                                        @if ($product['discount'] != 0)
                                            <strong>{{ $product['discount'] }} %{{ __('off') }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        <a class="cart-remove-btn"
                                            ata-class="cremove{{ $itemIdentifier }}"
                                            href="{{ route('product.cart.remove', $itemIdentifier) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none">
                                                <path
                                                    d="M9 3H15M3 6H21M19 6L18.2987 16.5193C18.1935 18.0975 18.1409 18.8867 17.8 19.485C17.4999 20.0118 17.0472 20.4353 16.5017 20.6997C15.882 21 15.0911 21 13.5093 21H10.4907C8.90891 21 8.11803 21 7.49834 20.6997C6.95276 20.4353 6.50009 20.0118 6.19998 19.485C5.85911 18.8867 5.8065 18.0975 5.70129 16.5193L5 6M10 10.5V15.5M14 10.5V15.5"
                                                    stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>

                            {{-- زر المتابعة للشراء لهذا التاجر --}}
                            <div class="vendor-checkout-btn p-3 text-end" style="background-color: #f8f9fa; border-top: 1px solid #e0e0e0;">
                                <a href="{{ route('front.checkout.vendor', ['vendorId' => $vendorId]) }}"
                                   class="template-btn"
                                   style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.3s ease;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; margin-right: 6px; vertical-align: middle;">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                    @lang('Checkout') {{ $vendorData['vendor_name'] }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    {{-- Fallback: العرض القديم في حالة عدم وجود productsByVendor --}}
                    <div class="cart-table table-responsive">
                        <table class="table">
                            <thead>
                                <tr class="">
                                    <th scope="col">@lang('Product Name')</th>
                                    <th scope="col">@lang('Price')</th>
                                    <th scope="col">@lang('Quantity')</th>
                                    <th scope="col">@lang('Subtotal')</th>
                                    <th scope="col">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody class="t_body">
                                @foreach ($products as $product)
                                    @php
                                        // Safe conversion of potentially array variables to strings for item identification
                                        $sizeStr = is_array($product['size']) ? implode('-', $product['size']) : ($product['size'] ?? '');
                                        $colorStr = is_array($product['color']) ? implode('-', $product['color']) : ($product['color'] ?? '');
                                        $valuesStr = is_array($product['values']) ? implode('-', $product['values']) : ($product['values'] ?? '');
                                        $valuesStr = str_replace([' ', ','], '', $valuesStr);

                                        // Create unique item identifier
                                        $itemIdentifier = $product['item']['id'] . $sizeStr . $colorStr . $valuesStr;

                                        if ($product['discount'] != 0) {
                                            $total_itemprice = $product['item_price'] * $product['qty'];
                                            $tdiscount = ($total_itemprice * $product['discount']) / 100;
                                            $discount += $tdiscount;
                                        }

                                    @endphp


                                    <tr class="">
                                        <td class="cart-product-area">
                                            <div class="cart-product d-flex">
                                                <img src="{{ $product['item']['photo'] ? \Illuminate\Support\Facades\Storage::url($product['item']['photo']) : asset('assets/images/noimage.png') }}"
                                                    alt="">
                                                <div class="cart-product-info">
                                                    @php
                                                        $cartProductUrl = '#';
                                                        if (isset($product['item']['slug']) && isset($product['user_id']) && isset($product['merchant_product_id'])) {
                                                            $cartProductUrl = route('front.product', [
                                                                'slug' => $product['item']['slug'],
                                                                'vendor_id' => $product['user_id'],
                                                                'merchant_product_id' => $product['merchant_product_id']
                                                            ]);
                                                        } elseif (isset($product['item']['slug'])) {
                                                            $cartProductUrl = route('front.product.legacy', $product['item']['slug']);
                                                        }
                                                    @endphp

                                                    <a class="cart-title d-inline-block"
                                                        href="{{ $cartProductUrl }}">{{ mb_strlen($product['item']['name'], 'UTF-8') > 35
                                                            ? mb_substr($product['item']['name'], 0, 35, 'UTF-8') . '...'
                                                            : $product['item']['name'] }}</a>

                                                    <div class="d-flex align-items-center gap-2">
                                                        @if (!empty($product['color']))
                                                            @lang('Color') : <p
                                                                class="cart-color d-inline-block rounded-2"
                                                                style="border: 10px solid #{{ $product['color'] == '' ? 'white' : $product['color'] }};">
                                                            </p>
                                                        @endif
                                                        @if (!empty($product['size']))
                                                            @lang('Size') : <p class="d-inline-block">
                                                                {{ $product['size'] }}
                                                            </p>
                                                        @endif
                                                    </div>

                                                </div>
                                            </div>
                                        </td>
                                        <td class="cart-price">
                                            {{ App\Models\Product::convertPrice($product['item_price']) }}</td>

                                        @if ($product['item']['type'] == 'Physical')
                                            <td>
                                                <div class="cart-quantity">
                                                    <button class="cart-quantity-btn quantity-down">-</button>
                                                    <input type="text"
                                                        id="qty{{ $itemIdentifier }}"
                                                        value="{{ $product['qty'] }}" class="borderless" readonly>
                                                    <input type="hidden" class="prodid"
                                                        value="{{ $product['item']['id'] }}">
                                                    <input type="hidden" class="itemid"
                                                        value="{{ $itemIdentifier }}">
                                                    <input type="hidden" class="size_qty"
                                                        value="{{ $product['size_qty'] }}">
                                                    <input type="hidden" class="size_price"
                                                        value="{{ $product['size_price'] }}">
                                                    <input type="hidden" class="minimum_qty"
                                                        value="{{ $product['item']['minimum_qty'] == null ? '0' : $product['item']['minimum_qty'] }}">

                                                    <button class="cart-quantity-btn quantity-up">+</button>
                                                </div>
                                            </td>
                                        @else
                                            <td class="product-quantity">
                                                1
                                            </td>
                                        @endif


                                        @if ($product['size_qty'])
                                            <input type="hidden"
                                                id="stock{{ $itemIdentifier }}"
                                                value="{{ $product['size_qty'] }}">
                                        @elseif($product['item']['type'] != 'Physical')
                                            <input type="hidden"
                                                id="stock{{ $itemIdentifier }}"
                                                value="1">
                                        @else
                                            <input type="hidden"
                                                id="stock{{ $itemIdentifier }}"
                                                value="{{ $product['stock'] }}">
                                        @endif



                                        <td class="cart-price"
                                            id="prc{{ $itemIdentifier }}">
                                            {{ App\Models\Product::convertPrice($product['price']) }}
                                            @if ($product['discount'] != 0)
                                                <strong>{{ $product['discount'] }} %{{ __('off') }}</strong>
                                            @endif
                                        </td>
                                        <td>
                                            <a class="cart-remove-btn"
                                                ata-class="cremove{{ $itemIdentifier }}"
                                                href="{{ route('product.cart.remove', $itemIdentifier) }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M9 3H15M3 6H21M19 6L18.2987 16.5193C18.1935 18.0975 18.1409 18.8867 17.8 19.485C17.4999 20.0118 17.0472 20.4353 16.5017 20.6997C15.882 21 15.0911 21 13.5093 21H10.4907C8.90891 21 8.11803 21 7.49834 20.6997C6.95276 20.4353 6.50009 20.0118 6.19998 19.485C5.85911 18.8867 5.8065 18.0975 5.70129 16.5193L5 6M10 10.5V15.5M14 10.5V15.5"
                                                        stroke="#1F0300" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h4 class="cart-summary-title">@lang('Cart Summary')</h4>
                    <div class="cart-summary-content">
                        <div class="cart-summary-item d-flex justify-content-between">
                            <p class="cart-summary-subtitle">@lang('Subtotal')({{ count(Session::get('cart')->items) }}
                                @lang('Items'))</p>
                            <p class="cart-summary-price">
                                {{ Session::has('cart') ? App\Models\Product::convertPrice($totalPrice + $discount) : '0.00' }}
                            </p>
                        </div>
                        <div class="cart-summary-item d-flex justify-content-between">
                            <p class="cart-summary-subtitle">@lang('Discount')</p>
                            <p class="cart-summary-price">{{ App\Models\Product::convertPrice($discount) }}</p>
                        </div>
                        <div class="cart-summary-item d-flex justify-content-between">
                            <p class="cart-summary-subtitle">@lang('Total')</p>
                            <p class="cart-summary-price total-cart-price">
                                {{ Session::has('cart') ? App\Models\Product::convertPrice($mainTotal) : '0.00' }}
                            </p>
                        </div>

                        @if(isset($productsByVendor) && count($productsByVendor) > 1)
                            {{-- رسالة توضيحية للعميل عند وجود منتجات من عدة تجار --}}
                            <div class="mt-3 p-3" style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-left: 4px solid #667eea; border-radius: 6px;">
                                <div style="display: flex; align-items: start; gap: 12px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#667eea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    <div>
                                        <strong style="color: #667eea; font-size: 0.95em; display: block; margin-bottom: 4px;">
                                            @lang('Multiple Vendors Detected')
                                        </strong>
                                        <p style="margin: 0; font-size: 0.85em; color: #555; line-height: 1.5;">
                                            @lang('You have items from') {{ count($productsByVendor) }} @lang('different vendors. Please complete checkout with each vendor separately. After completing payment, you will be returned to this page to checkout with the next vendor.')
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                <div class="card border py-4">
                    <div class="card-body">
                        <h4 class="text-center">{{ __('Cart is Empty!! Add some products in your Cart') }}</h4>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
