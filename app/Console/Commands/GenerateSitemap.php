<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\Brand;
use App\Models\Publication;
use Carbon\Carbon;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--type=all : Type of sitemap (all, products, categories, pages, blogs)}';
    protected $description = 'Generate XML sitemaps for SEO - يولد خرائط الموقع للأرشفة';

    public function handle()
    {
        $type = $this->option('type');
        $this->info('Generating sitemap...');

        try {
            if ($type === 'all') {
                $this->generateAllSitemaps();
            } else {
                $this->generateSingleSitemap($type);
            }

            $this->info('Sitemap generated successfully!');
            $this->info('Location: ' . public_path('sitemap.xml'));

        } catch (\Exception $e) {
            $this->error('Error generating sitemap: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function generateAllSitemaps()
    {
        // Create sitemap index for large sites
        $sitemapIndex = SitemapIndex::create();

        // Generate products sitemap
        $this->info('Generating products sitemap...');
        $this->generateProductsSitemap();
        $sitemapIndex->add('/sitemap-products.xml');

        // Generate categories sitemap
        $this->info('Generating categories sitemap...');
        $this->generateCategoriesSitemap();
        $sitemapIndex->add('/sitemap-categories.xml');

        // Generate pages sitemap
        $this->info('Generating pages sitemap...');
        $this->generatePagesSitemap();
        $sitemapIndex->add('/sitemap-pages.xml');

        // Generate blogs sitemap
        $this->info('Generating blogs sitemap...');
        $this->generateBlogsSitemap();
        $sitemapIndex->add('/sitemap-blogs.xml');

        // Write main sitemap index
        $sitemapIndex->writeToFile(public_path('sitemap.xml'));
    }

    protected function generateSingleSitemap($type)
    {
        switch ($type) {
            case 'products':
                $this->generateProductsSitemap();
                break;
            case 'categories':
                $this->generateCategoriesSitemap();
                break;
            case 'pages':
                $this->generatePagesSitemap();
                break;
            case 'blogs':
                $this->generateBlogsSitemap();
                break;
            default:
                $this->error('Unknown sitemap type: ' . $type);
        }
    }

    protected function generateProductsSitemap()
    {
        $sitemap = Sitemap::create();

        // Get catalog items with active merchant items
        $catalogItems = CatalogItem::whereHas('merchantItems', function($q) {
                $q->where('status', 1);
            })
            ->with(['merchantItems' => function($q) {
                $q->where('status', 1)->orderBy('price', 'asc');
            }])
            ->cursor();

        $count = 0;
        foreach ($catalogItems as $item) {
            // Get the cheapest merchant item for the canonical URL
            $merchantItem = $item->merchantItems->first();

            if ($merchantItem) {
                $url = route('front.catalog-item', [
                    'slug' => $item->slug,
                    'merchant_id' => $merchantItem->user_id,
                    'merchant_item_id' => $merchantItem->id
                ]);

                $sitemap->add(
                    Url::create($url)
                        ->setLastModificationDate($item->updated_at ? Carbon::parse($item->updated_at) : Carbon::now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.8)
                );
                $count++;
            }
        }

        $sitemap->writeToFile(public_path('sitemap-products.xml'));
        $this->info("Added {$count} products to sitemap");
    }

    protected function generateCategoriesSitemap()
    {
        $sitemap = Sitemap::create();

        // Add main category page
        $sitemap->add(
            Url::create(route('front.catalog'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.9)
        );

        // Add brand/category pages
        $brands = Brand::where('status', 1)
            ->whereHas('catalogItems')
            ->get();

        $count = 0;
        foreach ($brands as $brand) {
            $sitemap->add(
                Url::create(route('front.catalog', ['category' => $brand->slug]))
                    ->setLastModificationDate($brand->updated_at ? Carbon::parse($brand->updated_at) : Carbon::now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.7)
            );
            $count++;
        }

        $sitemap->writeToFile(public_path('sitemap-categories.xml'));
        $this->info("Added {$count} categories to sitemap");
    }

    protected function generatePagesSitemap()
    {
        $sitemap = Sitemap::create();

        // Add homepage with highest priority
        $sitemap->add(
            Url::create(url('/'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        // Add main category listing
        try {
            $sitemap->add(
                Url::create(route('front.catalog'))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(0.9)
            );
        } catch (\Exception $e) {
            // Route may not exist
        }

        // Add other important pages
        $importantRoutes = [
            ['route' => 'user.login', 'priority' => 0.3],
            ['route' => 'user.register', 'priority' => 0.3],
            ['route' => 'front.contact', 'priority' => 0.4],
            ['route' => 'front.categories', 'priority' => 0.6],
        ];

        foreach ($importantRoutes as $page) {
            try {
                $sitemap->add(
                    Url::create(route($page['route']))
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority($page['priority'])
                );
            } catch (\Exception $e) {
                // Route may not exist, skip
            }
        }

        $sitemap->writeToFile(public_path('sitemap-pages.xml'));
        $this->info("Added pages to sitemap");
    }

    protected function generateBlogsSitemap()
    {
        $sitemap = Sitemap::create();

        // Add blog index
        try {
            $sitemap->add(
                Url::create(route('front.blog'))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(0.6)
            );
        } catch (\Exception $e) {
            // Route may not exist
        }

        // Add blog posts
        $posts = Publication::where('status', 1)->get();

        $count = 0;
        foreach ($posts as $post) {
            try {
                $sitemap->add(
                    Url::create(route('front.blog.details', $post->slug))
                        ->setLastModificationDate($post->updated_at ? Carbon::parse($post->updated_at) : Carbon::now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.5)
                );
                $count++;
            } catch (\Exception $e) {
                // Skip if route doesn't exist
            }
        }

        $sitemap->writeToFile(public_path('sitemap-blogs.xml'));
        $this->info("Added {$count} blog posts to sitemap");
    }
}
