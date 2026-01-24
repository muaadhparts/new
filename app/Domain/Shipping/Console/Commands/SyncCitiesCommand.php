<?php

namespace App\Domain\Shipping\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;

/**
 * Sync Cities Command
 *
 * Syncs cities from external sources or updates status.
 */
class SyncCitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shipping:sync-cities
                            {--country= : Country code to sync}
                            {--activate-all : Activate all cities}
                            {--deactivate-unused : Deactivate cities with no orders}';

    /**
     * The console command description.
     */
    protected $description = 'Sync and manage shipping cities';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('activate-all')) {
            return $this->activateAll();
        }

        if ($this->option('deactivate-unused')) {
            return $this->deactivateUnused();
        }

        return $this->showStats();
    }

    /**
     * Activate all cities
     */
    protected function activateAll(): int
    {
        $updated = City::where('status', 0)->update(['status' => 1]);
        $this->info("Activated {$updated} cities.");
        return self::SUCCESS;
    }

    /**
     * Deactivate unused cities
     */
    protected function deactivateUnused(): int
    {
        $this->info('Finding cities with no orders...');

        // Cities that have never been used in orders
        $unusedCities = City::whereDoesntHave('purchases')->get();

        if ($unusedCities->isEmpty()) {
            $this->info('All cities have been used.');
            return self::SUCCESS;
        }

        $this->warn("Found {$unusedCities->count()} unused cities.");

        if (!$this->confirm('Deactivate these cities?')) {
            return self::SUCCESS;
        }

        $deactivated = City::whereIn('id', $unusedCities->pluck('id'))
            ->update(['status' => 0]);

        $this->info("Deactivated {$deactivated} cities.");

        return self::SUCCESS;
    }

    /**
     * Show city statistics
     */
    protected function showStats(): int
    {
        $countryCode = $this->option('country');

        $query = City::query();

        if ($countryCode) {
            $country = Country::where('code', $countryCode)->first();
            if ($country) {
                $query->where('country_id', $country->id);
            }
        }

        $total = $query->count();
        $active = (clone $query)->where('status', 1)->count();
        $inactive = $total - $active;

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Cities', $total],
                ['Active', $active],
                ['Inactive', $inactive],
            ]
        );

        return self::SUCCESS;
    }
}
