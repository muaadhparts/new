<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShipmentStatusLog;
use App\Models\User;
use App\Models\City;
use App\Models\UserNotification;
use App\Helpers\PriceHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * TryotoService - Enterprise-Level Shipping Integration
 *
 * خدمة مركزية للتعامل مع Tryoto API
 * تتضمن:
 * - إنشاء الشحنات
 * - تتبع الشحنات
 * - إلغاء الشحنات
 * - جلب أسعار الشحن
 * - إدارة التوكن
 */
class TryotoService
{
    private $baseUrl;
    private $isSandbox;
    private $token;

    public function __construct()
    {
        $this->isSandbox = config('services.tryoto.sandbox', false);
        $this->baseUrl = $this->isSandbox
            ? config('services.tryoto.test.url', 'https://staging-api.tryoto.com')
            : config('services.tryoto.live.url', 'https://api.tryoto.com');
    }

    /**
     * Get or refresh access token
     */
    public function getToken(): ?string
    {
        $cacheKey = 'tryoto-token-' . ($this->isSandbox ? 'sandbox' : 'live');

        $token = Cache::get($cacheKey);

        if ($token) {
            return $token;
        }

        return $this->refreshToken();
    }

    /**
     * Refresh the access token
     */
    public function refreshToken(): ?string
    {
        try {
            $refreshToken = $this->isSandbox
                ? config('services.tryoto.test.token')
                : config('services.tryoto.live.token');

            if (!$refreshToken) {
                Log::error('Tryoto: Missing refresh token');
                return null;
            }

            $response = Http::post($this->baseUrl . '/rest/v2/refreshToken', [
                'refresh_token' => $refreshToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                $expiresIn = (int)($data['expires_in'] ?? 3600);

                if ($token) {
                    $cacheKey = 'tryoto-token-' . ($this->isSandbox ? 'sandbox' : 'live');
                    Cache::put($cacheKey, $token, now()->addSeconds(max(300, $expiresIn - 60)));
                    return $token;
                }
            }

            Log::error('Tryoto: Token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Tryoto: Token refresh exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get delivery options for a route using checkOTODeliveryFee endpoint
     *
     * @param string $originCity المدينة المصدر
     * @param string $destinationCity المدينة الوجهة
     * @param float $weight الوزن بالكيلو
     * @param float $codAmount مبلغ الدفع عند الاستلام (غير مستخدم في هذا الـ endpoint)
     * @param array $dimensions الأبعاد [length, height, width]
     * @return array
     */
    public function getDeliveryOptions(string $originCity, string $destinationCity, float $weight = 1, float $codAmount = 0, array $dimensions = []): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            // Use checkOTODeliveryFee endpoint (the correct one)
            $requestData = [
                'originCity' => $originCity,
                'destinationCity' => $destinationCity,
                'weight' => max(0.1, $weight),
                'xlength' => max(30, $dimensions['length'] ?? 30),
                'xheight' => max(30, $dimensions['height'] ?? 30),
                'xwidth' => max(30, $dimensions['width'] ?? 30),
            ];

            Log::info('Tryoto: Requesting delivery options', $requestData);

            $response = Http::withToken($token)->post($this->baseUrl . '/rest/v2/checkOTODeliveryFee', $requestData);

            if ($response->successful()) {
                $data = $response->json();
                $companies = $data['deliveryCompany'] ?? [];

                // Transform to unified format
                $options = [];
                foreach ($companies as $company) {
                    $options[] = [
                        'deliveryOptionId' => (string)($company['deliveryOptionId'] ?? ''),
                        'company' => $company['deliveryOptionName'] ?? $company['deliveryCompanyName'] ?? 'Unknown',
                        'companyCode' => $company['deliveryCompanyName'] ?? '',
                        'price' => (float)($company['price'] ?? 0),
                        'estimatedDeliveryDays' => $this->parseDeliveryTime($company['avgDeliveryTime'] ?? ''),
                        'avgDeliveryTime' => $company['avgDeliveryTime'] ?? '',
                        'serviceType' => $company['serviceType'] ?? '',
                        'deliveryType' => $company['deliveryType'] ?? '',
                        'logo' => $company['logo'] ?? '',
                        'codCharge' => (float)($company['codCharge'] ?? 0),
                        'returnFee' => (float)($company['returnFee'] ?? 0),
                        'maxCODValue' => (float)($company['maxCODValue'] ?? 0),
                        'maxOrderValue' => (float)($company['maxOrderValue'] ?? 0),
                    ];
                }

                Log::info('Tryoto: Got ' . count($options) . ' delivery options');

                return [
                    'success' => true,
                    'options' => $options,
                    'raw' => $data
                ];
            }

            Log::warning('Tryoto: checkOTODeliveryFee failed', [
                'origin' => $originCity,
                'destination' => $destinationCity,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['success' => false, 'error' => 'Failed to get delivery options. Status: ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('Tryoto: getDeliveryOptions exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse delivery time string to number of days
     */
    private function parseDeliveryTime(string $time): string
    {
        // Examples: "1to3WorkingDays", "SameDay", "NextDay"
        if (empty($time)) return '';

        if (stripos($time, 'same') !== false) return '0';
        if (stripos($time, 'next') !== false) return '1';

        // Extract numbers like "1to3WorkingDays" -> "1-3"
        if (preg_match('/(\d+)to(\d+)/i', $time, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }

        if (preg_match('/(\d+)/', $time, $matches)) {
            return $matches[1];
        }

        return $time;
    }

    /**
     * تحويل city ID أو اسم إلى اسم المدينة الصحيح لـ Tryoto
     */
    public function resolveCityName($cityValue): string
    {
        if (empty($cityValue)) {
            return 'Riyadh';
        }

        // إذا كان رقمياً، ابحث عن المدينة
        if (is_numeric($cityValue)) {
            $city = City::find($cityValue);
            if ($city && $city->city_name) {
                return $city->city_name;
            }
        }

        // إذا كان نصاً غير رقمي، استخدمه مباشرة
        if (!is_numeric($cityValue)) {
            return $cityValue;
        }

        return 'Riyadh';
    }

    /**
     * Create a shipment using createOrder API with createShipment=true
     *
     * @param Order $order
     * @param int $vendorId
     * @param string $deliveryOptionId
     * @param string $company
     * @param float $price
     * @param string $serviceType
     * @return array
     */
    public function createShipment(Order $order, int $vendorId, string $deliveryOptionId, string $company, float $price, string $serviceType = ''): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            // Get vendor info
            $vendor = User::find($vendorId);

            // ✅ تحويل city_id إلى city name
            $originCity = $this->resolveCityName($vendor->city_id ?? $vendor->warehouse_city ?? $vendor->shop_city);
            $originAddress = $vendor->warehouse_address ?? $vendor->shop_address ?? $originCity;

            // ✅ تحويل destination city ID إلى city name
            $destinationCityValue = $order->shipping_city ?: $order->customer_city;
            $destinationCity = $this->resolveCityName($destinationCityValue);

            // Calculate dimensions from cart
            $dims = $this->calculateDimensions($order);

            // Determine COD amount
            $isCOD = in_array($order->method, ['cod', 'Cash On Delivery']);
            $codAmount = $isCOD ? (float)$order->pay_amount : 0.0;

            // Prepare receiver info with phone cleanup
            $receiverName = $order->shipping_name ?: $order->customer_name;
            $receiverPhone = $this->cleanPhoneNumber($order->shipping_phone ?: $order->customer_phone);
            $receiverEmail = $order->shipping_email ?: $order->customer_email ?: 'customer@example.com';
            $receiverAddress = $order->shipping_address ?: $order->customer_address;
            $receiverZip = $order->shipping_zip ?: $order->customer_zip ?: '00000';
            $receiverDistrict = $order->shipping_state ?? $order->customer_state ?? '';

            // Prepare sender phone
            $senderPhone = $this->cleanPhoneNumber($vendor->phone ?? '0500000000');

            // Prepare cart items
            $cart = is_string($order->cart) ? json_decode($order->cart, true) : $order->cart;
            $items = $cart['items'] ?? $cart ?? [];
            $orderItems = [];
            $itemCount = 0;

            foreach ($items as $item) {
                $itemData = $item['item'] ?? $item;
                $qty = (int)($item['qty'] ?? 1);
                $itemCount += $qty;
                $orderItems[] = [
                    'productId' => (string)($itemData['id'] ?? '0'),
                    'name' => $itemData['name'] ?? 'Product',
                    'price' => (float)($itemData['price'] ?? 0),
                    'rowTotal' => (float)($itemData['price'] ?? 0) * $qty,
                    'taxAmount' => 0,
                    'quantity' => $qty,
                    'serialnumber' => '',
                    'sku' => $itemData['sku'] ?? 'SKU-' . ($itemData['id'] ?? '0'),
                    'image' => $itemData['photo'] ?? '',
                ];
            }

            // Generate unique order ID for Tryoto (append timestamp to avoid duplicates)
            $tryotoOrderId = $order->order_number . '-V' . $vendorId . '-' . time();

            // Build createOrder payload
            $payload = [
                'orderId' => $tryotoOrderId,
                'ref1' => 'REF-' . $order->id . '-' . $vendorId,
                'deliveryOptionId' => $deliveryOptionId,
                'serviceType' => $serviceType,
                'createShipment' => true,
                'storeName' => $vendor->shop_name ?? $vendor->name ?? 'Store',
                'payment_method' => $isCOD ? 'cod' : 'paid',
                'amount' => (float)$order->pay_amount,
                'amount_due' => $isCOD ? (float)$order->pay_amount : 0,
                'shippingAmount' => $price,
                'subtotal' => (float)$order->pay_amount,
                'currency' => 'SAR',
                'shippingNotes' => 'Order #' . $order->order_number,
                'packageSize' => 'medium',
                'packageCount' => max(1, $itemCount),
                'packageWeight' => max(0.5, $dims['weight']),
                'boxWidth' => max(30, $dims['width']),
                'boxLength' => max(30, $dims['length']),
                'boxHeight' => max(30, $dims['height']),
                'orderDate' => date('d/m/Y H:i'),
                'deliverySlotDate' => date('d/m/Y', strtotime('+2 days')),
                'deliverySlotTo' => '6:00pm',
                'deliverySlotFrom' => '9:00am',
                'senderName' => $vendor->shop_name ?? $vendor->name,
                'senderPhone' => $senderPhone,
                'senderCity' => $originCity,
                'senderAddress' => $originAddress,
                'customer' => [
                    'name' => $receiverName,
                    'email' => $receiverEmail,
                    'mobile' => $receiverPhone,
                    'address' => $receiverAddress,
                    'district' => $receiverDistrict,
                    'city' => $destinationCity,
                    'country' => 'SA',
                    'postcode' => $receiverZip,
                    'lat' => '',
                    'lon' => '',
                    'refID' => '',
                    'W3WAddress' => ''
                ],
                'items' => $orderItems
            ];

            Log::info('Tryoto: Creating order with shipment', [
                'order_id' => $order->id,
                'tryoto_order_id' => $tryotoOrderId,
                'origin' => $originCity,
                'destination' => $destinationCity,
                'company' => $company,
            ]);

            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl . '/rest/v2/createOrder', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $otoId = $data['otoId'] ?? null;
                $trackingNumber = $data['trackingNumber'] ?? null;

                // If no tracking number yet, use otoId as reference
                $trackingRef = $trackingNumber ?? ('OTO-' . $otoId);

                // Save to shipment_status_logs
                $this->createInitialLog($order, $vendorId, $trackingRef, (string)$otoId, $company, $originCity, $data);

                // Send notification to vendor
                $this->notifyVendor($vendorId, $order, 'shipment_created', $trackingRef);

                Log::info('Tryoto: Order created successfully', [
                    'order_id' => $order->id,
                    'oto_id' => $otoId,
                    'tracking_number' => $trackingNumber,
                ]);

                return [
                    'success' => true,
                    'shipment_id' => (string)$otoId,
                    'tracking_number' => $trackingRef,
                    'oto_id' => $otoId,
                    'company' => $company,
                    'price' => $price,
                    'raw' => $data
                ];
            }

            $responseData = $response->json();

            Log::error('Tryoto: createOrder failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'error_code' => $responseData['errorCode'] ?? null,
                'error_message' => $responseData['errorMsg'] ?? $responseData['otoErrorMessage'] ?? null,
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $responseData['errorMsg'] ?? $responseData['otoErrorMessage'] ?? 'Failed to create shipment',
                'error_code' => $responseData['errorCode'] ?? null,
                'details' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Tryoto: createShipment exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clean and format phone number for Saudi Arabia
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Remove country code if present
        if (strpos($phone, '966') === 0) {
            $phone = substr($phone, 3);
        }

        // Ensure 9 digits starting with 5
        if (strlen($phone) < 9) {
            $phone = '5' . str_pad($phone, 8, '0', STR_PAD_LEFT);
        }

        return substr($phone, 0, 9);
    }

    /**
     * Track a shipment
     *
     * @param string $trackingNumber
     * @return array
     */
    public function trackShipment(string $trackingNumber, ?string $companyName = null): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            // If company name not provided, try to get it from database
            if (!$companyName) {
                $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)->first();
                $companyName = $shipment?->company_name;
            }

            // Build request payload
            $payload = ['trackingNumber' => $trackingNumber];
            if ($companyName) {
                $payload['deliveryCompanyName'] = $companyName;
            }

            $response = Http::withToken($token)->post($this->baseUrl . '/rest/v2/trackShipment', $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Update local log if there's new status
                $this->syncTrackingStatus($trackingNumber, $data);

                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'status_ar' => $this->getStatusArabic($data['status'] ?? 'unknown'),
                    'location' => $data['location'] ?? null,
                    'events' => $data['events'] ?? [],
                    'estimated_delivery' => $data['estimatedDelivery'] ?? null,
                    'raw' => $data
                ];
            }

            $errorData = $response->json();
            Log::warning('Tryoto: trackShipment failed', [
                'tracking' => $trackingNumber,
                'status' => $response->status(),
                'error' => $errorData['errorMsg'] ?? $response->body()
            ]);

            return ['success' => false, 'error' => $errorData['errorMsg'] ?? 'Failed to track shipment'];

        } catch (\Exception $e) {
            Log::error('Tryoto: trackShipment exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancel a shipment
     *
     * @param string $trackingNumber
     * @param string $reason
     * @return array
     */
    public function cancelShipment(string $trackingNumber, string $reason = ''): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            $response = Http::withToken($token)->post($this->baseUrl . '/rest/v2/cancelShipment', [
                'trackingNumber' => $trackingNumber,
                'reason' => $reason ?: 'Cancelled by merchant'
            ]);

            if ($response->successful()) {
                // Update local status
                $log = ShipmentStatusLog::where('tracking_number', $trackingNumber)->latest()->first();
                if ($log) {
                    ShipmentStatusLog::create([
                        'order_id' => $log->order_id,
                        'vendor_id' => $log->vendor_id,
                        'tracking_number' => $trackingNumber,
                        'shipment_id' => $log->shipment_id,
                        'company_name' => $log->company_name,
                        'status' => 'cancelled',
                        'status_ar' => 'ملغي',
                        'message' => 'Shipment cancelled: ' . $reason,
                        'message_ar' => 'تم إلغاء الشحنة: ' . $reason,
                        'status_date' => now(),
                    ]);

                    // Notify vendor
                    $this->notifyVendor($log->vendor_id, $log->order, 'shipment_cancelled', $trackingNumber);
                }

                return ['success' => true, 'message' => 'Shipment cancelled successfully'];
            }

            return ['success' => false, 'error' => 'Failed to cancel shipment', 'details' => $response->body()];

        } catch (\Exception $e) {
            Log::error('Tryoto: cancelShipment exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get order details from Tryoto (includes shipment status, tracking number, etc.)
     * Use this for manual refresh to get full order status
     *
     * @param string $orderId The orderId used when creating the order
     * @return array
     */
    public function getOrderDetails(string $orderId): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl . '/rest/v2/orderDetails', [
                    'orderId' => $orderId
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Tryoto: orderDetails success', [
                    'order_id' => $orderId,
                    'status' => $data['status'] ?? 'unknown',
                    'tracking' => $data['trackingNumber'] ?? null,
                ]);

                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'oto_id' => $data['otoId'] ?? null,
                    'tracking_number' => $data['trackingNumber'] ?? null,
                    'status' => $data['status'] ?? 'unknown',
                    'status_ar' => $this->getStatusArabic($data['status'] ?? 'unknown'),
                    'shipment_status' => $data['shipmentStatus'] ?? null,
                    'company_name' => $data['companyName'] ?? null,
                    'picked_up_date' => $data['pickedUpDate'] ?? null,
                    'delivered_date' => $data['deliveredDate'] ?? null,
                    'estimated_delivery' => $data['estimatedDelivery'] ?? null,
                    'awb_url' => $data['awbUrl'] ?? null, // رابط بوليصة الشحن
                    'pod_url' => $data['podUrl'] ?? null, // رابط صورة التسليم
                    'raw' => $data
                ];
            }

            $errorData = $response->json();
            Log::warning('Tryoto: orderDetails failed', [
                'order_id' => $orderId,
                'error' => $errorData['errorMsg'] ?? $response->body()
            ]);

            return [
                'success' => false,
                'error' => $errorData['errorMsg'] ?? 'Failed to get order details',
                'error_code' => $errorData['errorCode'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Tryoto: orderDetails exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Refresh shipment status using orderDetails API
     * This updates local database with latest status from Tryoto
     *
     * @param string $trackingNumber
     * @return array
     */
    public function refreshShipmentStatus(string $trackingNumber): array
    {
        // First find the local shipment record
        $shipment = ShipmentStatusLog::where('tracking_number', $trackingNumber)
            ->latest('created_at')
            ->first();

        if (!$shipment) {
            return ['success' => false, 'error' => 'Shipment not found in local database'];
        }

        // Try to get the orderId from raw_data
        $rawData = $shipment->raw_data;
        $orderId = null;

        if (is_array($rawData)) {
            $orderId = $rawData['orderId'] ?? $rawData['otoId'] ?? null;
        } elseif (is_string($rawData)) {
            $decoded = json_decode($rawData, true);
            $orderId = $decoded['orderId'] ?? $decoded['otoId'] ?? null;
        }

        // If no orderId, try trackShipment instead
        if (!$orderId) {
            return $this->trackShipment($trackingNumber);
        }

        // Get order details from Tryoto
        $details = $this->getOrderDetails($orderId);

        if (!$details['success']) {
            // Fallback to trackShipment
            return $this->trackShipment($trackingNumber);
        }

        // Update tracking number if we got a new one
        $newTrackingNumber = $details['tracking_number'];
        if ($newTrackingNumber && $newTrackingNumber !== $trackingNumber && !str_starts_with($newTrackingNumber, 'OTO-')) {
            // Create new log with real tracking number
            ShipmentStatusLog::create([
                'order_id' => $shipment->order_id,
                'vendor_id' => $shipment->vendor_id,
                'tracking_number' => $newTrackingNumber,
                'shipment_id' => $details['oto_id'],
                'company_name' => $details['company_name'] ?? $shipment->company_name,
                'status' => $details['status'],
                'status_ar' => $details['status_ar'],
                'message' => 'Tracking number assigned',
                'message_ar' => 'تم تعيين رقم التتبع: ' . $newTrackingNumber,
                'status_date' => now(),
                'raw_data' => $details['raw'],
            ]);

            // Notify vendor about tracking number
            $this->notifyVendor($shipment->vendor_id, $shipment->order, 'tracking_assigned', $newTrackingNumber);

            $details['tracking_updated'] = true;
            $details['old_tracking'] = $trackingNumber;
        }

        // Sync the status
        $this->syncTrackingStatus($newTrackingNumber ?? $trackingNumber, [
            'status' => $details['status'],
            'location' => null,
            'statusDate' => $details['picked_up_date'] ?? $details['delivered_date'] ?? now(),
        ]);

        return $details;
    }

    /**
     * Get all available cities
     *
     * @return array
     */
    public function getCities(): array
    {
        $cacheKey = 'tryoto-cities-' . ($this->isSandbox ? 'sandbox' : 'live');

        return Cache::remember($cacheKey, now()->addHours(24), function () {
            try {
                $token = $this->getToken();
                if (!$token) {
                    return [];
                }

                $response = Http::withToken($token)->get($this->baseUrl . '/rest/v2/getCities');

                if ($response->successful()) {
                    return $response->json()['cities'] ?? [];
                }

                return [];
            } catch (\Exception $e) {
                Log::error('Tryoto: getCities exception', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get shipment history for an order
     *
     * @param int $orderId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getShipmentHistory(int $orderId)
    {
        return ShipmentStatusLog::where('order_id', $orderId)
            ->orderBy('status_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get vendor shipments
     *
     * @param int $vendorId
     * @param string|null $status
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVendorShipments(int $vendorId, ?string $status = null, int $limit = 50)
    {
        $query = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->select('tracking_number', 'order_id', 'company_name', 'status', 'status_ar', 'status_date')
            ->groupBy('tracking_number')
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get shipping statistics for vendor
     *
     * @param int $vendorId
     * @return array
     */
    public function getVendorStatistics(int $vendorId): array
    {
        $stats = ShipmentStatusLog::where('vendor_id', $vendorId)
            ->selectRaw('
                COUNT(DISTINCT tracking_number) as total_shipments,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN status = "out_for_delivery" THEN 1 ELSE 0 END) as out_for_delivery,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "returned" THEN 1 ELSE 0 END) as returned
            ')
            ->first();

        return [
            'total' => $stats->total_shipments ?? 0,
            'delivered' => $stats->delivered ?? 0,
            'in_transit' => $stats->in_transit ?? 0,
            'out_for_delivery' => $stats->out_for_delivery ?? 0,
            'failed' => $stats->failed ?? 0,
            'returned' => $stats->returned ?? 0,
            'success_rate' => $stats->total_shipments > 0
                ? round(($stats->delivered / $stats->total_shipments) * 100, 1)
                : 0
        ];
    }

    /**
     * Get admin statistics
     *
     * @return array
     */
    public function getAdminStatistics(): array
    {
        $stats = ShipmentStatusLog::selectRaw('
                COUNT(DISTINCT tracking_number) as total_shipments,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "in_transit" THEN 1 ELSE 0 END) as in_transit,
                SUM(CASE WHEN status = "pending" OR status = "created" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "returned" THEN 1 ELSE 0 END) as returned
            ')
            ->first();

        // Get by company
        $byCompany = ShipmentStatusLog::selectRaw('company_name, COUNT(DISTINCT tracking_number) as count')
            ->groupBy('company_name')
            ->get()
            ->pluck('count', 'company_name')
            ->toArray();

        return [
            'total' => $stats->total_shipments ?? 0,
            'delivered' => $stats->delivered ?? 0,
            'in_transit' => $stats->in_transit ?? 0,
            'pending' => $stats->pending ?? 0,
            'failed' => $stats->failed ?? 0,
            'returned' => $stats->returned ?? 0,
            'by_company' => $byCompany,
            'success_rate' => $stats->total_shipments > 0
                ? round(($stats->delivered / $stats->total_shipments) * 100, 1)
                : 0
        ];
    }

    // ========================
    // Private Helper Methods
    // ========================

    /**
     * Calculate shipping dimensions from order cart
     */
    private function calculateDimensions(Order $order): array
    {
        $cartRaw = $order->cart;
        $cartArr = is_string($cartRaw) ? (json_decode($cartRaw, true) ?: []) : (is_array($cartRaw) ? $cartRaw : []);

        $items = $cartArr['items'] ?? $cartArr;
        $productsForDims = [];

        foreach ($items as $ci) {
            $qty = (int)($ci['qty'] ?? $ci['quantity'] ?? 1);
            $item = $ci['item'] ?? $ci;

            $productsForDims[] = [
                'qty' => max(1, $qty),
                'item' => [
                    'weight' => (float)($item['weight'] ?? 1),
                    'size' => $item['size'] ?? null,
                ],
            ];
        }

        if (empty($productsForDims)) {
            return ['weight' => 1, 'length' => 30, 'height' => 30, 'width' => 30];
        }

        return PriceHelper::calculateShippingDimensions($productsForDims);
    }

    /**
     * Create initial shipment log
     */
    private function createInitialLog(Order $order, int $vendorId, ?string $trackingNumber, ?string $shipmentId, string $company, string $originCity, array $rawData): void
    {
        if (!$trackingNumber) return;

        try {
            DB::table('shipment_status_logs')->insert([
                'order_id' => $order->id,
                'vendor_id' => $vendorId,
                'tracking_number' => $trackingNumber,
                'shipment_id' => $shipmentId,
                'company_name' => $company,
                'status' => 'created',
                'status_ar' => 'تم إنشاء الشحنة',
                'message' => 'Shipment created successfully',
                'message_ar' => 'تم إنشاء الشحنة بنجاح. في انتظار استلام السائق من المستودع.',
                'location' => $originCity,
                'status_date' => now(),
                'raw_data' => json_encode($rawData),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Tryoto: Failed to create initial log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync tracking status from API response
     */
    private function syncTrackingStatus(string $trackingNumber, array $data): void
    {
        $existingLog = ShipmentStatusLog::where('tracking_number', $trackingNumber)->latest()->first();

        if (!$existingLog) return;

        $currentStatus = $data['status'] ?? null;

        if ($currentStatus && $currentStatus !== $existingLog->status) {
            ShipmentStatusLog::create([
                'order_id' => $existingLog->order_id,
                'vendor_id' => $existingLog->vendor_id,
                'tracking_number' => $trackingNumber,
                'shipment_id' => $existingLog->shipment_id,
                'company_name' => $existingLog->company_name,
                'status' => $currentStatus,
                'status_ar' => $this->getStatusArabic($currentStatus),
                'message' => $data['message'] ?? null,
                'message_ar' => $this->getMessageArabic($currentStatus, $data['location'] ?? null),
                'location' => $data['location'] ?? null,
                'status_date' => $data['statusDate'] ?? now(),
                'raw_data' => $data,
            ]);

            // Update order status if delivered
            if ($currentStatus === 'delivered') {
                $order = Order::find($existingLog->order_id);
                if ($order && $order->status !== 'completed') {
                    $order->status = 'completed';
                    $order->save();
                    $order->tracks()->create([
                        'title' => 'Completed',
                        'text' => 'Order delivered - Tracking: ' . $trackingNumber,
                    ]);
                }
            }

            // Notify vendor on important status changes
            if (in_array($currentStatus, ['picked_up', 'delivered', 'failed', 'returned'])) {
                $this->notifyVendor($existingLog->vendor_id, $existingLog->order, 'status_' . $currentStatus, $trackingNumber);
            }
        }
    }

    /**
     * Send notification to vendor
     */
    private function notifyVendor(int $vendorId, $order, string $type, ?string $trackingNumber): void
    {
        if (!$vendorId) return;

        try {
            $notification = new UserNotification();
            $notification->user_id = $vendorId;
            $notification->order_number = $order->order_number ?? ($order->order_number ?? 'N/A');

            if (\Schema::hasColumn('user_notifications', 'order_id')) {
                $notification->order_id = $order->id ?? ($order->id ?? null);
            }

            $notification->is_read = 0;
            $notification->save();
        } catch (\Exception $e) {
            Log::warning('Tryoto: Notification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get Arabic status translation
     */
    private function getStatusArabic(string $status): string
    {
        $translations = [
            'created' => 'تم إنشاء الشحنة',
            'picked_up' => 'تم الاستلام من المستودع',
            'in_transit' => 'في الطريق',
            'out_for_delivery' => 'خرج للتوصيل',
            'delivered' => 'تم التسليم',
            'failed' => 'فشل التوصيل',
            'returned' => 'مرتجع',
            'cancelled' => 'ملغي',
        ];

        return $translations[$status] ?? $status;
    }

    /**
     * Get Arabic message based on status
     */
    private function getMessageArabic(string $status, ?string $location = null): string
    {
        $messages = [
            'created' => 'تم إنشاء الشحنة بنجاح',
            'picked_up' => 'تم استلام الشحنة من المستودع بنجاح',
            'in_transit' => $location ? "الشحنة في الطريق - الموقع الحالي: {$location}" : 'الشحنة في الطريق',
            'out_for_delivery' => 'الشحنة خرجت للتوصيل - سيصل السائق قريباً',
            'delivered' => 'تم تسليم الشحنة بنجاح للعميل',
            'failed' => 'فشل محاولة التوصيل - سيتم إعادة المحاولة',
            'returned' => 'تم إرجاع الشحنة إلى المستودع',
            'cancelled' => 'تم إلغاء الشحنة',
        ];

        return $messages[$status] ?? 'تم تحديث حالة الشحنة';
    }
}
