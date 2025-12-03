<?php

namespace App\Livewire;

use App\Models\Cart;
use App\Services\TryotoService;
use App\Services\VendorCartService;
use App\Services\ShippingCalculatorService;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class TryotoComponet extends Component
{
    /** منتجات هذا البائع داخل المودال */
    public array $products = [];

    /** خيارات شركات الشحن من Tryoto */
    public array $deliveryCompany = [];

    /** رقم البائع لربط الإشارة وتحديث نص الشحن أعلى المودال */
    public int $vendorId = 0;

    /** هل حدث خطأ في Tryoto API */
    public bool $hasError = false;

    /** رسالة الخطأ للعرض */
    public string $errorMessage = '';

    /** الوزن الإجمالي */
    protected $weight = 100;

    /** Cached general settings */
    protected $generalSettings;

    /** TryotoService instance */
    protected TryotoService $tryotoService;

    public function mount(array $products, int $vendorId = 0)
    {
        $this->products = $products;
        $this->vendorId = $vendorId;

        // Initialize TryotoService
        $this->tryotoService = app(TryotoService::class);

        // Cache general settings to avoid repeated DB calls
        $this->generalSettings = cache()->remember('generalsettings', now()->addMinutes(30), function () {
            return \DB::table('generalsettings')->first();
        });

        $this->getWeight();
        $this->checkOTODeliveryFee();
    }

    public function render()
    {
        return view('livewire.tryoto-componet');
    }

    /**
     * حساب الوزن الإجمالي والأبعاد باستخدام VendorCartService
     * ملاحظة: هذه الدالة للعرض فقط - البيانات الفعلية للشحن من checkOTODeliveryFee
     */
    public function getWeight(): void
    {
        // استخدام VendorCartService بدلاً من PriceHelper لتجنب القيم الافتراضية
        $shippingData = VendorCartService::calculateVendorShipping($this->vendorId, $this->products);

        $this->weight = $shippingData['chargeable_weight'] ?? $shippingData['actual_weight'] ?? 0;

        // Update products array with calculated weights for display
        foreach ($this->products as $index => $product) {
            $itemWeight = $product['item']['weight'] ?? 0;
            $product['weight_total'] = $product['qty'] * $itemWeight;
            $this->products[$index] = $product;
        }
    }

    /**
     * يُستدعى عند تغيير الراديو في جدول Tryoto
     * value = "deliveryOptionId#CompanyName#price"
     */
    public function selectedOption(string $value): void
    {
        // نبث حدثًا للواجهة لتحدّث نص "الشحن:" والسعر
        $this->dispatch('shipping-updated', vendorId: $this->vendorId);
    }

    /**
     * جلب خيارات الشحن من Tryoto باستخدام VendorCartService الموحد
     *
     * المبادئ:
     * - الوزن والأبعاد من VendorCartService (بدون fallback)
     * - مدينة التاجر من ShippingCalculatorService (user.city_id فقط)
     * - مدينة العميل من الـ session فقط
     */
    protected function checkOTODeliveryFee(): void
    {
        try {
            // === 1. حساب بيانات الشحن باستخدام VendorCartService ===
            $shippingData = VendorCartService::calculateVendorShipping($this->vendorId, $this->products);

            \Log::info('TryotoComponent: Shipping data calculated', [
                'vendor_id' => $this->vendorId,
                'shipping_data' => $shippingData
            ]);

            // === 2. التحقق من اكتمال البيانات ===
            if (!$shippingData['has_complete_data']) {
                $missingFields = $shippingData['missing_data'] ?? [];
                \Log::warning('TryotoComponent: Incomplete shipping data', [
                    'vendor_id' => $this->vendorId,
                    'missing' => $missingFields
                ]);

                $this->hasError = true;
                $this->errorMessage = 'بيانات الشحن غير مكتملة: ' . implode(', ', $missingFields);
                $this->deliveryCompany = [];
                return;
            }

            // === 3. جلب مدينة التاجر باستخدام ShippingCalculatorService ===
            $vendorCityData = ShippingCalculatorService::getVendorCity($this->vendorId);

            if (!$vendorCityData || empty($vendorCityData['city_name'])) {
                \Log::error('TryotoComponent: Vendor city not configured', [
                    'vendor_id' => $this->vendorId
                ]);

                $this->hasError = true;
                $this->errorMessage = 'مدينة التاجر غير محددة. يرجى التواصل مع البائع.';
                $this->deliveryCompany = [];
                return;
            }

            $originCity = $this->normalizeCityName($vendorCityData['city_name']);

            // === 4. جلب مدينة العميل من الـ session ===
            $destinationCity = $this->getDestinationCity();

            // === 5. استخدام الوزن القابل للشحن (chargeable_weight) ===
            $weight = $shippingData['chargeable_weight'] ?? $shippingData['actual_weight'] ?? 0;
            $dimensions = $shippingData['dimensions'] ?? [];

            \Log::info('TryotoComponent: Calling Tryoto API', [
                'vendor_id' => $this->vendorId,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'weight' => $weight,
                'dimensions' => $dimensions
            ]);

            // === 6. استدعاء Tryoto API ===
            $result = $this->tryotoService->getDeliveryOptions(
                $originCity,
                $destinationCity,
                $weight,
                0, // COD amount not used in this endpoint
                $dimensions
            );

            if (!$result['success']) {
                \Log::error('TryotoComponent: Failed to get delivery options', [
                    'error' => $result['error'],
                    'error_code' => $result['error_code'] ?? null,
                    'origin' => $originCity,
                    'destination' => $destinationCity,
                    'weight' => $weight,
                    'dimensions' => $dimensions
                ]);

                $this->hasError = true;
                $this->errorMessage = $this->translateTryotoError($result['error'] ?? 'Unknown error');
                $this->deliveryCompany = [];
                return;
            }

            // Transform options back to deliveryCompany format for the view
            $this->deliveryCompany = $result['raw']['deliveryCompany'] ?? [];
            $this->hasError = false;
            $this->errorMessage = '';

            \Log::info('TryotoComponent: Got delivery options', [
                'vendor_id' => $this->vendorId,
                'options_count' => count($this->deliveryCompany)
            ]);

            // إعلان مبدئي لتحديث نص الشحن الافتراضي (أول خيار)
            $this->dispatch('shipping-updated', vendorId: $this->vendorId);

        } catch (\Exception $e) {
            \Log::error('TryotoComponent: Exception in checkOTODeliveryFee', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vendor_id' => $this->vendorId
            ]);

            $this->hasError = true;
            $this->errorMessage = 'عذراً، خدمة الشحن الذكي غير متاحة حالياً. يرجى اختيار طريقة شحن أخرى.';
            $this->deliveryCompany = [];
        }
    }

    /**
     * ترجمة أخطاء Tryoto لرسائل عربية واضحة
     */
    protected function translateTryotoError(string $error): string
    {
        if (str_contains($error, 'could not be found on database')) {
            // استخراج اسم المدينة من الخطأ
            preg_match('/Given city (.+) could not be found/', $error, $matches);
            $cityName = $matches[1] ?? '';

            return "عذراً، مدينة المرسل ({$cityName}) غير مدعومة حالياً في خدمة الشحن الذكي. يرجى التواصل مع البائع أو اختيار طريقة شحن أخرى.";
        }

        if (str_contains($error, 'destination')) {
            return 'عذراً، مدينة التوصيل غير مدعومة في خدمة الشحن الذكي.';
        }

        return 'عذراً، خدمة الشحن الذكي غير متاحة حالياً. يرجى اختيار طريقة شحن أخرى.';
    }

    /**
     * Get destination city from customer data in session ONLY
     */
    protected function getDestinationCity(): string
    {
        // Vendor checkout only — لا يوجد checkout عادي في هذا الفرع
        $sessionKey = 'vendor_step1_' . $this->vendorId;

        // التحقق من وجود بيانات step1 الخاصة بالمنتج لدى هذا البائع
        if (!Session::has($sessionKey)) {
            \Log::error('TryotoComponent: Vendor step1 session missing', [
                'vendor_id' => $this->vendorId,
                'session_keys'=> array_keys(Session::all())
            ]);
            throw new \Exception('Customer destination city is required for shipping calculation. Please complete Step 1 and select a city.');
        }

        $step1 = Session::get($sessionKey);

        // التحقق من وجود customer_city
        if (empty($step1['customer_city']) || !is_numeric($step1['customer_city'])) {
            \Log::error('TryotoComponent: Invalid or missing customer_city', [
                'vendor_id' => $this->vendorId,
                'customer_city' => $step1['customer_city'] ?? null
            ]);
            throw new \Exception('Customer destination city is required for shipping calculation. Please select a city.');
        }

        $cityId = $step1['customer_city'];

        // التحقق من أن المدينة موجودة فعلياً في قاعدة البيانات
        $city = \App\Models\City::find($cityId);

        if (!$city || empty($city->city_name)) {
            \Log::error('TryotoComponent: City not found in DB', [
                'vendor_id' => $this->vendorId,
                'city_id' => $cityId,
            ]);
            throw new \Exception("Destination city not found. (ID: {$cityId})");
        }

        \Log::info('TryotoComponent: Destination city resolved', [
            'vendor_id' => $this->vendorId,
            'city_id' => $cityId,
            'city_name' => $city->city_name,
            'session_key' => $sessionKey,
        ]);

        return $this->normalizeCityName($city->city_name);
    }

    protected function normalizeCityName(string $cityName): string
    {
        // Characters to remove/replace
        $charsToReplace = ['ā', 'ī', 'ū', 'ē', 'ō', 'Ā', 'Ī', 'Ū', 'Ē', 'Ō'];
        $replacements = ['a', 'i', 'u', 'e', 'o', 'A', 'I', 'U', 'E', 'O'];

        // Remove diacritics and special characters
        $normalized = str_replace($charsToReplace, $replacements, $cityName);

        // Remove apostrophes
        $normalized = str_replace("'", '', $normalized);

        // Trim whitespace
        $normalized = trim($normalized);

        // Log the normalization
        if ($normalized !== $cityName) {
            \Log::info('TryotoComponent: City name normalized', [
                'original' => $cityName,
                'normalized' => $normalized,
            ]);
        }

        return $normalized;
    }
}
