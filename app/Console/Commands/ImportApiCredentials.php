<?php

namespace App\Console\Commands;

use App\Services\ApiCredentialService;
use Illuminate\Console\Command;

/**
 * Import SYSTEM-LEVEL API credentials from .env to encrypted database storage
 *
 * POLICY:
 * - api_credentials: ONLY for Google Maps and DigitalOcean (system-level)
 * - vendor_credentials: For Tryoto (shipping) and MyFatoorah (payment) per vendor
 * - This command does NOT import payment or shipping credentials
 */
class ImportApiCredentials extends Command
{
    protected $signature = 'credentials:import {--force : Force import without confirmation}';
    protected $description = 'Import SYSTEM-LEVEL API credentials (Google Maps, DigitalOcean) from .env to encrypted database';

    public function handle(): int
    {
        $this->info('===========================================');
        $this->info('  System API Credentials Import Tool');
        $this->info('===========================================');
        $this->newLine();

        $this->warn('POLICY NOTICE:');
        $this->line('  • This imports SYSTEM-LEVEL credentials only');
        $this->line('  • Google Maps, DigitalOcean → api_credentials table');
        $this->line('  • Tryoto, MyFatoorah → vendor_credentials table (per vendor)');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $service = new ApiCredentialService();

        // ONLY system-level credentials - NO payment or shipping credentials
        $credentials = [
            ['google_maps', 'api_key', env('GOOGLE_MAPS_API_KEY'), 'Google Maps API Key'],
            ['digitalocean', 'access_key', env('DO_ACCESS_KEY_ID'), 'DigitalOcean Spaces Access Key'],
            ['digitalocean', 'secret_key', env('DO_SECRET_ACCESS_KEY'), 'DigitalOcean Spaces Secret Key'],
        ];

        $imported = 0;
        $skipped = 0;

        foreach ($credentials as [$service_name, $key_name, $value, $description]) {
            if (empty($value)) {
                $this->line("  <fg=yellow>⊘</> {$service_name}.{$key_name} - Skipped (empty)");
                $skipped++;
                continue;
            }

            try {
                $service->set($service_name, $key_name, $value, $description);
                $this->line("  <fg=green>✓</> {$service_name}.{$key_name} - Imported");
                $imported++;
            } catch (\Exception $e) {
                $this->line("  <fg=red>✗</> {$service_name}.{$key_name} - Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Import complete: {$imported} imported, {$skipped} skipped");
        $this->newLine();

        $this->warn('===========================================');
        $this->warn('  IMPORTANT: Next Steps');
        $this->warn('===========================================');
        $this->line('1. Remove these from .env after verification:');
        $this->line('   - GOOGLE_MAPS_API_KEY');
        $this->line('   - DO_ACCESS_KEY_ID / DO_SECRET_ACCESS_KEY');
        $this->newLine();
        $this->line('2. For VENDOR credentials (Tryoto, MyFatoorah):');
        $this->line('   - Use Admin Panel > Vendor Credentials');
        $this->line('   - Or Vendor Dashboard > Settings');
        $this->line('   - Each vendor MUST have their own credentials');
        $this->newLine();

        return 0;
    }
}
