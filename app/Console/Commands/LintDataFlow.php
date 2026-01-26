<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Data Flow Policy Linter
 *
 * Enforces strict data flow rules:
 * - Controllers must pass DTOs to views (not Models)
 * - Views must only display data (no queries, no logic)
 * - Helpers must not contain queries or business logic
 */
class LintDataFlow extends Command
{
    protected $signature = 'lint:dataflow
                            {--layer= : Check specific layer (controller|view|helper|all)}
                            {--path= : Specific file or directory to check}
                            {--ci : Exit with error code on violations}
                            {--fix : Show fix suggestions}
                            {--summary : Show summary only}';

    protected $description = 'Enforce Data Flow Policy compliance across all layers';

    protected array $violations = [];
    protected int $filesScanned = 0;

    // Layer-specific forbidden patterns
    protected array $patterns = [
        'controller' => [
            [
                'pattern' => '/return\s+view\s*\([^)]+,\s*compact\s*\(/i',
                'message' => 'compact() may pass Models to View - consider explicit DTO array',
                'code' => 'CTRL_COMPACT',
                'severity' => 'warning',
                'fix' => "return view('name', ['dto' => \$dto])",
            ],
            [
                'pattern' => '/return\s+view\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*\[\s*[\'"](?!dto|card|data|items|breadcrumbs|pagination|errors|meta|page|tracking|query|count|cards|datas|shipmentLogs|contactEnabled)\w+[\'"]\s*=>\s*\$(?!dto|DTO|card|data|items|page|tracking|homePageDTO|catalogsPageDTO|trackingData|query|count|cards)\w+/i',
                'message' => 'Variable name suggests Model (not DTO) passed to view',
                'code' => 'CTRL_MODEL_VAR',
                'severity' => 'warning',
                'fix' => 'Rename to $dto, $card, or $data prefix',
            ],
            [
                'pattern' => '/\$\w+\s*=\s*\w+::(where|find|first|with|select)\s*\(/i',
                'message' => 'Direct Eloquent query in Controller - use Service/Repository',
                'code' => 'CTRL_DIRECT_QUERY',
                'severity' => 'warning',
                'fix' => 'Move query to Service: $this->service->findBy(...)',
            ],
        ],

        'view' => [
            // Queries - exclude count/first/pluck on collections (non-query usage)
            [
                'pattern' => '/\$\w+->(where|find|get|exists|sum|avg|max|min)\s*\(/i',
                'message' => 'Query method in Blade - pre-compute in Service',
                'code' => 'VIEW_QUERY',
                'severity' => 'error',
                'fix' => 'Use $dto->propertyName (pre-computed)',
            ],
            // Static query methods on Models
            [
                'pattern' => '/\b[A-Z][a-z]\w+::(where|find|first|all)\s*\(/i',
                'message' => 'Static Model query in Blade - pre-compute in Service',
                'code' => 'VIEW_STATIC_QUERY',
                'severity' => 'error',
                'fix' => 'Move to Service, pass result via DTO',
            ],
            [
                'pattern' => '/\$\w+->load\s*\(/i',
                'message' => 'Lazy loading in Blade - eager load in Controller',
                'code' => 'VIEW_LAZY_LOAD',
                'severity' => 'error',
                'fix' => 'Eager load in Controller: ->with([...])',
            ],
            [
                'pattern' => '/(DB|Model)::(table|where|select|raw)\s*\(/i',
                'message' => 'Direct DB/Model query in Blade',
                'code' => 'VIEW_DIRECT_DB',
                'severity' => 'error',
                'fix' => 'Move to Service, pass result via DTO',
            ],

            // Deep access (Model relations)
            [
                'pattern' => '/\{\{\s*\$(?!loop|errors|slot|attributes|__env)\w+->\w+->\w+/i',
                'message' => 'Deep property chain (possible Model relation) - flatten in DTO',
                'code' => 'VIEW_DEEP_ACCESS',
                'severity' => 'warning',
                'fix' => 'Use $dto->flatProperty instead of $model->relation->property',
            ],

            // Fallbacks hiding bugs
            [
                'pattern' => '/\{\{\s*\$(?!old|errors|session)\w+\s*\?\?\s*[\'"][^\'"]+[\'"]\s*\}\}/i',
                'message' => 'Null coalescing fallback - DTO should provide complete data',
                'code' => 'VIEW_FALLBACK',
                'severity' => 'warning',
                'fix' => 'Ensure Service/DTO provides non-null value',
            ],
            [
                'pattern' => '/\{\{\s*\$\w+->\w+\s*\?\:\s*[\'"][^\'"]+[\'"]\s*\}\}/i',
                'message' => 'Elvis operator fallback - DTO should provide complete data',
                'code' => 'VIEW_ELVIS_FALLBACK',
                'severity' => 'warning',
                'fix' => 'Ensure Service/DTO provides non-empty value',
            ],

            // Logic in views
            [
                'pattern' => '/@php\s*\n(?:[^\n]*\n){4,}.*?@endphp/s',
                'message' => 'Large @php block (5+ lines) - move logic to Service',
                'code' => 'VIEW_LARGE_PHP',
                'severity' => 'error',
                'fix' => 'Move calculations/logic to Service, pass result via DTO',
            ],
            [
                'pattern' => '/@php[^@]*(?:\$\w+\s*=\s*\$\w+\s*[\+\-\*\/]|\bif\b|\bforeach\b|\bwhile\b)[^@]*@endphp/is',
                'message' => 'Logic/calculations in @php block',
                'code' => 'VIEW_PHP_LOGIC',
                'severity' => 'warning',
                'fix' => 'Move to Service method',
            ],

            // Formatting in views
            [
                'pattern' => '/number_format\s*\(\s*\$[^,]+,/i',
                'message' => 'Number formatting in Blade - use pre-formatted DTO property',
                'code' => 'VIEW_NUMBER_FORMAT',
                'severity' => 'warning',
                'fix' => 'Use $dto->formattedPrice (pre-formatted in Service)',
            ],
            [
                'pattern' => '/json_encode\s*\(\s*\$/i',
                'message' => 'JSON encoding in Blade - pre-encode in Controller/DTO',
                'code' => 'VIEW_JSON_ENCODE',
                'severity' => 'warning',
                'fix' => 'Use $dto->toJson() or @json($dto)',
            ],
        ],

        'helper' => [
            [
                'pattern' => '/DB::(table|select|raw|statement)\s*\(/i',
                'message' => 'Direct DB query in Helper - use Repository',
                'code' => 'HELPER_DB_QUERY',
                'severity' => 'error',
                'fix' => 'Create Repository method for this query',
            ],
            [
                'pattern' => '/\w+::(where|find|first|get|all|create|update)\s*\(/i',
                'message' => 'Eloquent query in Helper - use Service',
                'code' => 'HELPER_ELOQUENT',
                'severity' => 'error',
                'fix' => 'Move to Domain Service',
            ],
            [
                'pattern' => '/function\s+\w+\s*\([^)]*\)\s*(?::\s*\w+\s*)?\{[^}]*(?:foreach|while|for)\s*\([^}]*\}/is',
                'message' => 'Complex logic in Helper - belongs in Service',
                'code' => 'HELPER_COMPLEX',
                'severity' => 'warning',
                'fix' => 'Move to dedicated Service class',
            ],
        ],
    ];

    // Files/patterns to exclude
    protected array $exclude = [
        '*/vendor/*',
        '*/node_modules/*',
        '*/.git/*',
        '*/storage/*',
        '*/tests/*',
    ];

    // Allowed patterns (exceptions)
    protected array $allowedInView = [
        'route(',
        '__(',
        '@lang(',
        'asset(',
        'url(',
        'Storage::url(',
        'Storage::get(',
        'Str::limit(',
        'Str::ucfirst(',
        'config(',
        'session(',
        'Session::get(',
        'Session::has(',
        'auth()->',
        'Auth::check(',
        'Auth::user(',
        'Auth::id(',
        'request()->is(',
        'request()->routeIs(',
        'old(',
        'csrf_field(',
        'method_field(',
        '->format(',    // Carbon date formatting
        '@json(',       // Blade directive
        'App::getLocale',
        'Cache::get(',
        'Cache::remember(',
        '$loop->',      // Blade loop variable
        '$errors->',    // Validation errors
        '$slot',        // Component slot
        '$attributes',  // Component attributes
    ];

    public function handle(): int
    {
        $layer = $this->option('layer') ?: 'all';
        $customPath = $this->option('path');
        $ciMode = $this->option('ci');
        $showFix = $this->option('fix');
        $summaryOnly = $this->option('summary');

        $this->info('Data Flow Policy Linter');
        $this->info('======================');
        $this->newLine();

        // Scan layers
        if ($layer === 'all' || $layer === 'controller') {
            $path = $customPath ?: app_path('Http/Controllers');
            $this->scanLayer('controller', $path, '*.php');
        }

        if ($layer === 'all' || $layer === 'view') {
            $path = $customPath ?: resource_path('views');
            $this->scanLayer('view', $path, '*.blade.php');
        }

        if ($layer === 'all' || $layer === 'helper') {
            $path = $customPath ?: app_path('Helpers');
            $this->scanLayer('helper', $path, '*.php');
        }

        // Report
        $this->newLine();
        $this->reportSummary();

        if (!$summaryOnly && !empty($this->violations)) {
            $this->newLine();
            $this->reportViolations($showFix);
        }

        // Exit code - only fail CI on errors, not warnings
        $errors = collect($this->violations)->where('severity', 'error')->count();
        if ($ciMode && $errors > 0) {
            $this->newLine();
            $this->error("CI Check FAILED: {$errors} error(s) found!");
            return 1;
        }

        if (empty($this->violations)) {
            $this->newLine();
            $this->info('All files comply with Data Flow Policy!');
        }

        return 0;
    }

    protected function scanLayer(string $layer, string $path, string $pattern): void
    {
        if (!File::exists($path)) {
            $this->warn("Path not found: {$path}");
            return;
        }

        $this->line("Scanning {$layer}: {$path}");

        $finder = new Finder();
        $finder->files()->in($path)->name($pattern);

        foreach ($this->exclude as $excluded) {
            $finder->notPath($excluded);
        }

        foreach ($finder as $file) {
            $this->filesScanned++;
            $this->checkFile($layer, $file->getRealPath(), $file->getRelativePathname());
        }
    }

    protected function checkFile(string $layer, string $filePath, string $relativePath): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);

        // Check for exception annotation
        if (str_contains($content, '@dataflow-exception')) {
            return;
        }

        // For views, strip out content inside <code>, <pre>, <script>, <style> tags
        // to avoid false positives from documentation or embedded JS
        $contentToCheck = $content;
        if ($layer === 'view') {
            $contentToCheck = $this->stripExcludedTags($content);
        }

        foreach ($this->patterns[$layer] ?? [] as $check) {
            if (preg_match_all($check['pattern'], $contentToCheck, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matchedText = trim($match[0]);

                    // Skip if it's an allowed pattern
                    if ($layer === 'view' && $this->isAllowedPattern($matchedText)) {
                        continue;
                    }

                    // Skip collection methods (not DB queries)
                    if ($layer === 'view' && $this->isCollectionMethod($matchedText, $content, $match[1])) {
                        continue;
                    }

                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                    $this->violations[] = [
                        'layer' => $layer,
                        'file' => $relativePath,
                        'line' => $lineNumber,
                        'code' => $check['code'],
                        'severity' => $check['severity'],
                        'message' => $check['message'],
                        'snippet' => Str::limit(trim($lines[$lineNumber - 1] ?? ''), 80),
                        'fix' => $check['fix'] ?? null,
                    ];
                }
            }
        }
    }

    /**
     * Strip content inside <code>, <pre>, <script>, <style> tags
     * Replace with whitespace to preserve line numbers/offsets
     */
    protected function stripExcludedTags(string $content): string
    {
        // List of tags to exclude (documentation/embedded code)
        $tagsToStrip = ['code', 'pre', 'script', 'style'];

        foreach ($tagsToStrip as $tag) {
            // Match opening tag, content, closing tag
            $pattern = '/<' . $tag . '(?:\s[^>]*)?>.*?<\/' . $tag . '>/is';
            $content = preg_replace_callback($pattern, function ($match) {
                // Replace with same-length whitespace to preserve offsets
                return str_repeat(' ', strlen($match[0]));
            }, $content);
        }

        return $content;
    }

    /**
     * Check if a match is a collection method (not a DB query)
     * Collections have ->where(), ->first(), ->get() but they're not queries
     */
    protected function isCollectionMethod(string $matchedText, string $fullContent, int $offset): bool
    {
        // Get surrounding context (100 chars before and after)
        $start = max(0, $offset - 100);
        $context = substr($fullContent, $start, 200);

        // Common collection variable patterns
        $collectionIndicators = [
            'collect(',
            '->pluck(',
            '->map(',
            '->filter(',
            '->reject(',
            '->each(',
            '->keyBy(',
            '->groupBy(',
            '->sortBy(',
            '->unique(',
            '->values(',
            '->keys(',
            '->merge(',
            '->chunk(',
            '->flip(',
            '->collapse(',
            '->flatten(',
            'Collection::',
            '->toArray()',
            '->toJson()',
        ];

        foreach ($collectionIndicators as $indicator) {
            if (str_contains($context, $indicator)) {
                return true;
            }
        }

        // Check if variable is from a foreach (usually collections)
        if (preg_match('/\$(\w+)->(?:where|first|get|sum)\s*\(/', $matchedText, $varMatch)) {
            $varName = $varMatch[1];
            // Check if this variable is from a @foreach (direct or as key => value)
            if (preg_match('/@foreach\s*\([^)]*\s+as\s+(?:\$\w+\s*=>\s*)?\$' . preg_quote($varName) . '\b/', $fullContent)) {
                return true;
            }
            // Also check for simple "as $varName" pattern
            if (preg_match('/@foreach\s*\([^)]*\s+as\s+\$\w+\s*=>\s*\$' . preg_quote($varName) . '\b/', $fullContent)) {
                return true;
            }
        }

        // Check common collection variable names from controller
        if (preg_match('/\$(statuses|items|results|data|list|rows|records|entries)->(?:where|first|get|sum)\s*\(/', $matchedText)) {
            return true;
        }

        return false;
    }

    protected function isAllowedPattern(string $text): bool
    {
        foreach ($this->allowedInView as $allowed) {
            if (str_contains($text, $allowed)) {
                return true;
            }
        }
        return false;
    }

    protected function reportSummary(): void
    {
        $errors = collect($this->violations)->where('severity', 'error')->count();
        $warnings = collect($this->violations)->where('severity', 'warning')->count();

        $byLayer = collect($this->violations)->groupBy('layer')->map->count();

        $this->info('Summary:');
        $this->line("  Files scanned: {$this->filesScanned}");
        $this->line("  Total violations: " . count($this->violations));

        if ($errors > 0) {
            $this->error("  Errors: {$errors}");
        }
        if ($warnings > 0) {
            $this->warn("  Warnings: {$warnings}");
        }

        if ($byLayer->isNotEmpty()) {
            $this->newLine();
            $this->line('  By layer:');
            foreach ($byLayer as $layer => $count) {
                $this->line("    {$layer}: {$count}");
            }
        }
    }

    protected function reportViolations(bool $showFix): void
    {
        $grouped = collect($this->violations)->groupBy('file');

        foreach ($grouped as $file => $fileViolations) {
            $this->warn("File: {$file}");

            foreach ($fileViolations as $v) {
                $severityColor = $v['severity'] === 'error' ? 'red' : 'yellow';
                $severityLabel = strtoupper($v['severity']);

                $this->line("  <fg={$severityColor}>Line {$v['line']}</>: [{$v['code']}] {$v['message']}");
                $this->line("    <fg=gray>> {$v['snippet']}</>");

                if ($showFix && $v['fix']) {
                    $this->line("    <fg=green>Fix: {$v['fix']}</>");
                }
            }

            $this->newLine();
        }
    }
}
