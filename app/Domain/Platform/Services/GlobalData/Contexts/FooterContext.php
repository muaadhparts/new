<?php

namespace App\Domain\Platform\Services\GlobalData\Contexts;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * FooterContext
 *
 * بيانات الفوتر:
 * - Policy pages (terms, privacy, refund)
 * - روابط التواصل الاجتماعي
 */
class FooterContext implements ContextInterface
{
    private $footerPages = null;
    private $socialLinks = null;

    public function load(): void
    {
        // Policy pages for footer links
        $this->footerPages = Cache::remember('footer_pages', 3600, fn() =>
            Page::where('is_active', true)->get()
        );

        $this->socialLinks = Cache::remember('footer_network_presences', 3600, fn() =>
            DB::table('network_presences')
                ->where('user_id', 0)
                ->where('status', 1)
                ->get()
        );
    }

    public function toArray(): array
    {
        return [
            'footerPages' => $this->footerPages,
            'socialLinks' => $this->socialLinks,
        ];
    }

    public function reset(): void
    {
        $this->footerPages = null;
        $this->socialLinks = null;
    }

    // === Getters ===

    public function getFooterPages()
    {
        return $this->footerPages;
    }

    public function getSocialLinks()
    {
        return $this->socialLinks;
    }
}
