<?php

namespace App\Console\Commands;

use App\Services\TryotoService;
use Illuminate\Console\Command;

class TryotoTestCommand extends Command
{
    protected $signature = 'tryoto:test {--origin=Buraydah : Origin city} {--destination=Riyadh : Destination city}';
    protected $description = 'Test Tryoto API configuration and delivery options';

    public function handle()
    {
        $this->info('🚚 Testing Tryoto API...');
        $this->newLine();

        $service = new TryotoService();

        // 1. Check configuration
        $this->info('1️⃣ Checking configuration...');
        $config = $service->checkConfiguration();

        $this->table(['Key', 'Value'], [
            ['Configured', $config['configured'] ? '✅ Yes' : '❌ No'],
            ['Sandbox Mode', $config['sandbox'] ? '🧪 Yes (Test)' : '🔴 No (Live)'],
            ['Base URL', $config['base_url'] ?? 'Not set'],
            ['Cached Token', $config['has_cached_token'] ? '✅ Yes' : '❌ No'],
        ]);

        if (!empty($config['issues'])) {
            $this->error('Issues found:');
            foreach ($config['issues'] as $issue) {
                $this->line("  ❌ {$issue}");
            }
            return 1;
        }

        $this->newLine();

        // 2. Test token acquisition
        $this->info('2️⃣ Testing token acquisition...');
        try {
            // Try direct API call to see the actual response
            $refreshToken = config('services.tryoto.live.token');
            $this->line('  📝 Refresh Token (first 30 chars): ' . substr($refreshToken, 0, 30) . '...');

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->post('https://api.tryoto.com/rest/v2/refreshToken', [
                    'refresh_token' => $refreshToken
                ]);

            $this->line('  📡 API Status: ' . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;
                if ($token) {
                    $this->line('  ✅ Token acquired successfully');
                    $this->line('  📝 Token (first 50 chars): ' . substr($token, 0, 50) . '...');
                    $this->line('  ⏱️ Expires in: ' . ($data['expires_in'] ?? 'N/A') . ' seconds');
                } else {
                    $this->error('  ❌ No access_token in response');
                    $this->line('  📄 Response: ' . json_encode($data, JSON_PRETTY_PRINT));
                    return 1;
                }
            } else {
                $this->error('  ❌ API Error: ' . $response->status());
                $this->line('  📄 Response: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('  ❌ Exception: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // 3. Test delivery options
        $origin = $this->option('origin');
        $destination = $this->option('destination');

        $this->info("3️⃣ Testing delivery options ({$origin} → {$destination})...");

        try {
            $result = $service->getDeliveryOptions(
                $origin,
                $destination,
                1.0,  // weight
                0,    // COD
                ['length' => 30, 'width' => 30, 'height' => 30]
            );

            if ($result['success']) {
                $options = $result['options'] ?? [];
                $this->line("  ✅ Found " . count($options) . " shipping options");

                if (!empty($options)) {
                    $tableData = [];
                    foreach (array_slice($options, 0, 5) as $opt) {
                        $tableData[] = [
                            $opt['company'] ?? 'N/A',
                            ($opt['price'] ?? 0) . ' SAR',
                            ($opt['estimatedDeliveryDays'] ?? 'N/A') . ' days',
                        ];
                    }
                    $this->table(['Company', 'Price', 'Delivery'], $tableData);
                }
            } else {
                $this->error('  ❌ Failed: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('  ❌ Exception: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('✅ Tryoto test completed!');

        return 0;
    }
}
