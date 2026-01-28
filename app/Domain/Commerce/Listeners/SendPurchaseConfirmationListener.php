<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\PurchasePlacedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Classes\MuaadhMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Purchase Confirmation Listener
 *
 * Sends confirmation email to customer when purchase is placed.
 *
 * Channel Independence: This listener handles customer notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class SendPurchaseConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * The queue to use
     */
    public string $queue = 'notifications';

    /**
     * Handle the event.
     */
    public function handle(PurchasePlacedEvent $event): void
    {
        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            Log::warning('SendPurchaseConfirmation: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        // Use customer email from purchase record (more reliable)
        $customerEmail = $purchase->customer_email;

        if (!$customerEmail) {
            Log::warning('SendPurchaseConfirmation: Customer email not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $this->sendConfirmationEmail($purchase, $customerEmail);

        Log::channel('domain')->info('Purchase confirmation sent to customer', [
            'event_id' => $event->eventId,
            'purchase_id' => $event->purchaseId,
            'customer_email' => $customerEmail,
        ]);
    }

    /**
     * Send the confirmation email using MuaadhMailer
     */
    protected function sendConfirmationEmail(Purchase $purchase, string $customerEmail): void
    {
        $mailer = new MuaadhMailer();

        $mailer->sendAutoPurchaseMail([
            'order_id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'customer_name' => $purchase->customer_name,
            'customer_email' => $customerEmail,
            'total' => $purchase->pay_amount,
        ], $customerEmail);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchasePlacedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send purchase confirmation', [
            'purchase_id' => $event->purchaseId,
            'error' => $exception->getMessage(),
        ]);
    }
}
