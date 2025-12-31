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

        // Channel for the customer (if purchase has user_id)
        if ($this->shipmentLog->purchase && $this->shipmentLog->purchase->user_id) {
            $channels[] = new PrivateChannel('user.' . $this->shipmentLog->purchase->user_id);
        }

        // Channel for the merchant
        if ($this->shipmentLog->merchant_id) {
            $channels[] = new PrivateChannel('merchant.' . $this->shipmentLog->merchant_id);
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
            'purchase_id' => $this->shipmentLog->purchase_id,
            'purchase_number' => $this->shipmentLog->purchase?->purchase_number,
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
