<?php

namespace App\Domain\Platform\Events;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base Domain Event
 *
 * All domain events should extend this class.
 * Provides common functionality for event tracking and serialization.
 */
abstract class DomainEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * When the event occurred
     */
    public readonly DateTimeImmutable $occurredAt;

    /**
     * Unique event ID for tracking
     */
    public readonly string $eventId;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
        $this->eventId = $this->generateEventId();
    }

    /**
     * Get the event name
     */
    public function eventName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Get the aggregate type this event relates to
     */
    abstract public function aggregateType(): string;

    /**
     * Get the aggregate ID this event relates to
     */
    abstract public function aggregateId(): int|string;

    /**
     * Convert to array for logging/storage
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->eventName(),
            'aggregate_type' => $this->aggregateType(),
            'aggregate_id' => $this->aggregateId(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'payload' => $this->payload(),
        ];
    }

    /**
     * Get event payload
     */
    abstract public function payload(): array;

    /**
     * Generate unique event ID
     */
    private function generateEventId(): string
    {
        return sprintf(
            '%s-%s-%s',
            strtolower(class_basename(static::class)),
            now()->format('YmdHis'),
            substr(md5(uniqid(mt_rand(), true)), 0, 8)
        );
    }
}
