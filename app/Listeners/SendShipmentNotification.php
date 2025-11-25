<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Models\Order;
use App\Models\User;
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

        // Notify vendor
        $this->notifyVendor($shipmentLog, $oldStatus);

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
        $order = Order::find($shipmentLog->order_id);

        if (!$order) {
            return;
        }

        // If order has registered user
        if ($order->user_id) {
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new ShipmentStatusUpdated($shipmentLog, $oldStatus));
            }
        } else {
            // Guest user - send email directly
            if ($order->customer_email) {
                Notification::route('mail', $order->customer_email)
                    ->notify(new ShipmentStatusUpdated($shipmentLog, $oldStatus));
            }
        }
    }

    /**
     * Notify vendor about shipment update
     */
    protected function notifyVendor($shipmentLog, $oldStatus): void
    {
        if (!$shipmentLog->vendor_id) {
            return;
        }

        $vendor = User::find($shipmentLog->vendor_id);
        if ($vendor) {
            // Store database notification for vendor
            $vendor->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => ShipmentStatusUpdated::class,
                'data' => [
                    'type' => 'shipment_status_updated',
                    'tracking_number' => $shipmentLog->tracking_number,
                    'order_id' => $shipmentLog->order_id,
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
            'order_id' => $shipmentLog->order_id,
            'vendor_id' => $shipmentLog->vendor_id,
        ]);
    }
}
