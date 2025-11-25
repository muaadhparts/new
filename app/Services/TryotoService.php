<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShipmentStatusLog;
use App\Models\User;
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
     * Get delivery options for a route
     *
     * @param string $originCity المدينة المصدر
     * @param string $destinationCity المدينة الوجهة
     * @param float $weight الوزن بالكيلو
     * @param float $codAmount مبلغ الدفع عند الاستلام
     * @return array
     */
    public function getDeliveryOptions(string $originCity, string $destinationCity, float $weight = 1, float $codAmount = 0): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            $response = Http::withToken($token)->post($this->baseUrl . '/rest/v2/getDeliveryOptions', [
                'originCity' => $originCity,
                'destinationCity' => $destinationCity,
                'weight' => max(0.1, $weight),
                'codAmount' => $codAmount,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'options' => $data['deliveryOptions'] ?? [],
                    'raw' => $data
                ];
            }

            Log::warning('Tryoto: getDeliveryOptions failed', [
                'origin' => $originCity,
                'destination' => $destinationCity,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['success' => false, 'error' => 'Failed to get delivery options'];

        } catch (\Exception $e) {
            Log::error('Tryoto: getDeliveryOptions exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a shipment
     *
     * @param Order $order
     * @param int $vendorId
     * @param string $deliveryOptionId
     * @param string $company
     * @param float $price
     * @return array
     */
    public function createShipment(Order $order, int $vendorId, string $deliveryOptionId, string $company, float $price): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            // Get vendor info
            $vendor = User::find($vendorId);
            $originCity = $vendor->warehouse_city ?? $vendor->shop_city ?? 'Riyadh';
            $originAddress = $vendor->warehouse_address ?? $vendor->shop_address ?? '';

            // Get destination
            $destinationCity = $order->shipping_city ?: $order->customer_city ?: 'Riyadh';

            // Calculate dimensions from cart
            $dims = $this->calculateDimensions($order);

            // Determine COD amount
            $codAmount = in_array($order->method, ['cod', 'Cash On Delivery']) ? (float)$order->pay_amount : 0.0;

            $payload = [
                'otoId' => $order->order_number,
                'deliveryOptionId' => $deliveryOptionId,
                'originCity' => $originCity,
                'destinationCity' => $destinationCity,
                'receiverName' => $order->shipping_name ?: $order->customer_name,
                'receiverPhone' => $order->shipping_phone ?: $order->customer_phone,
                'receiverAddress' => $order->shipping_address ?: $order->customer_address,
                'weight' => max(0.1, $dims['weight']),
                'xlength' => max(30, $dims['length']),
                'xheight' => max(30, $dims['height']),
                'xwidth' => max(30, $dims['width']),
                'codAmount' => $codAmount,
            ];

            Log::info('Tryoto: Creating shipment', ['order_id' => $order->id, 'payload' => $payload]);

            $response = Http::withToken($token)->post($this->baseUrl . '/rest/v2/createShipment', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $shipmentId = $data['shipmentId'] ?? null;
                $trackingNumber = $data['trackingNumber'] ?? null;

                // Save to shipment_status_logs
                $this->createInitialLog($order, $vendorId, $trackingNumber, $shipmentId, $company, $originCity, $data);

                // Send notification to vendor
                $this->notifyVendor($vendorId, $order, 'shipment_created', $trackingNumber);

                Log::info('Tryoto: Shipment created successfully', [
                    'order_id' => $order->id,
                    'tracking_number' => $trackingNumber,
                    'shipment_id' => $shipmentId
                ]);

                return [
                    'success' => true,
                    'shipment_id' => $shipmentId,
                    'tracking_number' => $trackingNumber,
                    'company' => $company,
                    'price' => $price,
                    'raw' => $data
                ];
            }

            Log::error('Tryoto: createShipment failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['success' => false, 'error' => 'Failed to create shipment', 'details' => $response->body()];

        } catch (\Exception $e) {
            Log::error('Tryoto: createShipment exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Track a shipment
     *
     * @param string $trackingNumber
     * @return array
     */
    public function trackShipment(string $trackingNumber): array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Unable to get access token'];
            }

            $response = Http::withToken($token)->post($this->baseUrl . '/rest/v2/trackShipment', [
                'trackingNumber' => $trackingNumber
            ]);

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

            return ['success' => false, 'error' => 'Failed to track shipment'];

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
