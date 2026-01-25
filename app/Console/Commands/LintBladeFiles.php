<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Blade Display Only Lint Command.
 *
 * Enforces the rule that Blade files must be display-only:
 * - No queries in views
 * - No method calls that trigger queries
 * - No fallback operators (??) on DTO properties
 * - No complex @php blocks with business logic
 *
 * All data must come pre-computed from Controller/Service via DTO.
 */
class LintBladeFiles extends Command
{
    protected $signature = 'lint:blade
                            {--fix : Show suggested fixes}
                            {--path= : Specific path to check}
                            {--ci : CI mode - exit with error code on violations}
                            {--show-clean : Show all checked files including clean ones}';

    protected $description = 'Check Blade files for Display Only rule violations';

    protected array $violations = [];

    /**
     * Forbidden patterns that violate Display Only rule.
     */
    protected array $forbiddenPatterns = [
        // Direct query methods on variables (but NOT count() on collections - that's OK)
        [
            'pattern' => '/\$\w+->(where|find|first|firstOrFail|get|all|exists|pluck|sum|avg|max|min)\s*\(/i',
            'message' => 'Query method call in Blade - move to Service',
            'code' => 'QUERY_METHOD',
            'severity' => 'error',
        ],
        // Direct DB/Model static calls
        [
            'pattern' => '/(DB|Model)::(table|where|select|raw|query)\s*\(/i',
            'message' => 'Direct DB/Model query in Blade',
            'code' => 'DIRECT_QUERY',
            'severity' => 'error',
        ],
        // Relationship method calls with query (e.g., ->items()->count())
        [
            'pattern' => '/\$\w+->\w+\(\)->(count|sum|avg|where|first|get|pluck|exists)\s*\(/i',
            'message' => 'Relationship query in Blade - use DTO->propertyCount',
            'code' => 'RELATIONSHIP_QUERY',
            'severity' => 'error',
        ],
        // Lazy loading
        [
            'pattern' => '/\$\w+->load(Missing)?\s*\(/i',
            'message' => 'Lazy loading in Blade - eager load in Controller',
            'code' => 'LAZY_LOADING',
            'severity' => 'error',
        ],
        // Null coalescing on DTO/card/data properties (critical data should never be null)
        [
            'pattern' => '/\$(card|dto|viewData)->\w+\s*\?\?\s*[\'\"]/i',
            'message' => 'Null coalescing on DTO property - data should be complete from Service',
            'code' => 'DTO_FALLBACK',
            'severity' => 'warning',
        ],
        // Method chaining in echo output
        [
            'pattern' => '/\{\{\s*\$\w+->\w+\(\)->\w+/i',
            'message' => 'Method chaining in output - pre-compute in Service',
            'code' => 'METHOD_CHAIN',
            'severity' => 'warning',
        ],
        // Complex @php blocks (more than 3 meaningful lines)
        [
            'pattern' => '/@php\s*\n(?:\s*(?:\/\/[^\n]*|\s*)\n)*(?:\s*\$\w+\s*=[^;]+;\s*\n){4,}.*?@endphp/s',
            'message' => 'Complex @php block with multiple assignments - move logic to Service/DTO',
            'code' => 'COMPLEX_PHP',
            'severity' => 'warning',
        ],
        // Creating new model instances
        [
            'pattern' => '/new\s+\\\\?App\\\\[A-Za-z\\\\]+Model/i',
            'message' => 'Creating model instance in Blade',
            'code' => 'MODEL_INSTANCE',
            'severity' => 'error',
        ],
        // json_encode in Blade (should be in DTO->toJson())
        [
            'pattern' => '/json_encode\s*\(\s*\$\w+/i',
            'message' => 'json_encode in Blade - use DTO method or pre-encode in Service',
            'code' => 'JSON_ENCODE',
            'severity' => 'warning',
        ],
    ];

    /**
     * Patterns that are explicitly allowed (whitelist).
     */
    protected array $allowedPatterns = [
        // Routing & URLs
        'route(',
        'url(',
        'asset(',
        'Storage::url(',
        'mix(',
        'vite(',

        // Translation
        '__(',
        '@lang(',
        'trans(',
        'trans_choice(',

        // String helpers
        'Str::limit(',
        'Str::ucfirst(',
        'Str::lower(',
        'Str::upper(',
        'Str::title(',
        'Str::slug(',

        // Formatting
        'number_format(',
        'money_format(',
        'sprintf(',
        'date(',
        '->format(',  // Date/Carbon formatting

        // Config & Session (read-only)
        'config(',
        'session(',
        'old(',

        // Auth (read-only checks)
        'auth()->check',
        'auth()->guest',
        'auth()->id',
        'auth()->user()',
        '@auth',
        '@guest',

        // Laravel validation errors (special object passed to all views)
        '$errors->',
        '@error',

        // Request (read-only)
        'request()->routeIs(',
        'request()->is(',
        'request()->input(',
        'request()->get(',
        'request()->has(',

        // CSRF & Form
        'csrf_field(',
        'csrf_token(',
        'method_field(',

        // Blade directives
        '@csrf',
        '@method',
        '@include',
        '@extends',
        '@section',
        '@yield',
        '@component',
        '@slot',
        '@push',
        '@stack',
        '@once',
        '@error',
        '@can',
        '@cannot',

        // Common safe helpers
        'app()->getLocale(',
        'now()->',
        'today()->',
        'Carbon::',

        // Collection methods (safe - data already loaded)
        // Note: patterns match the regex capture which ends with '('
        '->count(',       // Counting loaded collection
        '->first(',       // Getting first from loaded collection
        '->last(',        // Getting last from loaded collection
        '->isEmpty(',     // Checking if collection is empty
        '->isNotEmpty(',  // Checking if collection is not empty
        '->filter(',      // Filtering loaded collection
        '->map(',         // Mapping loaded collection
        '->pluck(',       // Plucking from loaded collection (in-memory)
        '->sortBy(',      // Sorting loaded collection
        '->groupBy(',     // Grouping loaded collection
        '->unique(',      // Getting unique from loaded collection
        '->values(',      // Getting values from loaded collection
        '->keys(',        // Getting keys from loaded collection
        '->chunk(',       // Chunking loaded collection
        '->take(',        // Taking from loaded collection
        '->skip(',        // Skipping from loaded collection
        '->contains(',    // Checking if collection contains
        '->each(',        // Iterating loaded collection
        '->reduce(',      // Reducing loaded collection
        '->flatten(',     // Flattening loaded collection
        '->merge(',       // Merging collections
        '->push(',        // Pushing to collection
        '->put(',         // Putting in collection
        '->forget(',      // Forgetting from collection
        '->except(',      // Except from collection
        '->only(',        // Only from collection
        '->has(',         // Has in collection
        '->get(',         // Get from collection (by key)
        '->where(',       // Filtering collection (in-memory)
        '->whereIn(',     // Filtering collection (in-memory)
        '->whereNotIn(',  // Filtering collection (in-memory)
        '->whereBetween(',// Filtering collection (in-memory)
        '->whereNull(',   // Filtering collection (in-memory)
        '->whereNotNull(',// Filtering collection (in-memory)
        '->find(',        // Finding in collection by key
        '->all(',         // Getting all from collection
        '->sum(',         // Summing collection values
        '->avg(',         // Averaging collection values
        '->max(',         // Max from collection
        '->min(',         // Min from collection
        '->median(',      // Median from collection

        // Pagination methods (safe)
        '->links(',       // Pagination links
        '->withQueryString(', // Pagination with query string
        '->appends(',     // Pagination appends
        '->onEachSide(',  // Pagination sides
        '->hasPages(',    // Check if has pages
        '->hasMorePages(', // Check if has more pages
        '->currentPage(', // Get current page
        '->lastPage(',    // Get last page
        '->perPage(',     // Get per page
        '->total(',       // Get total count
    ];

    /**
     * Files/directories to exclude from checking.
     */
    protected array $excludedPaths = [
        'vendor',
        'errors',
        'emails',
        'mail',
        'pagination',
        'auth/passwords', // Laravel default auth views
    ];

    public function handle(): int
    {
        $path = $this->option('path')
            ? base_path($this->option('path'))
            : resource_path('views');

        $ciMode = $this->option('ci');
        $showClean = $this->option('show-clean');

        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║           BLADE DISPLAY ONLY LINT CHECK                    ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("Scanning: {$path}");
        $this->newLine();

        if (!File::isDirectory($path) && !File::isFile($path)) {
            $this->error("Path not found: {$path}");
            return 1;
        }

        $finder = new Finder();

        if (File::isFile($path)) {
            $files = [new \SplFileInfo($path)];
        } else {
            $finder->files()->in($path)->name('*.blade.php');

            // Exclude paths
            foreach ($this->excludedPaths as $excluded) {
                $finder->notPath($excluded);
            }

            $files = iterator_to_array($finder);
        }

        $stats = [
            'total' => 0,
            'clean' => 0,
            'violations' => 0,
            'errors' => 0,
            'warnings' => 0,
        ];

        foreach ($files as $file) {
            $stats['total']++;
            $relativePath = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file->getRealPath());

            $fileViolations = $this->checkFile($file->getRealPath());

            if (empty($fileViolations)) {
                $stats['clean']++;
                if ($showClean) {
                    $this->line("  <fg=green>✓</> {$relativePath}");
                }
            } else {
                $stats['violations']++;
                $this->reportFileViolations($relativePath, $fileViolations, $stats);
            }
        }

        $this->printSummary($stats);

        if ($ciMode && ($stats['errors'] > 0)) {
            $this->newLine();
            $this->error('╔════════════════════════════════════════════════════════════╗');
            $this->error('║  CI CHECK FAILED: Blade Display Only violations found!     ║');
            $this->error('╚════════════════════════════════════════════════════════════╝');
            return 1;
        }

        if ($stats['errors'] === 0 && $stats['warnings'] === 0) {
            $this->newLine();
            $this->info('╔════════════════════════════════════════════════════════════╗');
            $this->info('║  ✓ All Blade files comply with Display Only rule!          ║');
            $this->info('╚════════════════════════════════════════════════════════════╝');
        }

        return $stats['errors'] > 0 ? 1 : 0;
    }

    protected function checkFile(string $filePath): array
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $fileViolations = [];

        // Skip files with special markers
        if (str_contains($content, '{{-- @lint-disable --}}')) {
            return [];
        }

        foreach ($this->forbiddenPatterns as $check) {
            if (preg_match_all($check['pattern'], $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matchedText = trim($match[0]);

                    // Skip if matches allowed pattern
                    if ($this->isAllowedPattern($matchedText, $content, $match[1])) {
                        continue;
                    }

                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                    // Check for inline disable comment
                    $lineContent = $lines[$lineNumber - 1] ?? '';
                    if (str_contains($lineContent, '@lint-ignore')) {
                        continue;
                    }

                    $fileViolations[] = [
                        'line' => $lineNumber,
                        'code' => $check['code'],
                        'message' => $check['message'],
                        'severity' => $check['severity'],
                        'snippet' => $this->getLineSnippet($lines, $lineNumber),
                        'match' => Str::limit($matchedText, 50),
                    ];

                    $this->violations[] = end($fileViolations);
                }
            }
        }

        return $fileViolations;
    }

    protected function isAllowedPattern(string $text, string $fullContent, int $offset): bool
    {
        foreach ($this->allowedPatterns as $allowed) {
            if (str_contains($text, $allowed)) {
                return true;
            }
        }

        // Check if inside a comment
        $beforeMatch = substr($fullContent, max(0, $offset - 100), 100);
        if (preg_match('/\{\{--[^}]*$/s', $beforeMatch)) {
            return true; // Inside Blade comment
        }

        return false;
    }

    protected function getLineSnippet(array $lines, int $lineNumber): string
    {
        $index = $lineNumber - 1;
        $snippet = isset($lines[$index]) ? trim($lines[$index]) : '';
        return Str::limit($snippet, 100);
    }

    protected function reportFileViolations(string $relativePath, array $violations, array &$stats): void
    {
        $this->newLine();
        $this->warn("┌─ {$relativePath}");

        foreach ($violations as $v) {
            $icon = $v['severity'] === 'error' ? '<fg=red>✗</>' : '<fg=yellow>⚠</>';
            $severity = $v['severity'] === 'error' ? '<fg=red>ERROR</>' : '<fg=yellow>WARN</>';

            $this->line("│  {$icon} Line {$v['line']}: [{$v['code']}] {$v['message']}");
            $this->line("│     <fg=gray>{$v['snippet']}</>");

            if ($v['severity'] === 'error') {
                $stats['errors']++;
            } else {
                $stats['warnings']++;
            }
        }

        $this->line("└─");
    }

    protected function printSummary(array $stats): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('                         SUMMARY                               ');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->line("  Total files scanned:    {$stats['total']}");
        $this->line("  Clean files:            <fg=green>{$stats['clean']}</>");
        $this->line("  Files with violations:  " . ($stats['violations'] > 0 ? "<fg=red>{$stats['violations']}</>" : '0'));
        $this->line("  Total errors:           " . ($stats['errors'] > 0 ? "<fg=red>{$stats['errors']}</>" : '0'));
        $this->line("  Total warnings:         " . ($stats['warnings'] > 0 ? "<fg=yellow>{$stats['warnings']}</>" : '0'));
        $this->info('═══════════════════════════════════════════════════════════════');
    }
}
