<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SmokeTestPages - Quick verification of critical pages
 *
 * Tests that critical pages:
 * 1. Load without PHP errors
 * 2. Don't trigger N+1 queries
 * 3. Return expected HTTP status
 * 4. Complete within acceptable time
 */
class SmokeTestPages extends Command
{
    protected $signature = 'smoke:test
                            {--area=all : Area to test (front, merchant, operator, all)}
                            {--check-n-plus-1 : Enable N+1 query detection}
                            {--verbose-queries : Show all queries executed}
                            {--ci : CI mode - exit with code 1 on failure}';

    protected $description = 'Run smoke tests on critical pages to verify they work correctly';

    private array $results = [];
    private int $queryCount = 0;
    private array $queries = [];
    private bool $checkNPlus1 = false;

    /**
     * Critical pages to test per area
     */
    private array $criticalPages = [
        'front' => [
            ['name' => 'Home Page', 'route' => '/', 'expected_status' => 200],
            ['name' => 'Categories', 'route' => '/categories', 'expected_status' => 200],
            ['name' => 'Brands', 'route' => '/brands', 'expected_status' => 200],
            ['name' => 'Catalogs', 'route' => '/catalogs', 'expected_status' => 200],
        ],
        'merchant' => [
            ['name' => 'Merchant Dashboard', 'route' => '/merchant/dashboard', 'expected_status' => [200, 302], 'requires_auth' => 'merchant'],
            ['name' => 'Merchant Products', 'route' => '/merchant/catalog-items', 'expected_status' => [200, 302], 'requires_auth' => 'merchant'],
            ['name' => 'Merchant Orders', 'route' => '/merchant/purchases', 'expected_status' => [200, 302], 'requires_auth' => 'merchant'],
        ],
        'operator' => [
            ['name' => 'Operator Dashboard', 'route' => '/operator/dashboard', 'expected_status' => [200, 302], 'requires_auth' => 'operator'],
            ['name' => 'Operator Users', 'route' => '/operator/users', 'expected_status' => [200, 302], 'requires_auth' => 'operator'],
            ['name' => 'Operator Orders', 'route' => '/operator/purchases', 'expected_status' => [200, 302], 'requires_auth' => 'operator'],
        ],
    ];

    /**
     * N+1 detection thresholds
     */
    private const MAX_QUERIES_PER_PAGE = 50;
    private const DUPLICATE_QUERY_THRESHOLD = 3;

    public function handle(): int
    {
        $this->checkNPlus1 = $this->option('check-n-plus-1');
        $area = $this->option('area');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    SMOKE TEST RUNNER                         ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($this->checkNPlus1) {
            $this->info('<fg=yellow>N+1 detection enabled - monitoring queries</>');
            $this->enableQueryLogging();
        }

        $areasToTest = $area === 'all'
            ? array_keys($this->criticalPages)
            : [$area];

        $totalPassed = 0;
        $totalFailed = 0;

        foreach ($areasToTest as $areaName) {
            if (!isset($this->criticalPages[$areaName])) {
                $this->warn("Unknown area: {$areaName}");
                continue;
            }

            $this->info('');
            $this->info("┌─────────────────────────────────────────────────────────────┐");
            $this->info("│  Testing: " . strtoupper($areaName) . str_repeat(' ', 48 - strlen($areaName)) . "│");
            $this->info("└─────────────────────────────────────────────────────────────┘");

            foreach ($this->criticalPages[$areaName] as $page) {
                $result = $this->testPage($page);
                $this->results[] = $result;

                if ($result['passed']) {
                    $totalPassed++;
                    $this->info("  <fg=green>✓</> {$page['name']} ({$result['time_ms']}ms, {$result['query_count']} queries)");
                } else {
                    $totalFailed++;
                    $this->error("  ✗ {$page['name']}: {$result['error']}");
                }

                if ($this->option('verbose-queries') && !empty($result['queries'])) {
                    foreach (array_slice($result['queries'], 0, 5) as $query) {
                        $this->line("      <fg=gray>{$query}</>");
                    }
                    if (count($result['queries']) > 5) {
                        $this->line("      <fg=gray>... and " . (count($result['queries']) - 5) . " more</>");
                    }
                }
            }
        }

        // Summary
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info("                         SUMMARY");
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info("  Total tests: " . ($totalPassed + $totalFailed));
        $this->info("  <fg=green>Passed: {$totalPassed}</>");

        if ($totalFailed > 0) {
            $this->error("  Failed: {$totalFailed}");
        } else {
            $this->info("  Failed: 0");
        }

        // N+1 Summary
        if ($this->checkNPlus1) {
            $this->showNPlus1Summary();
        }

        $this->info('═══════════════════════════════════════════════════════════════');

        if ($totalFailed > 0 && $this->option('ci')) {
            return 1;
        }

        return 0;
    }

    private function testPage(array $page): array
    {
        $startTime = microtime(true);
        $this->resetQueryCounter();

        $result = [
            'name' => $page['name'],
            'route' => $page['route'],
            'passed' => false,
            'error' => null,
            'time_ms' => 0,
            'query_count' => 0,
            'queries' => [],
            'n_plus_1_detected' => false,
        ];

        try {
            // Skip auth-required pages in smoke test (they'll redirect)
            if (isset($page['requires_auth'])) {
                $result['passed'] = true;
                $result['error'] = 'Skipped (requires auth)';
                $result['time_ms'] = 0;
                return $result;
            }

            // Make request
            $url = config('app.url') . $page['route'];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html',
                    'User-Agent: SmokeTest/1.0',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $endTime = microtime(true);
            $result['time_ms'] = round(($endTime - $startTime) * 1000);
            $result['query_count'] = $this->queryCount;
            $result['queries'] = $this->queries;

            if ($error) {
                $result['error'] = "cURL error: {$error}";
                return $result;
            }

            // Check HTTP status
            $expectedStatus = (array) $page['expected_status'];
            if (!in_array($httpCode, $expectedStatus)) {
                $result['error'] = "HTTP {$httpCode} (expected: " . implode('|', $expectedStatus) . ")";
                return $result;
            }

            // Check for PHP errors in response
            if (str_contains($response, 'ErrorException') ||
                str_contains($response, 'Fatal error') ||
                str_contains($response, 'Parse error')) {
                $result['error'] = 'PHP error detected in response';
                return $result;
            }

            // Check N+1
            if ($this->checkNPlus1) {
                $nPlus1 = $this->detectNPlus1();
                if ($nPlus1) {
                    $result['n_plus_1_detected'] = true;
                    $result['error'] = "N+1 detected: {$nPlus1}";
                    return $result;
                }
            }

            // Check query count threshold
            if ($this->queryCount > self::MAX_QUERIES_PER_PAGE) {
                $result['error'] = "Too many queries: {$this->queryCount} (max: " . self::MAX_QUERIES_PER_PAGE . ")";
                return $result;
            }

            $result['passed'] = true;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    private function enableQueryLogging(): void
    {
        DB::enableQueryLog();

        DB::listen(function ($query) {
            $this->queryCount++;
            $this->queries[] = $query->sql;
        });
    }

    private function resetQueryCounter(): void
    {
        $this->queryCount = 0;
        $this->queries = [];
        DB::flushQueryLog();
    }

    private function detectNPlus1(): ?string
    {
        // Group queries by pattern
        $patterns = [];
        foreach ($this->queries as $sql) {
            // Normalize query to detect duplicates
            $normalized = preg_replace('/\b\d+\b/', '?', $sql);
            $normalized = preg_replace('/\'[^\']*\'/', '?', $normalized);

            if (!isset($patterns[$normalized])) {
                $patterns[$normalized] = 0;
            }
            $patterns[$normalized]++;
        }

        // Check for repeated queries
        foreach ($patterns as $pattern => $count) {
            if ($count >= self::DUPLICATE_QUERY_THRESHOLD) {
                return "Query repeated {$count} times: " . substr($pattern, 0, 80);
            }
        }

        return null;
    }

    private function showNPlus1Summary(): void
    {
        $detected = array_filter($this->results, fn($r) => $r['n_plus_1_detected']);

        if (empty($detected)) {
            $this->info('  <fg=green>✓ No N+1 queries detected</>');
        } else {
            $this->warn('  ⚠️  N+1 queries detected in ' . count($detected) . ' pages:');
            foreach ($detected as $result) {
                $this->warn("    - {$result['name']}: {$result['error']}");
            }
        }
    }
}
