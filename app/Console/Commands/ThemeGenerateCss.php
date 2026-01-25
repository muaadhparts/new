<?php

namespace App\Console\Commands;

use App\Domain\Platform\Services\ThemeService;
use Illuminate\Console\Command;

/**
 * Artisan command to regenerate theme CSS from database settings.
 * Delegates all work to ThemeService (single source of truth).
 */
class ThemeGenerateCss extends Command
{
    protected $signature = 'theme:generate-css';
    protected $description = 'Regenerate theme-colors.css from database settings';

    public function handle(ThemeService $themeService): int
    {
        $this->info('Regenerating theme-colors.css...');

        $cssPath = $themeService->generateCss();
        $settings = $themeService->getAll();

        $this->info("Generated theme-colors.css successfully!");
        $this->line("  Path: {$cssPath}");
        $this->line("  Primary: {$settings['theme_primary']}");

        return Command::SUCCESS;
    }
}
