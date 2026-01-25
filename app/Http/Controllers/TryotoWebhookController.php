<?php

namespace App\Http\Controllers;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Identity\Models\UserCatalogEvent;
use App\Domain\Shipping\Services\ShipmentTrackingService;
use App\Domain\Accounting\Services\PaymentAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TryotoWebhookController extends Controller
{
    /**
     * Webhook Secret Key - يجب أن يكون نفسه في Tryoto Dashboard
     */
    private const WEBHOOK_SECRET = 'tryoto_webhook_secret_key_2025';

    /**
     * Handle Tryoto webhook notifications
     *
     * Tryoto will POST to this endpoint when shipment status changes
     */
    public function handle(Request $request)
    {
        try {
            // ✅ Security Layer 1: IP Whitelist (اختياري - حدد IPs Tryoto)
            if (!$this->verifyTrustedSource($request)) {
                Log::warning('Tryoto Webhook: Untrusted IP', ['ip' => $request->ip()]);
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // ✅ Security Layer 2: Signature Verification
            if (!$this->verifySignature($request)) {
                Log::warning('Tryoto Webhook: Invalid signature', ['ip' => $request->ip()]);
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
            }

            // ✅ Security Layer 3: Rate Limiting (منع spam)
            if (!$this->checkRateLimit($request)) {
                Log::warning('Tryoto Webhook: Rate limit exceeded', ['ip' => $request->ip()]);
                return response()->json(['success' => false, 'message' => 'Too many requests'], 429);
            }

            // Log incoming webhook
            Log::debug('Tryoto Webhook Received', ['payload' => $request->all()]);

            // استخراج البيانات من Webhook
            $trackingNumber = $request->input('trackingNumber');
            $shipmentId = $request->input('shipmentId');
            $status = $request->input('status'); // picked_up, in_transit, delivered, etc
            $location = $request->input('location');
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $message = $request->input('message');
            $statusDate = $request->input('statusDate') ?: now();

            if (!$trackingNumber) {
                Log::warning('Tryoto Webhook: Missing tracking number');
                return response()->json(['success' => false, 'message' => 'Tracking number required'], 400);
            }

            // البحث عن الشحنة في النظام
            $existingTracking = ShipmentTracking::getLatestByTracking($trackingNumber);

            if (!$existingTracking) {
                Log::warning('Tryoto Webhook: Tracking number not found', ['tracking' => $trackingNumber]);
                return response()->json(['success' => false, 'message' => 'Tracking number not found'], 404);
            }

            // Use ShipmentTrackingService to update
            $trackingService = app(ShipmentTrackingService::class);
            $newTracking = $trackingService->updateFromApi(
                $trackingNumber,
                $status,
                [
                    'location' => $location,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'message' => $message,
                    'message_ar' => $this->getMessageArabic($status, $location),
                    'occurred_at' => $statusDate,
                    'raw_payload' => $request->all(),
                ]
            );

            // ترجمة الحالة للعربية
            $statusAr = $this->getStatusArabic($status);

            // Note: Purchase status is now automatically updated via:
            // ShipmentTrackingService → ShipmentTrackingObserver → OrderStatusResolverService
            // No direct status modification needed here

            // === COD Collection Status Update ===
            // When shipment is delivered, update COD collection status
            if ($status === 'delivered' && $existingTracking->purchase_id) {
                $this->updateCodCollectionStatus($existingTracking->purchase_id);
            }

            // إرسال Notification للتاجر عند التغييرات المهمة
            if (in_array($status, ['picked_up', 'delivered', 'failed', 'returned'])) {
                if ($existingTracking->merchant_id) {
                    try {
                        $notification = new UserCatalogEvent();
                        $notification->user_id = $existingTracking->merchant_id;
                        $notification->purchase_number = $existingTracking->purchase->purchase_number ?? 'N/A';
                        // فقط إضافة purchase_id إذا كان العمود موجوداً
                        if (\Schema::hasColumn('user_catalog_events', 'purchase_id')) {
                            $notification->purchase_id = $existingTracking->purchase_id;
                        }
                        $notification->is_read = 0;
                        $notification->save();
                    } catch (\Exception $notifError) {
                        // تجاهل أخطاء الـ notification - ليست حرجة
                        Log::warning('Tryoto Webhook: Notification failed', [
                            'error' => $notifError->getMessage(),
                            'merchant_id' => $existingTracking->merchant_id,
                        ]);
                    }
                }
            }

            Log::debug('Tryoto Webhook Processed Successfully', [
                'tracking' => $trackingNumber,
                'status' => $status,
                'purchase_id' => $existingTracking->purchase_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'data' => [
                    'tracking_number' => $trackingNumber,
                    'status' => $status,
                    'status_ar' => $statusAr,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Tryoto Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get Arabic translation for status
     */
    private function getStatusArabic($status)
    {
        return ShipmentTracking::getStatusTranslation($status);
    }

    /**
     * Get Arabic message based on status
     */
    private function getMessageArabic($status, $location = null)
    {
        $messages = [
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

    /**
     * Test endpoint to check webhook is working
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'Tryoto Webhook endpoint is working',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Update COD collection status when shipment is delivered
     *
     * Uses PaymentAccountingService to mark COD as collected
     */
    private function updateCodCollectionStatus(int $purchaseId): void
    {
        try {
            $accountingService = app(PaymentAccountingService::class);

            // Get all MerchantPurchases for this purchase that have COD
            $merchantPurchases = MerchantPurchase::where('purchase_id', $purchaseId)
                ->where('collection_status', MerchantPurchase::COLLECTION_PENDING)
                ->where('delivery_method', MerchantPurchase::DELIVERY_SHIPPING_COMPANY)
                ->get();

            foreach ($merchantPurchases as $mp) {
                $accountingService->markCollectedByShippingCompany($mp, 'tryoto');
                Log::debug('COD Collection marked for MerchantPurchase', [
                    'merchant_purchase_id' => $mp->id,
                    'purchase_id' => $purchaseId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update COD collection status', [
                'purchase_id' => $purchaseId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ✅ Security: Verify trusted source (IP Whitelist)
     */
    private function verifyTrustedSource(Request $request)
    {
        // إذا كنت تريد تفعيل IP whitelist، أضف IPs Tryoto هنا
        $trustedIps = [
            // '185.123.45.67', // Tryoto IP 1
            // '185.123.45.68', // Tryoto IP 2
        ];

        // إذا كانت القائمة فارغة، نسمح بجميع الـ IPs (للتطوير)
        if (empty($trustedIps)) {
            return true;
        }

        $clientIp = $request->ip();
        return in_array($clientIp, $trustedIps);
    }

    /**
     * ✅ Security: Verify webhook signature
     */
    private function verifySignature(Request $request)
    {
        $signature = $request->header('X-Tryoto-Signature');

        // إذا لم يكن هناك signature في الـ header (للتطوير)
        if (!$signature) {
            // في Production، يجب أن ترجع false
            // return false;

            // في Development، نسمح بدون signature
            return true;
        }

        // حساب الـ signature المتوقع
        $payload = json_encode($request->all());
        $expectedSignature = hash_hmac('sha256', $payload, self::WEBHOOK_SECRET);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * ✅ Security: Rate limiting
     */
    private function checkRateLimit(Request $request)
    {
        $key = 'webhook_rate_limit:' . $request->ip();
        $maxAttempts = 60; // 60 requests
        $decayMinutes = 1; // per minute

        $attempts = cache()->get($key, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        return true;
    }
}
