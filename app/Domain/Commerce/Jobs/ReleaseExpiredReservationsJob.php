<?php

namespace App\Domain\Commerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Commerce\Models\StockReservation;
use Carbon\Carbon;

/**
 * Release Expired Reservations Job
 *
 * Releases stock from expired cart reservations.
 */
class ReleaseExpiredReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $expirationMinutes = 30
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cutoff = Carbon::now()->subMinutes($this->expirationMinutes);

        $expiredReservations = StockReservation::where('status', 'reserved')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($expiredReservations as $reservation) {
            $this->releaseReservation($reservation);
        }

        \Log::info('Released expired reservations', [
            'count' => $expiredReservations->count(),
        ]);
    }

    /**
     * Release a single reservation
     */
    protected function releaseReservation(StockReservation $reservation): void
    {
        // Return stock to item
        if ($reservation->merchantItem) {
            $reservation->merchantItem->increment('stock', $reservation->quantity);
        }

        // Mark as released
        $reservation->update([
            'status' => 'released',
            'released_at' => now(),
        ]);
    }
}
