<?php

namespace App\Events;

use App\Models\ShipmentTracking;
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

    public $shipmentTracking;
    public $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(ShipmentTracking $shipmentTracking, ?string $oldStatus = null)
    {
        $this->shipmentTracking = $shipmentTracking;
        $this->oldStatus = $oldStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Channel for the customer (if purchase has user_id)
        if ($this->shipmentTracking->purchase && $this->shipmentTracking->purchase->user_id) {
            $channels[] = new PrivateChannel('user.' . $this->shipmentTracking->purchase->user_id);
        }

        // Channel for the merchant
        if ($this->shipmentTracking->merchant_id) {
            $channels[] = new PrivateChannel('merchant.' . $this->shipmentTracking->merchant_id);
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
            'tracking_number' => $this->shipmentTracking->tracking_number,
            'purchase_id' => $this->shipmentTracking->purchase_id,
            'purchase_number' => $this->shipmentTracking->purchase?->purchase_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->shipmentTracking->status,
            'status_ar' => $this->shipmentTracking->status_ar,
            'message' => $this->shipmentTracking->message_ar ?? $this->shipmentTracking->message,
            'location' => $this->shipmentTracking->location,
            'company_name' => $this->shipmentTracking->company_name,
            'occurred_at' => $this->shipmentTracking->occurred_at?->toISOString(),
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
