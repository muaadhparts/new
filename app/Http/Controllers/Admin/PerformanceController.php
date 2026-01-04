<?php

namespace App\Http\Controllers\Admin;

use App\Services\PerformanceAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PerformanceController extends AdminBaseController
{
    protected PerformanceAnalyzer $analyzer;

    public function __construct()
    {
        parent::__construct();
        $this->analyzer = new PerformanceAnalyzer();
    }

    /**
     * Display performance dashboard
     */
    public function index(Request $request)
    {
        $days = (int) $request->get('days', 7);

        try {
            $summary = $this->analyzer->getSummary($days);
            $slowQueries = $this->analyzer->getSlowQueries($days, 10);
            $slowRequests = $this->analyzer->getSlowRequests($days, 10);
            $slowestEndpoints = $this->analyzer->getSlowestEndpoints($days, 10);

            return view('admin.performance.index', compact(
                'summary',
                'slowQueries',
                'slowRequests',
                'slowestEndpoints',
                'days'
            ));
        } catch (\Exception $e) {
            return view('admin.performance.index', [
                'error' => $e->getMessage(),
                'days' => $days,
            ]);
        }
    }

    /**
     * Get slow queries list
     */
    public function slowQueries(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $slowQueries = $this->analyzer->getSlowQueries($days, 50);

        return view('admin.performance.slow-queries', compact('slowQueries', 'days'));
    }

    /**
     * Get slow requests list
     */
    public function slowRequests(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $slowRequests = $this->analyzer->getSlowRequests($days, 50);

        return view('admin.performance.slow-requests', compact('slowRequests', 'days'));
    }

    /**
     * Get repeated queries
     */
    public function repeatedQueries(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $repeatedQueries = $this->analyzer->getMostRepeatedQueries($days, 30);

        return view('admin.performance.repeated-queries', compact('repeatedQueries', 'days'));
    }

    /**
     * Generate and download JSON report
     */
    public function downloadReport(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $performanceReport = $this->analyzer->generateReport($days);

        $filename = 'performance-report-' . Carbon::now()->format('Y-m-d-His') . '.json';

        return response()->json($performanceReport)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * API endpoint for performance data (for AJAX)
     */
    public function apiSummary(Request $request)
    {
        $days = (int) $request->get('days', 7);

        return response()->json([
            'success' => true,
            'data' => $this->analyzer->getSummary($days),
        ]);
    }

    /**
     * Prune old telescope entries
     */
    public function pruneOldEntries(Request $request)
    {
        $days = (int) $request->get('keep_days', 30);

        $deleted = DB::table('telescope_entries')
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deleted} old entries",
            'deleted_count' => $deleted,
        ]);
    }
}
