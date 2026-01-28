<?php

namespace App\Domain\Commerce\Listeners;

use App\Domain\Commerce\Events\PaymentReceivedEvent;
use App\Domain\Commerce\Models\Purchase;
use App\Classes\MuaadhMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Send Payment Confirmation Listener
 *
 * Sends payment confirmation email to customer when payment is received.
 *
 * Channel Independence: This listener handles payment notifications
 * for ALL channels (Web, Mobile, API, WhatsApp).
 */
class SendPaymentConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 60;
    public string $queue = 'notifications';

    /**
     * Handle the event.
     */
    public function handle(PaymentReceivedEvent $event): void
    {
        $purchase = Purchase::find($event->purchaseId);

        if (!$purchase) {
            Log::warning('SendPaymentConfirmation: Purchase not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $customerEmail = $purchase->customer_email;

        if (!$customerEmail) {
            Log::warning('SendPaymentConfirmation: Customer email not found', [
                'purchase_id' => $event->purchaseId,
            ]);
            return;
        }

        $this->sendPaymentConfirmationEmail($purchase, $event);

        Log::channel('domain')->info('Payment confirmation sent to customer', [
            'event_id' => $event->eventId,
            'purchase_id' => $event->purchaseId,
            'amount' => $event->amount,
            'payment_method' => $event->paymentMethod,
        ]);
    }

    /**
     * Send payment confirmation email
     */
    protected function sendPaymentConfirmationEmail(Purchase $purchase, PaymentReceivedEvent $event): void
    {
        $mailer = new MuaadhMailer();

        // Use existing payment confirmation mail if available
        // Otherwise, this could be extended to send a specific payment confirmation
        $mailer->sendAutoPurchaseMail([
            'order_id' => $purchase->id,
            'purchase_number' => $purchase->purchase_number,
            'customer_name' => $purchase->customer_name,
            'customer_email' => $purchase->customer_email,
            'total' => $event->amount,
            'payment_method' => $event->paymentMethod,
            'transaction_id' => $event->transactionId,
            'type' => 'payment_confirmation',
        ], $purchase->customer_email);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentReceivedEvent $event, \Throwable $exception): void
    {
        Log::error('Failed to send payment confirmation', [
            'purchase_id' => $event->purchaseId,
            'error' => $exception->getMessage(),
        ]);
    }
}
