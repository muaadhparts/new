<?php

namespace App\Domain\Catalog\Schedule;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup Orphaned Images Task
 *
 * Removes images that are no longer referenced by any catalog item.
 */
class CleanupOrphanedImagesTask
{
    /**
     * Execute the task.
     */
    public function __invoke(): void
    {
        $directory = 'catalog-items';

        if (!Storage::disk('public')->exists($directory)) {
            Log::info('Catalog images directory does not exist', ['directory' => $directory]);
            return;
        }

        $files = Storage::disk('public')->files($directory);

        // Get all referenced images from database
        $referencedImages = DB::table('catalog_items')
            ->whereNotNull('photo')
            ->pluck('photo')
            ->map(fn ($path) => basename($path))
            ->toArray();

        $deleted = 0;
        $kept = 0;

        foreach ($files as $file) {
            $filename = basename($file);

            if (!in_array($filename, $referencedImages)) {
                Storage::disk('public')->delete($file);
                $deleted++;
            } else {
                $kept++;
            }
        }

        Log::info('Orphaned images cleanup completed', [
            'total_files' => count($files),
            'deleted' => $deleted,
            'kept' => $kept,
        ]);
    }

    /**
     * Get the schedule frequency.
     */
    public static function frequency(): string
    {
        return 'weekly';
    }
}
