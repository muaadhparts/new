<?php

namespace App\Console\Commands;

use App\Events\ShipmentStatusChanged;
use App\Models\ShipmentStatusLog;
use App\Services\TryotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateShipmentStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shipments:update
                            {--status=* : Filter by status (default: pending,created,picked_up,in_transit,out_for_delivery)}
                            {--limit=100 : Maximum number of shipments to update}
                            {--merchant= : Filter by merchant ID}
                            {--force : Force update even recently updated shipments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all active shipment statuses from Tryoto API';

    protected TryotoService $tryotoService;

    public function __construct()
    {
        parent::__construct();
        $this->tryotoService = new TryotoService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚚 Starting shipment status update...');

        $statuses = $this->option('status');
        if (empty($statuses)) {
            // Default: only update active shipments (not delivered, cancelled, returned)
            $statuses = ['pending', 'created', 'picked_up', 'in_transit', 'out_for_delivery'];
        }

        $limit = (int) $this->option('limit');
        $merchantId = $this->option('merchant');
        $force = $this->option('force');

        // Get unique tracking numbers with their latest status
        $query = ShipmentStatusLog::query()
            ->whereIn('id', function ($sub) use ($statuses, $merchantId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_status_logs')
                    ->whereIn('status', $statuses)
                    ->when($merchantId, function ($q) use ($merchantId) {
                        $q->where('merchant_id', $merchantId);
                    })
                    ->groupBy('tracking_number');
            });

        // If not forcing, skip recently updated (within last hour)
        if (!$force) {
            $query->where('updated_at', '<', now()->subHour());
        }

        $shipments = $query->limit($limit)
            ->orderBy('updated_at', 'asc') // Oldest first
            ->get();

        $total = $shipments->count();
        $updated = 0;
        $failed = 0;
        $unchanged = 0;

        if ($total === 0) {
            $this->info('✅ No shipments need updating.');
            return 0;
        }

        $this->info("Found {$total} shipments to update...");
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($shipments as $shipment) {
            try {
                $result = $this->tryotoService->trackShipment($shipment->tracking_number);

                if ($result['success']) {
                    $newStatus = $result['status'] ?? 'unknown';

                    if ($newStatus !== $shipment->status) {
                        $updated++;

                        // Event is fired inside syncTrackingStatus
                        Log::channel('tryoto')->info('Shipment status updated via cron', [
                            'tracking' => $shipment->tracking_number,
                            'old_status' => $shipment->status,
                            'new_status' => $newStatus,
                        ]);
                    } else {
                        $unchanged++;
                        // Touch the record to update timestamp even if status unchanged
                        $shipment->touch();
                    }
                } else {
                    $failed++;
                    Log::channel('tryoto')->warning('Failed to update shipment', [
                        'tracking' => $shipment->tracking_number,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::channel('tryoto')->error('Exception updating shipment', [
                    'tracking' => $shipment->tracking_number,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();

            // Small delay to avoid rate limiting
            usleep(200000); // 200ms delay
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Update complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $total],
                ['Updated', $updated],
                ['Unchanged', $unchanged],
                ['Failed', $failed],
            ]
        );

        Log::channel('tryoto')->info('Shipment status cron completed', [
            'total' => $total,
            'updated' => $updated,
            'unchanged' => $unchanged,
            'failed' => $failed,
        ]);

        return 0;
    }
}
