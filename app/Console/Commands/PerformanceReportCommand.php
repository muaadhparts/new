<?php

namespace App\Console\Commands;

use App\Domain\Platform\Services\PerformanceAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PerformanceReportCommand extends Command
{
    protected $signature = 'performance:report
                            {--days=7 : Number of days to analyze}
                            {--email= : Email address to send report to}
                            {--prune= : Number of days to keep (prune older entries)}';

    protected $description = 'Generate performance report and optionally send via email';

    public function handle()
    {
        $days = (int) $this->option('days');
        $email = $this->option('email');
        $pruneDays = $this->option('prune');

        $this->info("Generating performance report for the last {$days} days...");

        try {
            $analyzer = new PerformanceAnalyzer();
            $performanceReport = $analyzer->generateReport($days);

            // Display summary
            $this->displaySummary($performanceReport['summary']);

            // Display slow queries
            $this->displaySlowQueries($performanceReport['slow_queries']);

            // Display slow requests
            $this->displaySlowRequests($performanceReport['slow_requests']);

            // Prune old entries if requested
            if ($pruneDays) {
                $this->pruneOldEntries((int) $pruneDays);
            }

            // Send email if requested
            if ($email) {
                $this->sendPerformanceReport($email, $performanceReport);
            }

            // Log report generation
            Log::channel('single')->info('Performance report generated', [
                'days' => $days,
                'slow_queries' => $performanceReport['summary']['slow_queries'],
                'slow_requests' => $performanceReport['summary']['slow_requests'],
                'exceptions' => $performanceReport['summary']['exceptions_count'],
            ]);

            $this->info('Report generated successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error generating report: ' . $e->getMessage());
            Log::error('Performance report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    protected function displaySummary(array $summary): void
    {
        $this->newLine();
        $this->info('=== Performance Summary ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', number_format($summary['total_queries'])],
                ['Slow Queries', $summary['slow_queries'] . ' (' . $summary['slow_queries_percentage'] . '%)'],
                ['Very Slow Queries', $summary['very_slow_queries']],
                ['Total Requests', number_format($summary['total_requests'])],
                ['Slow Requests', $summary['slow_requests'] . ' (' . $summary['slow_requests_percentage'] . '%)'],
                ['Avg Query Time', $summary['avg_query_time_ms'] . 'ms'],
                ['Avg Request Duration', $summary['avg_request_duration_ms'] . 'ms'],
                ['Exceptions', $summary['exceptions_count']],
            ]
        );
    }

    protected function displaySlowQueries(array $queries): void
    {
        if (empty($queries)) {
            $this->info('No slow queries found.');
            return;
        }

        $this->newLine();
        $this->info('=== Top Slow Queries ===');

        $rows = array_map(function ($query) {
            return [
                $query['time'] . 'ms',
                substr($query['sql'], 0, 80) . (strlen($query['sql']) > 80 ? '...' : ''),
            ];
        }, array_slice($queries, 0, 10));

        $this->table(['Time', 'Query'], $rows);
    }

    protected function displaySlowRequests(array $requests): void
    {
        if (empty($requests)) {
            $this->info('No slow requests found.');
            return;
        }

        $this->newLine();
        $this->info('=== Top Slow Requests ===');

        $rows = array_map(function ($request) {
            return [
                $request['duration'] . 'ms',
                $request['method'],
                substr($request['uri'], 0, 50),
            ];
        }, array_slice($requests, 0, 10));

        $this->table(['Duration', 'Method', 'URI'], $rows);
    }

    protected function pruneOldEntries(int $days): void
    {
        $this->info("Pruning entries older than {$days} days...");

        $deleted = DB::table('telescope_entries')
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} old entries.");
    }

    protected function sendPerformanceReport(string $email, array $performanceReport): void
    {
        $this->info("Sending report to {$email}...");

        try {
            Mail::raw($this->formatPerformanceReportForEmail($performanceReport), function ($message) use ($email) {
                $message->to($email)
                    ->subject('Performance Report - ' . config('app.name'));
            });

            $this->info('Report sent successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
        }
    }

    protected function formatPerformanceReportForEmail(array $performanceReport): string
    {
        $summary = $performanceReport['summary'];

        return "
Performance Report - {$performanceReport['generated_at']}
===============================================

SUMMARY (Last {$summary['period_days']} days)
---------------------------------------------
Total Queries: {$summary['total_queries']}
Slow Queries: {$summary['slow_queries']} ({$summary['slow_queries_percentage']}%)
Very Slow Queries: {$summary['very_slow_queries']}

Total Requests: {$summary['total_requests']}
Slow Requests: {$summary['slow_requests']} ({$summary['slow_requests_percentage']}%)

Average Query Time: {$summary['avg_query_time_ms']}ms
Average Request Duration: {$summary['avg_request_duration_ms']}ms

Exceptions: {$summary['exceptions_count']}

THRESHOLDS
----------
Slow Query: > {$summary['thresholds']['slow_query']}ms
Very Slow Query: > {$summary['thresholds']['very_slow_query']}ms
Slow Request: > {$summary['thresholds']['slow_request']}ms

View detailed report at: " . url('/admin/performance') . "
";
    }
}
