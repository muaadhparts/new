{{--
    ====================================================================
    MULTI-VENDOR CART SYSTEM
    ====================================================================

    This view implements a per-vendor cart display system where:

    1. Each vendor has their own independent cart section
    2. Each vendor section contains:
       - Vendor header with vendor name/ID
       - Products table (only this vendor's products)
       - Independent Cart Summary (subtotal, discount, total)
       - Dedicated "Checkout This Vendor" button

    3. NO GLOBAL SUMMARY exists - all calculations are per-vendor
    4. Each checkout processes ONLY one vendor at a time
    5. After order completion, only that vendor's products are removed

    Key Variables:
    - $productsByVendor: Array grouped by vendor_id
    - $vendorData: Contains vendor_id, vendor_name, products, total, count

    Flow:
    Cart Page → Checkout Vendor {id} → Step1 → Step2 → Step3 → Order Creation

    Modified: 2025-01-XX for Multi-Vendor Checkout System
    ====================================================================
--}}

<div class="container gs-cart-container">
    <div class="row gs-cart-row">

        @if (Session::has('cart'))
            <div class="col-lg-12">
                {{-- Loop through each vendor section independently --}}
                @foreach($productsByVendor as $vendorId => $vendorData)
                <div class="vendor-cart-section mb-5" style="background: #ffffff; border-radius: 20px; box-shadow: 0 8px 24px rgba(13, 148, 136, 0.1); border: 2px solid #e0f2fe; overflow: hidden;">
                    {{-- Vendor Header --}}
                    <div class="vendor-header" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); padding: 1.5rem; color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1" style="font-weight: 800; letter-spacing: 0.5px;">
                                    <i class="fas fa-store me-2"></i>{{ $vendorData['vendor_name'] }}
                                </h4>
                                <p class="mb-0" style="opacity: 0.9; font-size: 0.95rem;">
                                    <i class="fas fa-box me-1"></i>{{ $vendorData['count'] }} @lang('Items')
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-0">
                        {{-- Products Table --}}
                        <div class="col-lg-8">
                            <div class="cart-table table-responsive" style="padding: 2rem;">
                                <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">@lang('Image')</th>
                            <th scope="col">@lang('Name')</th>
                            <th scope="col">@lang('Part Number')</th>
                            <th scope="col">@lang('Brand')</th>
                            <th scope="col">@lang('Brand Quality')</th>
                            <th scope="col">@lang('Price')</th>
                            <th scope="col">@lang('Quantity')</th>
                            <th scope="col">@lang('Subtotal')</th>
                            <th scope="col">@lang('Action')</th>
                        </tr>
                        </thead>

                        <tbody class="t_body">
                        @foreach ($vendorData['products'] as $rowKey => $product)
                            @php
                                // معلومات أساسية
                                $currentVendorId = data_get($product, 'item.user_id') ?? 0;
                                $slug     = data_get($product, 'item.slug');
                                $name     = data_get($product, 'item.name');
                                $sku      = data_get($product, 'item.sku');
                                $photo    = data_get($product, 'item.photo');

                                // المفتاح الحقيقي للسلة كما هو (Vendor-aware)
                                $row    = (string) $rowKey;
                                // نسخة آمنة للـ DOM
                                $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', $row);

                                // Fetch merchant data using helper method (avoids code duplication)
                                $itemProduct = \App\Models\Product::where('slug', $slug)->first();
                                $itemMerchant = $itemProduct ? $itemProduct->getMerchantProduct($currentVendorId) : null;
                                $itemMerchantId = $itemMerchant->id ?? null;

                                // رابط تفاصيل المنتج مع تمرير {vendor_id, merchant_product_id}
                                $productUrl = ($currentVendorId && $itemMerchantId) ? route('front.product', ['slug' => $slug, 'vendor_id' => $currentVendorId, 'merchant_product_id' => $itemMerchantId]) : 'javascript:;';
                            @endphp

                            <tr>
                                {{-- Image Column --}}
                                <td class="cart-image">
                                    <img src="{{ $photo ? \Illuminate\Support\Facades\Storage::url($photo) : asset('assets/images/noimage.png') }}" alt="" style="width: 80px; height: 80px; object-fit: cover;">
                                </td>

                                {{-- Name Column --}}
                                <td class="cart-name">
                                    <x-product-name :item="$product['item']" :vendor-id="$currentVendorId" :merchant-product-id="$itemMerchantId" :showSku="false" target="_blank" />
                                    @if (!empty($product['color']) || !empty($product['size']))
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            @if (!empty($product['color']))
                                                <span class="text-muted small">@lang('Color'): </span>
                                                <span class="cart-color d-inline-block rounded-2" style="border:10px solid #{{ $product['color']==''?'white':$product['color'] }};"></span>
                                            @endif
                                            @if (!empty($product['size']))
                                                <span class="text-muted small">@lang('Size'): {{ $product['size'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Part Number (SKU) Column --}}
                                <td class="cart-sku">
                                    <span>{{ $sku ?? '-' }}</span>
                                </td>

                                {{-- Brand Column --}}
                                <td class="cart-brand">
                                    <span>{{ $itemProduct && $itemProduct->brand ? Str::ucfirst($itemProduct->brand->name) : '-' }}</span>
                                </td>

                                {{-- Brand Quality Column --}}
                                <td class="cart-quality">
                                    <span>{{ $itemMerchant && $itemMerchant->qualityBrand ? (app()->getLocale() == 'ar' && $itemMerchant->qualityBrand->name_ar ? $itemMerchant->qualityBrand->name_ar : $itemMerchant->qualityBrand->name_en) : '-' }}</span>
                                </td>

                                {{-- Price Column --}}
                                <td class="cart-price">
                                    {{ App\Models\Product::convertPrice($product['item_price']) }}
                                </td>

                                @if (data_get($product,'item.type') == 'Physical')
                                    <td>
                                        <div class="cart-quantity">
                                            <button class="cart-quantity-btn quantity-down">-</button>

                                            <input type="text" id="qty{{ $domKey }}" value="{{ $product['qty'] }}"
                                                   class="borderless" readonly>

                                            {{-- مفاتيح الطلب --}}
                                            <input type="hidden" class="prodid"   value="{{ data_get($product,'item.id') }}">
                                            <input type="hidden" class="itemid"   value="{{ $row }}">     {{-- يُرسل للخادم --}}
                                            <input type="hidden" class="domkey"   value="{{ $domKey }}">  {{-- لاختيار عناصر DOM --}}
                                            <input type="hidden" class="size_qty" value="{{ $product['size_qty'] }}">
                                            <input type="hidden" class="size_price" value="{{ $product['size_price'] }}">
                                            <input type="hidden" class="minimum_qty"
                                                   value="{{ data_get($product,'item.minimum_qty') === null ? '0' : data_get($product,'item.minimum_qty') }}">

                                            <button class="cart-quantity-btn quantity-up">+</button>
                                        </div>
                                    </td>
                                @else
                                    <td class="product-quantity">1</td>
                                @endif

                                {{-- مخزون الصف --}}
                                @if (!empty($product['size_qty']))
                                    <input type="hidden" id="stock{{ $domKey }}" value="{{ $product['size_qty'] }}">
                                @elseif (data_get($product,'item.type') != 'Physical')
                                    <input type="hidden" id="stock{{ $domKey }}" value="1">
                                @else
                                    <input type="hidden" id="stock{{ $domKey }}" value="{{ $product['stock'] }}">
                                @endif

                                <td class="cart-price" id="prc{{ $domKey }}">
                                    {{ App\Models\Product::convertPrice($product['price']) }}
                                    @if (!empty($product['discount']))
                                        <strong>{{ $product['discount'] }} %{{ __('off') }}</strong>
                                    @endif
                                </td>

                                <td>
                                    <a class="cart-remove-btn" data-class="cremove{{ $domKey }}"
                                       href="{{ route('product.cart.remove', $row) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none">
                                            <path d="M9 3H15M3 6H21M19 6L18.2987 16.5193C18.1935 18.0975 18.1409 18.8867 17.8 19.485C17.4999 20.0118 17.0472 20.4353 16.5017 20.6997C15.882 21 15.0911 21 13.5093 21H10.4907C8.90891 21 8.11803 21 7.49834 20.6997C6.95276 20.4353 6.50009 20.0118 6.19998 19.485C5.85911 18.8867 5.8065 18.0975 5.70129 16.5193L5 6M10 10.5V15.5M14 10.5V15.5"
                                                  stroke="#1F0300" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                            </div>
                        </div>

                        {{-- Vendor Cart Summary --}}
                        <div class="col-lg-4">
                            <div class="cart-summary" style="margin: 2rem; background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%); border-radius: 16px; padding: 2rem; border: 2px solid #14b8a6;">
                                <h5 class="cart-summary-title" style="color: #0f172a; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 3px solid; border-image: linear-gradient(90deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%) 1; background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                                    @lang('Cart Summary')
                                </h5>
                                <div class="cart-summary-content">
                                    @php
                                        // Calculate discount for THIS vendor only (not global)
                                        // Each vendor has independent discount calculations
                                        $vendorDiscount = 0;
                                        foreach ($vendorData['products'] as $product) {
                                            if (!empty($product['discount'])) {
                                                $total_itemprice = (float)($product['item_price'] ?? 0) * (int)($product['qty'] ?? 1);
                                                $tdiscount = ($total_itemprice * (float)$product['discount']) / 100;
                                                $vendorDiscount += $tdiscount;
                                            }
                                        }
                                        $vendorSubtotal = $vendorData['total'] + $vendorDiscount;
                                    @endphp

                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 1px solid #e0f2fe;">
                                        <p class="cart-summary-subtitle" style="color: #64748b; font-weight: 600; margin: 0;">
                                            @lang('Subtotal') ({{ $vendorData['count'] }} @lang('Items'))
                                        </p>
                                        <p class="cart-summary-price" style="color: #0d9488; font-weight: 700; font-size: 1.1rem; margin: 0;">
                                            {{ App\Models\Product::convertPrice($vendorSubtotal) }}
                                        </p>
                                    </div>

                                    @if($vendorDiscount > 0)
                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 1px solid #e0f2fe;">
                                        <p class="cart-summary-subtitle" style="color: #64748b; font-weight: 600; margin: 0;">
                                            @lang('Discount')
                                        </p>
                                        <p class="cart-summary-price" style="color: #ef4444; font-weight: 700; font-size: 1.1rem; margin: 0;">
                                            - {{ App\Models\Product::convertPrice($vendorDiscount) }}
                                        </p>
                                    </div>
                                    @endif

                                    <div class="cart-summary-item d-flex justify-content-between" style="padding: 1rem 0; border-bottom: 2px solid #14b8a6;">
                                        <p class="cart-summary-subtitle" style="color: #0f172a; font-weight: 700; margin: 0; font-size: 1.1rem;">
                                            @lang('Total')
                                        </p>
                                        <p class="cart-summary-price total-cart-price" style="color: #0d9488; font-weight: 800; font-size: 1.3rem; margin: 0;">
                                            {{ App\Models\Product::convertPrice($vendorData['total']) }}
                                        </p>
                                    </div>

                                    <div class="cart-summary-btn" style="margin-top: 1.5rem;">
                                        @auth
                                            <a href="{{ route('front.checkout.vendor', $vendorId) }}" class="template-btn w-100" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: #ffffff; border: none; padding: 1rem 2rem; border-radius: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3); text-align: center; display: block; text-decoration: none;">
                                                <i class="fas fa-shopping-cart me-2"></i>@lang('Checkout This Vendor')
                                            </a>
                                        @else
                                            <a href="{{ route('user.login', ['redirect' => 'cart']) }}" class="template-btn w-100" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: #ffffff; border: none; padding: 1rem 2rem; border-radius: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3); text-align: center; display: block; text-decoration: none;">
                                                <i class="fas fa-shopping-cart me-2"></i>@lang('Checkout This Vendor')
                                            </a>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
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
