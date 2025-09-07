<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class DownloadStockalibates
{
    use AsAction;

    public string $commandSignature = 'download:stock';
    public string $commandDescription = 'Download today\'s stock CSV files from Spaces';

    // public function handle(): void
    // {
    //     // يبني المسار باستخدام دالة space_path (نفس المشروع القديم)
    //     $files = Storage::disk('s3')->allFiles(space_path('ATWJRY'));

    //     $stocks = [];
    //     foreach ($files as $file) {
    //         if (Str::contains(basename($file), '.csv')) {
    //             $localPath = "stock/csv/" . basename($file);

    //             $isDownloaded = Storage::disk('local')->put(
    //                 $localPath,
    //                 Storage::disk('s3')->get($file)
    //             );

    //             $stocks[basename($file)] = $isDownloaded;
    //         }
    //     }

    //     Log::info('DownloadStock', $stocks);
    //     // dd($stocks); // ← للتجربة
    // }
    public function handle(): array
    {
        $files = Storage::disk('s3')->allFiles(space_path('alibates'));

        $stocks = [];
        foreach ($files as $file) {
            if (Str::contains(basename($file), '.csv')) {
                $localPath = "stock/csv/" . basename($file);

                $isDownloaded = Storage::disk('local')->put(
                    $localPath,
                    Storage::disk('s3')->get($file)
                );

                $stocks[basename($file)] = $isDownloaded;
            }
        }

        Log::info('DownloadStock', $stocks);

        return $stocks; // ✅ مهم: رجّع الملفات
    }


    public function asCommand(Command $command)
    {
        $this->handle();
        $command->info('Done');
    }
}
