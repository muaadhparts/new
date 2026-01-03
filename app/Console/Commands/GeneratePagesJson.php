<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class GeneratePagesJson extends Command
{
    protected $signature = 'audit:generate-pages {--base-url=http://new.test : Base URL for the site}';
    protected $description = 'Generate pages.generated.json from routes and DB samples for contrast audit';

    /**
     * Frontend routes to audit (patterns)
     */
    private $frontendPatterns = [
        // Static pages
        '/' => 'Home',
        '/contact' => 'Contact',
        '/faq' => 'FAQ',
        '/about' => 'About',
        '/cart' => 'Cart',
        '/checkout' => 'Checkout',
        '/login' => 'Login',
        '/register' => 'Register',
        '/blog' => 'Blog',
        '/brands' => 'Brands',

        // Dynamic pages - will be filled with DB samples
        '/category/{slug}' => 'Category',
        '/subcategory/{slug}' => 'Subcategory',
        '/catalogItem/{slug}' => 'CatalogItem Detail',
        '/catalog/{model}' => 'Catalog',
        '/brand/{slug}' => 'Brand',
        '/blog/{slug}' => 'Blog Post',
        '/merchant/{id}' => 'Merchant Profile',

        // User dashboard pages
        '/user/dashboard' => 'User Dashboard',
        '/user/orders' => 'User Orders',
        '/user/favorites' => 'User Favorites',
        '/user/profile' => 'User Profile',
    ];

    public function handle()
    {
        $baseUrl = $this->option('base-url');
        $pages = [];

        $this->info('Generating pages.json from routes and DB samples...');
        $this->newLine();

        // 1. Add static pages
        $this->info('Adding static pages...');
        foreach ($this->frontendPatterns as $path => $name) {
            if (!str_contains($path, '{')) {
                $pages[] = [
                    'name' => $name,
                    'path' => $path,
                    'requiresAuth' => str_starts_with($path, '/user/'),
                    'priority' => 'high'
                ];
                $this->line("  + {$name}: {$path}");
            }
        }

        // 2. Add dynamic pages with DB samples
        $this->newLine();
        $this->info('Adding dynamic pages from DB samples...');

        // Categories
        $categories = DB::table('categories')
            ->where('is_featured', 1)
            ->limit(3)
            ->pluck('slug');
        foreach ($categories as $slug) {
            $pages[] = [
                'name' => "Category: {$slug}",
                'path' => "/category/{$slug}",
                'requiresAuth' => false,
                'priority' => 'high'
            ];
            $this->line("  + Category: /category/{$slug}");
        }

        // Subcategories
        $subcats = DB::table('subcategories')
            ->limit(2)
            ->pluck('slug');
        foreach ($subcats as $slug) {
            $pages[] = [
                'name' => "Subcategory: {$slug}",
                'path' => "/subcategory/{$slug}",
                'requiresAuth' => false,
                'priority' => 'medium'
            ];
            $this->line("  + Subcategory: /subcategory/{$slug}");
        }

        // CatalogItems (get featured/popular ones)
        $catalogItems = DB::table('catalogItems')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where('featured', 1)
            ->orderBy('views', 'desc')
            ->limit(3)
            ->pluck('slug');
        foreach ($catalogItems as $slug) {
            $pages[] = [
                'name' => "CatalogItem: {$slug}",
                'path' => "/catalogItem/{$slug}",
                'requiresAuth' => false,
                'priority' => 'high'
            ];
            $this->line("  + CatalogItem: /catalogItem/{$slug}");
        }

        // Brands (use id since no slug)
        $brands = DB::table('brands')
            ->limit(2)
            ->pluck('id');
        foreach ($brands as $id) {
            $pages[] = [
                'name' => "Brand: {$id}",
                'path' => "/brand/{$id}",
                'requiresAuth' => false,
                'priority' => 'medium'
            ];
            $this->line("  + Brand: /brand/{$id}");
        }

        // Blogs
        $blogs = DB::table('blogs')
            ->where('status', 1)
            ->limit(2)
            ->pluck('slug');
        foreach ($blogs as $slug) {
            $pages[] = [
                'name' => "Blog: {$slug}",
                'path' => "/blog/{$slug}",
                'requiresAuth' => false,
                'priority' => 'low'
            ];
            $this->line("  + Blog: /blog/{$slug}");
        }

        // Merchants
        $merchants = DB::table('users')
            ->where('is_merchant', 1)
            ->where('status', 1)
            ->limit(2)
            ->pluck('id');
        foreach ($merchants as $id) {
            $pages[] = [
                'name' => "Merchant: {$id}",
                'path' => "/merchant/{$id}",
                'requiresAuth' => false,
                'priority' => 'medium'
            ];
            $this->line("  + Merchant: /merchant/{$id}");
        }

        // 3. Build final JSON structure
        $output = [
            'baseUrl' => $baseUrl,
            'generated' => now()->toIso8601String(),
            'totalPages' => count($pages),
            'pages' => $pages,
            'stateChecks' => [
                'default' => true,
                'hover' => true,
                'focus' => true,
                'disabled' => true
            ],
            'elementSelectors' => [
                'buttons' => [
                    '.m-btn', '.btn', 'button', '[type="submit"]', '[type="button"]',
                    '.template-btn', '.add-to-cart', '.cart-btn'
                ],
                'badges' => [
                    '.badge', '.m-badge', '[class*="badge-"]'
                ],
                'alerts' => [
                    '.alert', '.m-alert', '[class*="alert-"]'
                ],
                'links' => ['a'],
                'inputs' => ['input', 'select', 'textarea', '.form-control'],
                'cards' => ['.card', '.m-card', '.catalogItem-card'],
                'navigation' => ['.nav-link', '.dropdown-item', '.menu-link']
            ]
        ];

        // 4. Write to file
        $outputPath = base_path('scripts/contrast-audit/pages.generated.json');

        // Ensure directory exists
        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        file_put_contents($outputPath, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info("Generated {$output['totalPages']} pages to: {$outputPath}");

        return Command::SUCCESS;
    }
}
