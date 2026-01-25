<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Support\Facades\Event;

/**
 * SQL Safety Check - Prevents destructive database operations.
 *
 * This listener blocks:
 * - DROP DATABASE
 * - DROP TABLE (without _backup or _old suffix)
 * - TRUNCATE TABLE
 * - DELETE FROM without WHERE clause
 */
class SqlSafetyCheck
{
    /**
     * Forbidden SQL patterns that should NEVER be executed.
     */
    protected array $forbiddenPatterns = [
        // DROP DATABASE - Always forbidden
        '/^\s*DROP\s+DATABASE/i' => 'DROP DATABASE is absolutely forbidden',

        // DROP TABLE - Forbidden unless it's a backup/temp table
        '/^\s*DROP\s+TABLE\s+(?!.*(_backup|_old|_temp|_bak)\b)/i' => 'DROP TABLE is forbidden (only _backup/_old/_temp tables can be dropped)',

        // TRUNCATE TABLE - Always forbidden
        '/^\s*TRUNCATE\s+(TABLE\s+)?/i' => 'TRUNCATE TABLE is forbidden - use DELETE with WHERE instead',

        // DELETE without WHERE - Forbidden
        '/^\s*DELETE\s+FROM\s+\S+\s*$/i' => 'DELETE without WHERE clause is forbidden',
        '/^\s*DELETE\s+FROM\s+\S+\s*;?\s*$/i' => 'DELETE without WHERE clause is forbidden',
    ];

    /**
     * Check if SQL is safe to execute.
     *
     * @param string $sql
     * @return array ['safe' => bool, 'reason' => string|null]
     */
    public function check(string $sql): array
    {
        $sql = trim($sql);

        foreach ($this->forbiddenPatterns as $pattern => $reason) {
            if (preg_match($pattern, $sql)) {
                return [
                    'safe' => false,
                    'reason' => $reason,
                    'sql' => substr($sql, 0, 100) . (strlen($sql) > 100 ? '...' : ''),
                ];
            }
        }

        return ['safe' => true, 'reason' => null];
    }

    /**
     * Register the safety listener.
     */
    public static function register(): void
    {
        $checker = new self();

        // Listen to all raw SQL statements
        DB::listen(function ($query) use ($checker) {
            $result = $checker->check($query->sql);

            if (!$result['safe']) {
                // Log the blocked query
                \Log::critical('BLOCKED DESTRUCTIVE SQL QUERY', [
                    'sql' => $result['sql'],
                    'reason' => $result['reason'],
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                ]);

                throw new \RuntimeException(
                    "BLOCKED: {$result['reason']}\n" .
                    "SQL: {$result['sql']}\n" .
                    "This operation is forbidden by SqlSafetyCheck."
                );
            }
        });
    }
}
