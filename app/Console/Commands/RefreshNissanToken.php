<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Catalog\Models\Token;
use App\Domain\Catalog\Services\NissanTokenService;

class RefreshNissanToken extends Command
{
    protected $signature = 'nissan:refresh-token';
    protected $description = 'Check and refresh Nissan token if about to expire';

    public function handle()
    {
        $bufferMinutes = 15;
        $validToken = Token::where('expires_at', '>', now()->addMinutes($bufferMinutes))->latest()->first();

        if (!$validToken) {
            $newToken = NissanTokenService::refresh();
            if ($newToken) {
                $this->info('Token refreshed successfully.');
            } else {
                $this->error('Failed to refresh token.');
            }
        } else {
            $this->info('Token is still valid for more than ' . $bufferMinutes . ' minutes.');
        }
    }
}
