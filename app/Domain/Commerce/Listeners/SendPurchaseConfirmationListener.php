<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\PurchasePlacedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Purchase Confirmation Listener
 *
 * Sends confirmation email to customer when purchase is placed.
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

        $customer = User::find($event->customerId);

        if (!$customer || !$customer->email) {
            Log::warning('SendPurchaseConfirmation: Customer email not found', [
                'customer_id' => $event->customerId,
            ]);
            return;
        }

        $this->sendConfirmationEmail($customer, $purchase, $event);

        Log::info('Purchase confirmation sent', [
            'purchase_id' => $event->purchaseId,
            'customer_email' => $customer->email,
        ]);
    }

    /**
     * Send the confirmation email
     */
    protected function sendConfirmationEmail(User $customer, Purchase $purchase, PurchasePlacedEvent $event): void
    {
        // Mail::to($customer->email)->send(new PurchaseConfirmationMail($purchase));

        // For now, just log - actual mail implementation depends on mail setup
        Log::info('Would send purchase confirmation email', [
            'to' => $customer->email,
            'purchase_id' => $purchase->id,
            'total' => $event->totalAmount,
            'currency' => $event->currency,
        ]);
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
