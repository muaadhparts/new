<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\PurchasePlacedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use App\Classes\MuaadhMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Merchants Of Purchase Listener
 *
 * Notifies merchants when they receive a new purchase.
 *
 * Channel Independence: This listener handles merchant notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class NotifyMerchantsOfPurchaseListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * The queue to use
     */
    public string $queue = 'notifications';

    /**
     * Handle the event.
     */
    public function handle(PurchasePlacedEvent $event): void
    {
        if (empty($event->merchantIds)) {
            return;
        }

        $purchase = Purchase::find($event->purchaseId);
        if (!$purchase) {
            Log::warning('NotifyMerchants: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $merchants = User::whereIn('id', $event->merchantIds)
            ->where('status', 1)
            ->get();

        foreach ($merchants as $merchant) {
            $this->notifyMerchant($merchant, $purchase, $event);
        }

        Log::channel('domain')->info('Merchants notified of new purchase', [
            'event_id' => $event->eventId,
            'purchase_id' => $event->purchaseId,
            'merchant_count' => $merchants->count(),
        ]);
    }

    /**
     * Notify a single merchant via email
     */
    protected function notifyMerchant(User $merchant, Purchase $purchase, PurchasePlacedEvent $event): void
    {
        if (!$merchant->email) {
            Log::warning('NotifyMerchants: Merchant has no email', [
                'merchant_id' => $merchant->id,
            ]);
            return;
        }

        $mailer = new MuaadhMailer();

        $mailer->sendMerchantPurchaseMail([
            'order_id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'merchant_name' => $merchant->shop_name ?? $merchant->name,
        ], $merchant->email);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchasePlacedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to notify merchants', [
            'purchase_id' => $event->purchaseId,
            'merchant_ids' => $event->merchantIds,
            'error' => $exception->getMessage(),
        ]);
    }
}
