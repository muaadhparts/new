<?php

namespace App\Services\GlobalData\Contexts;

use App\Models\StaticContent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * FooterContext
 *
 * بيانات الفوتر:
 * - صفحات الفوتر
 * - روابط التواصل الاجتماعي
 */
class FooterContext implements ContextInterface
{
    private $footerPages = null;
    private $socialLinks = null;

    public function load(): void
    {
        $this->footerPages = Cache::remember('footer_pages', 3600, fn() =>
            StaticContent::where('footer', 1)->get()
        );

        $this->socialLinks = Cache::remember('footer_social_links', 3600, fn() =>
            DB::table('social_links')
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
