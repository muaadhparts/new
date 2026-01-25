<?php

namespace App\Console\Commands;

use App\Domain\Merchant\Services\ApiCredentialService;
use Illuminate\Console\Command;

class SetApiCredential extends Command
{
    protected $signature = 'credentials:set
                            {service : The service name (e.g., google_maps, myfatoorah, tryoto)}
                            {key : The key name (e.g., api_key, secret_key, refresh_token)}
                            {--description= : Optional description}';

    protected $description = 'Set or update an encrypted API credential';

    public function handle(): int
    {
        $serviceName = $this->argument('service');
        $keyName = $this->argument('key');
        $description = $this->option('description');

        $this->info("Setting credential: {$serviceName}.{$keyName}");
        $this->newLine();

        // Securely prompt for the value (hidden input)
        $value = $this->secret('Enter the credential value (input is hidden):');

        if (empty($value)) {
            $this->error('Credential value cannot be empty.');
            return 1;
        }

        // Confirm the value
        $confirmValue = $this->secret('Confirm the credential value:');

        if ($value !== $confirmValue) {
            $this->error('Values do not match. Please try again.');
            return 1;
        }

        try {
            $service = new ApiCredentialService();
            $service->set($serviceName, $keyName, $value, $description);

            $this->newLine();
            $this->info("✓ Credential {$serviceName}.{$keyName} has been securely stored.");
            $this->newLine();

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to store credential: {$e->getMessage()}");
            return 1;
        }
    }
}
