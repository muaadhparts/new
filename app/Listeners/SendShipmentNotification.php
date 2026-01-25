<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use App\Notifications\ShipmentStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendShipmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ShipmentStatusChanged $event): void
    {
        $shipmentLog = $event->shipmentLog;
        $oldStatus = $event->oldStatus;

        // Skip if status hasn't changed
        if ($oldStatus === $shipmentLog->status) {
            return;
        }

        // Notify customer
        $this->notifyCustomer($shipmentLog, $oldStatus);

        // Notify merchant
        $this->notifyMerchant($shipmentLog, $oldStatus);

        // For critical statuses (delivered, failed, returned), also notify admin
        if (in_array($shipmentLog->status, ['delivered', 'failed', 'returned'])) {
            $this->notifyAdmin($shipmentLog, $oldStatus);
        }
    }

    /**
     * Notify customer about shipment update
     */
    protected function notifyCustomer($shipmentLog, $oldStatus): void
    {
        $purchase = Purchase::find($shipmentLog->purchase_id);

        if (!$purchase) {
            return;
        }

        // If purchase has registered user
        if ($purchase->user_id) {
            $user = User::find($purchase->user_id);
            if ($user) {
                $user->notify(new ShipmentStatusUpdated($shipmentLog, $oldStatus));
            }
        } else {
            // Guest user - send email directly
            if ($purchase->customer_email) {
                Notification::route('mail', $purchase->customer_email)
                    ->notify(new ShipmentStatusUpdated($shipmentLog, $oldStatus));
            }
        }
    }

    /**
     * Notify merchant about shipment update
     */
    protected function notifyMerchant($shipmentLog, $oldStatus): void
    {
        if (!$shipmentLog->merchant_id) {
            return;
        }

        $merchant = User::find($shipmentLog->merchant_id);
        if ($merchant) {
            // Store database notification for merchant
            $merchant->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => ShipmentStatusUpdated::class,
                'data' => [
                    'type' => 'shipment_status_updated',
                    'tracking_number' => $shipmentLog->tracking_number,
                    'purchase_id' => $shipmentLog->purchase_id,
                    'old_status' => $oldStatus,
                    'new_status' => $shipmentLog->status,
                    'status_ar' => $shipmentLog->status_ar,
                    'message' => $shipmentLog->message_ar,
                ],
            ]);
        }
    }

    /**
     * Notify admin about critical shipment updates
     */
    protected function notifyAdmin($shipmentLog, $oldStatus): void
    {
        // This could be implemented to send notifications to admin
        // via email, Slack, or other channels
        \Log::channel('tryoto')->info('Critical shipment status change', [
            'tracking' => $shipmentLog->tracking_number,
            'old_status' => $oldStatus,
            'new_status' => $shipmentLog->status,
            'purchase_id' => $shipmentLog->purchase_id,
            'merchant_id' => $shipmentLog->merchant_id,
        ]);
    }
}
