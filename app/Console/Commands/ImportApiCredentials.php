<?php

namespace App\Console\Commands;

use App\Services\ApiCredentialService;
use Illuminate\Console\Command;

class ImportApiCredentials extends Command
{
    protected $signature = 'credentials:import {--force : Force import without confirmation}';
    protected $description = 'Import API credentials from .env to encrypted database storage';

    public function handle(): int
    {
        $this->info('===========================================');
        $this->info('  API Credentials Import Tool');
        $this->info('===========================================');
        $this->newLine();

        if (!$this->option('force')) {
            $this->warn('This command will import API credentials from .env to the database.');
            $this->warn('Make sure the api_credentials table exists before running this command.');
            $this->newLine();

            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $service = new ApiCredentialService();

        $credentials = [
            ['google_maps', 'api_key', env('GOOGLE_MAPS_API_KEY'), 'Google Maps API Key'],
            ['myfatoorah', 'api_key', env('FATOORAH_API_KEY'), 'MyFatoorah API Key'],
            ['tryoto', 'refresh_token', env('TRYOTO_REFRESH_TOKEN'), 'Tryoto Refresh Token'],
            ['digitalocean', 'access_key', env('DO_ACCESS_KEY_ID'), 'DigitalOcean Spaces Access Key'],
            ['digitalocean', 'secret_key', env('DO_SECRET_ACCESS_KEY'), 'DigitalOcean Spaces Secret Key'],
            ['aws', 'access_key', env('AWS_ACCESS_KEY_ID'), 'AWS Access Key'],
            ['aws', 'secret_key', env('AWS_SECRET_ACCESS_KEY'), 'AWS Secret Key'],
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
        $this->line('1. Remove the following from .env after verification:');
        $this->line('   - GOOGLE_MAPS_API_KEY');
        $this->line('   - FATOORAH_API_KEY');
        $this->line('   - TRYOTO_REFRESH_TOKEN');
        $this->line('   - DO_ACCESS_KEY_ID / DO_SECRET_ACCESS_KEY');
        $this->line('   - AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY');
        $this->newLine();
        $this->line('2. Update your code to use ApiCredentialService instead of env()');
        $this->newLine();

        return 0;
    }
}
