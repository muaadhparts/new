<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PerformanceAnalyzer
{
    protected int $slowQueryThreshold;
    protected int $verySlowQueryThreshold;
    protected int $slowRequestThreshold;

    public function __construct()
    {
        $this->slowQueryThreshold = (int) env('SLOW_QUERY_THRESHOLD', 100);
        $this->verySlowQueryThreshold = (int) env('VERY_SLOW_QUERY_THRESHOLD', 500);
        $this->slowRequestThreshold = (int) env('SLOW_REQUEST_THRESHOLD', 1000);
    }

    /**
     * Get slow queries from Telescope
     */
    public function getSlowQueries(int $days = 7, int $limit = 50): Collection
    {
        return DB::table('telescope_entries')
            ->where('type', 'query')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2)) >= ?", [$this->slowQueryThreshold])
            ->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2)) DESC")
            ->limit($limit)
            ->get()
            ->map(function ($entry) {
                $content = json_decode($entry->content, true);
                $time = (float) ($content['time'] ?? 0);
                return [
                    'id' => $entry->uuid,
                    'sql' => $content['sql'] ?? 'N/A',
                    'time' => round($time, 2),
                    'connection' => $content['connection'] ?? 'mysql',
                    'created_at' => $entry->created_at,
                    'is_very_slow' => $time >= $this->verySlowQueryThreshold,
                ];
            });
    }

    /**
     * Get slow requests from Telescope
     */
    public function getSlowRequests(int $days = 7, int $limit = 50): Collection
    {
        return DB::table('telescope_entries')
            ->where('type', 'request')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.duration')) AS DECIMAL(10,2)) >= ?", [$this->slowRequestThreshold])
            ->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.duration')) AS DECIMAL(10,2)) DESC")
            ->limit($limit)
            ->get()
            ->map(function ($entry) {
                $content = json_decode($entry->content, true);
                return [
                    'id' => $entry->uuid,
                    'method' => $content['method'] ?? 'GET',
                    'uri' => $content['uri'] ?? 'N/A',
                    'duration' => (int) ($content['duration'] ?? 0),
                    'status' => $content['response_status'] ?? 200,
                    'controller_action' => $content['controller_action'] ?? 'N/A',
                    'created_at' => $entry->created_at,
                ];
            });
    }

    /**
     * Get most repeated queries
     */
    public function getMostRepeatedQueries(int $days = 7, int $limit = 20): Collection
    {
        return DB::table('telescope_entries')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.sql')) as query_sql"))
            ->selectRaw('COUNT(*) as count')
            ->selectRaw("AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2))) as avg_time")
            ->selectRaw("MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2))) as max_time")
            ->selectRaw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2))) as total_time")
            ->where('type', 'query')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.sql'))"))
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'sql' => $row->query_sql,
                    'count' => (int) $row->count,
                    'avg_time' => round((float) $row->avg_time, 2),
                    'max_time' => round((float) $row->max_time, 2),
                    'total_time' => round((float) $row->total_time, 2),
                ];
            });
    }

    /**
     * Get performance summary
     */
    public function getSummary(int $days = 7): array
    {
        $startDate = Carbon::now()->subDays($days);

        // Total queries
        $totalQueries = DB::table('telescope_entries')
            ->where('type', 'query')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Slow queries count
        $slowQueriesCount = DB::table('telescope_entries')
            ->where('type', 'query')
            ->where('created_at', '>=', $startDate)
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2)) >= ?", [$this->slowQueryThreshold])
            ->count();

        // Very slow queries count
        $verySlowQueriesCount = DB::table('telescope_entries')
            ->where('type', 'query')
            ->where('created_at', '>=', $startDate)
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2)) >= ?", [$this->verySlowQueryThreshold])
            ->count();

        // Total requests
        $totalRequests = DB::table('telescope_entries')
            ->where('type', 'request')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Slow requests count
        $slowRequestsCount = DB::table('telescope_entries')
            ->where('type', 'request')
            ->where('created_at', '>=', $startDate)
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.duration')) AS DECIMAL(10,2)) >= ?", [$this->slowRequestThreshold])
            ->count();

        // Average query time
        $avgQueryTime = DB::table('telescope_entries')
            ->where('type', 'query')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.time')) AS DECIMAL(10,2))) as avg")
            ->value('avg');

        // Average request duration
        $avgRequestDuration = DB::table('telescope_entries')
            ->where('type', 'request')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.duration')) AS DECIMAL(10,2))) as avg")
            ->value('avg');

        // Exceptions count
        $exceptionsCount = DB::table('telescope_entries')
            ->where('type', 'exception')
            ->where('created_at', '>=', $startDate)
            ->count();

        return [
            'period_days' => $days,
            'total_queries' => $totalQueries,
            'slow_queries' => $slowQueriesCount,
            'very_slow_queries' => $verySlowQueriesCount,
            'slow_queries_percentage' => $totalQueries > 0
                ? round(($slowQueriesCount / $totalQueries) * 100, 2)
                : 0,
            'total_requests' => $totalRequests,
            'slow_requests' => $slowRequestsCount,
            'slow_requests_percentage' => $totalRequests > 0
                ? round(($slowRequestsCount / $totalRequests) * 100, 2)
                : 0,
            'avg_query_time_ms' => round((float) $avgQueryTime, 2),
            'avg_request_duration_ms' => round((float) $avgRequestDuration, 2),
            'exceptions_count' => $exceptionsCount,
            'thresholds' => [
                'slow_query' => $this->slowQueryThreshold,
                'very_slow_query' => $this->verySlowQueryThreshold,
                'slow_request' => $this->slowRequestThreshold,
            ],
        ];
    }

    /**
     * Get slowest endpoints
     */
    public function getSlowestEndpoints(int $days = 7, int $limit = 20): Collection
    {
        return DB::table('telescope_entries')
            ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.uri')) as uri"))
            ->addSelect(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.method')) as method"))
            ->selectRaw('COUNT(*) as request_count')
            ->selectRaw("AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.duration')) AS DECIMAL(10,2))) as avg_duration")
            ->selectRaw("MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(content, '$.duration')) AS DECIMAL(10,2))) as max_duration")
            ->where('type', 'request')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.uri'))"), DB::raw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.method'))"))
            ->orderByDesc('avg_duration')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'uri' => $row->uri,
                    'method' => $row->method,
                    'request_count' => (int) $row->request_count,
                    'avg_duration' => round((float) $row->avg_duration, 2),
                    'max_duration' => round((float) $row->max_duration, 2),
                ];
            });
    }

    /**
     * Generate full performance report
     */
    public function generateReport(int $days = 7): array
    {
        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'summary' => $this->getSummary($days),
            'slow_queries' => $this->getSlowQueries($days, 20)->toArray(),
            'slow_requests' => $this->getSlowRequests($days, 20)->toArray(),
            'repeated_queries' => $this->getMostRepeatedQueries($days, 15)->toArray(),
            'slowest_endpoints' => $this->getSlowestEndpoints($days, 15)->toArray(),
        ];
    }
}
