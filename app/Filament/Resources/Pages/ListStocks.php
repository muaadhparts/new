<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Actions\DownloadStock;
use App\Actions\ImportStock;
use App\Filament\Resources\StockResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('updateStock')
                ->label('Update Stock')
                ->requiresConfirmation()
                ->action(function () {
                    $path  = DownloadStock::run();
                    $count = ImportStock::run();

                    // dd('UI Update Stock', $path, $count); // ← فحص سريع

                    Notification::make()
                        ->success()
                        ->title('Stock updated')
                        ->body("File: {$path}\nRows imported: {$count}")
                        ->send();
                }),
        ];
    }
}
