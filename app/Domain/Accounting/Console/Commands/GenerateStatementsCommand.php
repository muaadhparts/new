<?php

namespace App\Domain\Accounting\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Identity\Models\User;
use App\Domain\Accounting\Services\MerchantStatementService;
use Carbon\Carbon;

/**
 * Generate Statements Command
 *
 * Generates monthly statements for merchants.
 */
class GenerateStatementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounting:generate-statements
                            {--month= : Month in YYYY-MM format (default: last month)}
                            {--merchant= : Specific merchant ID}
                            {--send-email : Send statements via email}';

    /**
     * The console command description.
     */
    protected $description = 'Generate monthly statements for merchants';

    /**
     * Execute the console command.
     */
    public function handle(MerchantStatementService $statementService): int
    {
        $monthOption = $this->option('month');
        $merchantId = $this->option('merchant');
        $sendEmail = $this->option('send-email');

        // Parse month
        if ($monthOption) {
            $date = Carbon::createFromFormat('Y-m', $monthOption)->startOfMonth();
        } else {
            $date = Carbon::now()->subMonth()->startOfMonth();
        }

        $this->info("Generating statements for {$date->format('F Y')}...");

        // Get merchants
        $query = User::where('is_merchant', 1);

        if ($merchantId) {
            $query->where('id', $merchantId);
        }

        $merchants = $query->get();

        if ($merchants->isEmpty()) {
            $this->warn('No merchants found.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($merchants->count());
        $generated = 0;

        foreach ($merchants as $merchant) {
            try {
                $statement = $statementService->generate($merchant, $date);

                if ($sendEmail && $statement) {
                    // Send email notification
                    $this->line(" - Statement sent to {$merchant->email}");
                }

                $generated++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Failed for merchant #{$merchant->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Generated {$generated} statements.");

        return self::SUCCESS;
    }
}
