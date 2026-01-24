<?php

namespace App\Domain\Catalog\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * Cleanup Images Command
 *
 * Removes orphaned images not linked to any product.
 */
class CleanupImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'catalog:cleanup-images
                            {--dry-run : Show what would be deleted without deleting}
                            {--disk=public : Storage disk to clean}';

    /**
     * The console command description.
     */
    protected $description = 'Remove orphaned product images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = $this->option('disk');
        $dryRun = $this->option('dry-run');

        $this->info('Scanning for orphaned images...');

        // Get all images in use
        $usedImages = $this->getUsedImages();
        $this->info('Found ' . count($usedImages) . ' images in use.');

        // Get all images on disk
        $diskImages = Storage::disk($disk)->files('products');
        $this->info('Found ' . count($diskImages) . ' images on disk.');

        // Find orphaned images
        $orphaned = array_diff($diskImages, $usedImages);
        $this->info('Found ' . count($orphaned) . ' orphaned images.');

        if (empty($orphaned)) {
            $this->info('No orphaned images to clean up.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry run mode - no files will be deleted.');
            $this->table(['Orphaned Images'], array_map(fn($i) => [$i], $orphaned));
            return self::SUCCESS;
        }

        if (!$this->confirm('Delete ' . count($orphaned) . ' orphaned images?')) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphaned as $image) {
            if (Storage::disk($disk)->delete($image)) {
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} orphaned images.");

        return self::SUCCESS;
    }

    /**
     * Get all images currently in use
     */
    protected function getUsedImages(): array
    {
        $images = [];

        // Catalog item images
        CatalogItem::whereNotNull('images')
            ->pluck('images')
            ->each(function ($itemImages) use (&$images) {
                $decoded = is_string($itemImages) ? json_decode($itemImages, true) : $itemImages;
                if (is_array($decoded)) {
                    $images = array_merge($images, $decoded);
                }
            });

        // Merchant item images
        MerchantItem::whereNotNull('images')
            ->pluck('images')
            ->each(function ($itemImages) use (&$images) {
                $decoded = is_string($itemImages) ? json_decode($itemImages, true) : $itemImages;
                if (is_array($decoded)) {
                    $images = array_merge($images, $decoded);
                }
            });

        return array_unique($images);
    }
}
