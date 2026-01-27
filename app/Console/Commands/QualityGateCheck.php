<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * QualityGateCheck - Enforces quality baselines in CI
 *
 * This command ensures that code quality doesn't degrade:
 * 1. Runs lint:blade and lint:dataflow
 * 2. Compares results against .quality-baseline.json
 * 3. Fails if warnings increase above baseline
 * 4. Shows progress toward quality targets
 */
class QualityGateCheck extends Command
{
    protected $signature = 'quality:check
                            {--ci : CI mode - exit with code 1 on regression}
                            {--update-baseline : Update baseline with current values (use after fixing issues)}
                            {--show-progress : Show progress toward targets}';

    protected $description = 'Check code quality against baseline and prevent regressions';

    private string $baselinePath;
    private array $baseline;

    public function handle(): int
    {
        $this->baselinePath = base_path('.quality-baseline.json');

        if (!file_exists($this->baselinePath)) {
            $this->error('Baseline file not found: .quality-baseline.json');
            $this->info('Run: php artisan quality:check --update-baseline to create it');
            return 1;
        }

        $this->baseline = json_decode(file_get_contents($this->baselinePath), true);

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    QUALITY GATE CHECK                        ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Run linters and capture results
        $bladeResult = $this->runBladeLint();
        $dataflowResult = $this->runDataflowLint();

        // Check against baseline
        $hasRegression = false;

        $this->info('');
        $this->info('┌─────────────────────────────────────────────────────────────┐');
        $this->info('│                    BASELINE COMPARISON                       │');
        $this->info('└─────────────────────────────────────────────────────────────┘');

        // Check blade lint
        $bladeBaseline = $this->baseline['baselines']['lint:blade'] ?? ['errors' => 0, 'warnings' => 0];
        $bladeRegression = $this->checkRegression(
            'lint:blade',
            $bladeResult['errors'],
            $bladeResult['warnings'],
            $bladeBaseline['errors'],
            $bladeBaseline['warnings']
        );
        $hasRegression = $hasRegression || $bladeRegression;

        // Check dataflow lint
        $dataflowBaseline = $this->baseline['baselines']['lint:dataflow'] ?? ['errors' => 0, 'warnings' => 0];
        $dataflowRegression = $this->checkRegression(
            'lint:dataflow',
            $dataflowResult['errors'],
            $dataflowResult['warnings'],
            $dataflowBaseline['errors'],
            $dataflowBaseline['warnings']
        );
        $hasRegression = $hasRegression || $dataflowRegression;

        // Show progress if requested
        if ($this->option('show-progress')) {
            $this->showProgress($bladeResult, $dataflowResult);
        }

        // Update baseline if requested
        if ($this->option('update-baseline')) {
            $this->updateBaseline($bladeResult, $dataflowResult);
            $this->info('');
            $this->info('<fg=green>✓ Baseline updated successfully</>');
            return 0;
        }

        // CI mode - fail on regression
        if ($hasRegression) {
            $this->error('');
            $this->error('╔══════════════════════════════════════════════════════════════╗');
            $this->error('║  QUALITY GATE FAILED - Warnings increased above baseline!    ║');
            $this->error('╚══════════════════════════════════════════════════════════════╝');
            $this->error('');
            $this->error('Fix the issues or document exceptions with @dataflow-exception');

            if ($this->option('ci')) {
                return 1;
            }
        } else {
            $this->info('');
            $this->info('<fg=green>╔══════════════════════════════════════════════════════════════╗</>');
            $this->info('<fg=green>║           QUALITY GATE PASSED - No regressions!              ║</>');
            $this->info('<fg=green>╚══════════════════════════════════════════════════════════════╝</>');
        }

        return 0;
    }

    private function runBladeLint(): array
    {
        $this->info('Running lint:blade...');

        // Capture output
        ob_start();
        Artisan::call('lint:blade', ['--ci' => false]);
        $output = ob_get_clean();
        $output .= Artisan::output();

        // Parse results
        $errors = 0;
        $warnings = 0;

        if (preg_match('/Total errors:\s*(\d+)/i', $output, $matches)) {
            $errors = (int) $matches[1];
        }
        if (preg_match('/Total warnings:\s*(\d+)/i', $output, $matches)) {
            $warnings = (int) $matches[1];
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function runDataflowLint(): array
    {
        $this->info('Running lint:dataflow...');

        ob_start();
        Artisan::call('lint:dataflow', ['--summary' => true]);
        $output = ob_get_clean();
        $output .= Artisan::output();

        $errors = 0;
        $warnings = 0;

        if (preg_match('/Warnings:\s*(\d+)/i', $output, $matches)) {
            $warnings = (int) $matches[1];
        }
        if (preg_match('/Errors:\s*(\d+)/i', $output, $matches)) {
            $errors = (int) $matches[1];
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function checkRegression(
        string $name,
        int $currentErrors,
        int $currentWarnings,
        int $baselineErrors,
        int $baselineWarnings
    ): bool {
        $hasRegression = false;

        $errorDiff = $currentErrors - $baselineErrors;
        $warningDiff = $currentWarnings - $baselineWarnings;

        $errorStatus = $errorDiff > 0 ? '<fg=red>▲ +' . $errorDiff . '</>' : ($errorDiff < 0 ? '<fg=green>▼ ' . $errorDiff . '</>' : '<fg=gray>─ 0</>');
        $warningStatus = $warningDiff > 0 ? '<fg=red>▲ +' . $warningDiff . '</>' : ($warningDiff < 0 ? '<fg=green>▼ ' . $warningDiff . '</>' : '<fg=gray>─ 0</>');

        $this->info('');
        $this->info("  <fg=cyan>{$name}</>");
        $this->info("    Errors:   {$currentErrors} (baseline: {$baselineErrors}) {$errorStatus}");
        $this->info("    Warnings: {$currentWarnings} (baseline: {$baselineWarnings}) {$warningStatus}");

        if ($currentErrors > $baselineErrors) {
            $this->error("    ❌ REGRESSION: Errors increased by {$errorDiff}");
            $hasRegression = true;
        }

        if ($currentWarnings > $baselineWarnings) {
            $this->warn("    ⚠️  REGRESSION: Warnings increased by {$warningDiff}");
            $hasRegression = true;
        }

        if ($currentWarnings < $baselineWarnings) {
            $this->info("    <fg=green>✓ IMPROVEMENT: Warnings reduced by " . abs($warningDiff) . "</>");
        }

        return $hasRegression;
    }

    private function showProgress(array $bladeResult, array $dataflowResult): void
    {
        $this->info('');
        $this->info('┌─────────────────────────────────────────────────────────────┐');
        $this->info('│                    PROGRESS TO TARGETS                       │');
        $this->info('└─────────────────────────────────────────────────────────────┘');

        $targets = $this->baseline['targets'] ?? [];

        foreach ($targets as $name => $target) {
            $current = match ($name) {
                'lint:blade_warnings' => $bladeResult['warnings'],
                'lint:dataflow_warnings' => $dataflowResult['warnings'],
                default => $target['current']
            };

            $targetValue = $target['target'];
            $deadline = $target['deadline'];
            $progress = $target['current'] > 0
                ? round((($target['current'] - $current) / ($target['current'] - $targetValue)) * 100, 1)
                : 100;
            $progress = max(0, min(100, $progress));

            $progressBar = $this->buildProgressBar($progress);

            $this->info("  {$name}:");
            $this->info("    Current: {$current} → Target: {$targetValue} (Deadline: {$deadline})");
            $this->info("    {$progressBar} {$progress}%");
        }
    }

    private function buildProgressBar(float $progress): string
    {
        $filled = (int) ($progress / 5);
        $empty = 20 - $filled;
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
    }

    private function updateBaseline(array $bladeResult, array $dataflowResult): void
    {
        $this->baseline['generated_at'] = date('Y-m-d');
        $this->baseline['baselines']['lint:blade'] = [
            'errors' => $bladeResult['errors'],
            'warnings' => $bladeResult['warnings'],
            'last_reduced' => date('Y-m-d'),
        ];
        $this->baseline['baselines']['lint:dataflow'] = [
            'errors' => $dataflowResult['errors'],
            'warnings' => $dataflowResult['warnings'],
            'last_reduced' => date('Y-m-d'),
        ];

        // Update targets current values
        if (isset($this->baseline['targets']['lint:blade_warnings'])) {
            $this->baseline['targets']['lint:blade_warnings']['current'] = $bladeResult['warnings'];
        }
        if (isset($this->baseline['targets']['lint:dataflow_warnings'])) {
            $this->baseline['targets']['lint:dataflow_warnings']['current'] = $dataflowResult['warnings'];
        }

        file_put_contents(
            $this->baselinePath,
            json_encode($this->baseline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
