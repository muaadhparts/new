<?php

namespace App\Events;

use App\Models\ShipmentStatusLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shipmentLog;
    public $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(ShipmentStatusLog $shipmentLog, ?string $oldStatus = null)
    {
        $this->shipmentLog = $shipmentLog;
        $this->oldStatus = $oldStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Channel for the customer (if order has user_id)
        if ($this->shipmentLog->order && $this->shipmentLog->order->user_id) {
            $channels[] = new PrivateChannel('user.' . $this->shipmentLog->order->user_id);
        }

        // Channel for the vendor
        if ($this->shipmentLog->vendor_id) {
            $channels[] = new PrivateChannel('vendor.' . $this->shipmentLog->vendor_id);
        }

        // Admin channel
        $channels[] = new PrivateChannel('admin.shipments');

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'tracking_number' => $this->shipmentLog->tracking_number,
            'order_id' => $this->shipmentLog->order_id,
            'order_number' => $this->shipmentLog->order?->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->shipmentLog->status,
            'status_ar' => $this->shipmentLog->status_ar,
            'message' => $this->shipmentLog->message_ar,
            'location' => $this->shipmentLog->location,
            'company_name' => $this->shipmentLog->company_name,
            'status_date' => $this->shipmentLog->status_date?->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'shipment.status.changed';
    }
}
