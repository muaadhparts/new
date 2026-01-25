<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting test data seeding...');

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $this->seedLanguages();
        $this->seedMonetaryUnits();
        $this->seedCountries();
        $this->seedCities();
        $this->seedSettings();
        $this->seedFrontendSettings();
        $this->seedHomePageThemes();
        $this->seedOperatorRoles();
        $this->seedOperators();
        $this->seedUsers();
        $this->seedBrands();
        $this->seedBrandRegions();
        $this->seedCatalogs();
        $this->seedQualityBrands();
        $this->seedCatalogItems();
        $this->seedMerchantBranches();
        $this->seedMerchantItems();
        $this->seedShippings();
        $this->seedPages();
        $this->seedModules();
        $this->seedPlatformSettings();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->command->info('Test data seeding completed!');
    }

    private function seedLanguages(): void
    {
        $this->command->info('Seeding languages...');

        DB::table('languages')->insertOrIgnore([
            ['id' => 1, 'name' => 'ar', 'language' => 'Arabic', 'file' => 'ar', 'is_default' => 1, 'rtl' => 1],
            ['id' => 2, 'name' => 'en', 'language' => 'English', 'file' => 'en', 'is_default' => 0, 'rtl' => 0],
        ]);
    }

    private function seedMonetaryUnits(): void
    {
        $this->command->info('Seeding monetary units...');

        DB::table('monetary_units')->insertOrIgnore([
            ['id' => 1, 'name' => 'ريال سعودي', 'sign' => 'ر.س', 'value' => 1.0, 'is_default' => 1],
            ['id' => 2, 'name' => 'دولار أمريكي', 'sign' => '$', 'value' => 0.2667, 'is_default' => 0],
            ['id' => 3, 'name' => 'درهم إماراتي', 'sign' => 'د.إ', 'value' => 0.98, 'is_default' => 0],
        ]);
    }

    private function seedCountries(): void
    {
        $this->command->info('Seeding countries...');

        DB::table('countries')->insertOrIgnore([
            ['id' => 1, 'name' => 'Saudi Arabia', 'name_ar' => 'المملكة العربية السعودية', 'iso2' => 'SA', 'iso3' => 'SAU', 'phone_code' => '+966', 'capital' => 'Riyadh', 'currency' => 'SAR', 'status' => 1],
            ['id' => 2, 'name' => 'United Arab Emirates', 'name_ar' => 'الإمارات', 'iso2' => 'AE', 'iso3' => 'ARE', 'phone_code' => '+971', 'capital' => 'Abu Dhabi', 'currency' => 'AED', 'status' => 1],
            ['id' => 3, 'name' => 'Kuwait', 'name_ar' => 'الكويت', 'iso2' => 'KW', 'iso3' => 'KWT', 'phone_code' => '+965', 'capital' => 'Kuwait City', 'currency' => 'KWD', 'status' => 1],
        ]);
    }

    private function seedCities(): void
    {
        $this->command->info('Seeding cities...');

        DB::table('cities')->insertOrIgnore([
            ['id' => 1, 'name' => 'Riyadh', 'name_ar' => 'الرياض', 'country_id' => 1, 'latitude' => 24.7136, 'longitude' => 46.6753, 'status' => 1],
            ['id' => 2, 'name' => 'Jeddah', 'name_ar' => 'جدة', 'country_id' => 1, 'latitude' => 21.5433, 'longitude' => 39.1728, 'status' => 1],
            ['id' => 3, 'name' => 'Dammam', 'name_ar' => 'الدمام', 'country_id' => 1, 'latitude' => 26.4207, 'longitude' => 50.0888, 'status' => 1],
            ['id' => 4, 'name' => 'Mecca', 'name_ar' => 'مكة المكرمة', 'country_id' => 1, 'latitude' => 21.3891, 'longitude' => 39.8579, 'status' => 1],
            ['id' => 5, 'name' => 'Medina', 'name_ar' => 'المدينة المنورة', 'country_id' => 1, 'latitude' => 24.5247, 'longitude' => 39.5692, 'status' => 1],
            ['id' => 6, 'name' => 'Dubai', 'name_ar' => 'دبي', 'country_id' => 2, 'latitude' => 25.2048, 'longitude' => 55.2708, 'status' => 1],
        ]);
    }

    private function seedSettings(): void
    {
        $this->command->info('Seeding settings...');

        DB::table('settings')->insertOrIgnore([
            'id' => 1,
            'logo' => 'logo.png',
            'favicon' => 'favicon.ico',
            'loader' => 'loader.gif',
            'site_name' => 'MUAADH EPC',
            'site_name_ar' => 'معاذ لقطع الغيار',
            'title' => 'MUAADH EPC - Auto Parts',
            'tagline' => 'Your trusted source for auto parts',
            'tagline_ar' => 'مصدرك الموثوق لقطع الغيار',
            'footer' => 'Quality auto parts',
            'footer_ar' => 'قطع غيار عالية الجودة',
            'meta_keywords' => 'auto parts, car parts, spare parts',
            'copyright' => '2024 MUAADH EPC',
            'copyright_ar' => '2024 معاذ',
            'is_maintain' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedFrontendSettings(): void
    {
        $this->command->info('Seeding frontend settings...');

        DB::table('frontend_settings')->insertOrIgnore([
            'id' => 1,
            'hero_title' => 'Find Your Auto Parts',
            'hero_title_ar' => 'ابحث عن قطع غيارك',
            'hero_subtitle' => 'Search by VIN or Part Number',
            'hero_subtitle_ar' => 'ابحث برقم الهيكل أو رقم القطعة',
            'primary_color' => '#006c35',
            'secondary_color' => '#f8f9fa',
            'show_vin_search' => 1,
            'show_part_search' => 1,
            'show_brands' => 1,
            'show_categories' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedHomePageThemes(): void
    {
        $this->command->info('Seeding home page themes...');

        DB::table('home_page_themes')->insertOrIgnore([
            'id' => 1,
            'name' => 'Default Theme',
            'slug' => 'default',
            'description' => 'Default theme',
            'is_active' => 1,
            'hero_style' => 'default',
            'hero_title' => 'Find Your Parts',
            'hero_title_ar' => 'ابحث عن قطعك',
            'search_style' => 'tabs',
            'show_vin_search' => 1,
            'show_part_search' => 1,
            'show_vehicle_search' => 1,
            'show_brands' => 1,
            'show_categories' => 1,
            'show_trust_badges' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedOperatorRoles(): void
    {
        $this->command->info('Seeding operator roles...');

        DB::table('operator_roles')->insertOrIgnore([
            ['id' => 'owner', 'name' => 'Owner', 'name_ar' => 'المالك', 'permissions' => json_encode(['*'])],
            ['id' => 'admin', 'name' => 'Admin', 'name_ar' => 'مدير', 'permissions' => json_encode(['dashboard', 'users', 'merchants'])],
            ['id' => 'support', 'name' => 'Support', 'name_ar' => 'دعم', 'permissions' => json_encode(['dashboard', 'users'])],
        ]);
    }

    private function seedOperators(): void
    {
        $this->command->info('Seeding operators...');

        DB::table('operators')->insertOrIgnore([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@muaadh.com',
            'phone' => '+966500000000',
            'role_id' => 'owner',
            'status' => 1,
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedUsers(): void
    {
        $this->command->info('Seeding users...');

        $users = [
            [
                'id' => 1,
                'name' => 'أحمد محمد',
                'email' => 'ahmed@test.com',
                'phone' => '+966501234567',
                'city' => 'الرياض',
                'city_id' => 1,
                'country' => 'SA',
                'password' => Hash::make('password'),
                'email_verified' => 'Yes',
                'is_merchant' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'محمد علي',
                'email' => 'mohammed@test.com',
                'phone' => '+966502345678',
                'city' => 'جدة',
                'city_id' => 2,
                'country' => 'SA',
                'password' => Hash::make('password'),
                'email_verified' => 'Yes',
                'is_merchant' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'خالد العتيبي',
                'email' => 'merchant1@test.com',
                'phone' => '+966503456789',
                'city' => 'الرياض',
                'city_id' => 1,
                'country' => 'SA',
                'password' => Hash::make('password'),
                'email_verified' => 'Yes',
                'is_merchant' => 1,
                'shop_name' => 'Al-Otaibi Auto Parts',
                'shop_name_ar' => 'قطع غيار العتيبي',
                'shop_address' => 'Industrial Area, Riyadh',
                'owner_name' => 'خالد العتيبي',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'عبدالله الشمري',
                'email' => 'merchant2@test.com',
                'phone' => '+966504567890',
                'city' => 'الدمام',
                'city_id' => 3,
                'country' => 'SA',
                'password' => Hash::make('password'),
                'email_verified' => 'Yes',
                'is_merchant' => 1,
                'shop_name' => 'Eastern Parts',
                'shop_name_ar' => 'قطع الشرقية',
                'shop_address' => 'Dammam Industrial City',
                'owner_name' => 'عبدالله الشمري',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'فهد القحطاني',
                'email' => 'merchant3@test.com',
                'phone' => '+966505678901',
                'city' => 'جدة',
                'city_id' => 2,
                'country' => 'SA',
                'password' => Hash::make('password'),
                'email_verified' => 'Yes',
                'is_merchant' => 1,
                'shop_name' => 'Jeddah Auto Center',
                'shop_name_ar' => 'مركز جدة للسيارات',
                'shop_address' => 'Al-Khayyat District, Jeddah',
                'owner_name' => 'فهد القحطاني',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insertOrIgnore($user);
        }
    }

    private function seedBrands(): void
    {
        $this->command->info('Seeding brands...');

        $brands = [
            ['id' => 1, 'name' => 'Nissan', 'name_ar' => 'نيسان', 'slug' => 'nissan', 'is_featured' => 1, 'status' => 1],
            ['id' => 2, 'name' => 'Toyota', 'name_ar' => 'تويوتا', 'slug' => 'toyota', 'is_featured' => 1, 'status' => 1],
            ['id' => 3, 'name' => 'Hyundai', 'name_ar' => 'هيونداي', 'slug' => 'hyundai', 'is_featured' => 1, 'status' => 1],
            ['id' => 4, 'name' => 'Kia', 'name_ar' => 'كيا', 'slug' => 'kia', 'is_featured' => 1, 'status' => 1],
            ['id' => 5, 'name' => 'Honda', 'name_ar' => 'هوندا', 'slug' => 'honda', 'is_featured' => 1, 'status' => 1],
            ['id' => 6, 'name' => 'Chevrolet', 'name_ar' => 'شيفروليه', 'slug' => 'chevrolet', 'is_featured' => 0, 'status' => 1],
            ['id' => 7, 'name' => 'Ford', 'name_ar' => 'فورد', 'slug' => 'ford', 'is_featured' => 0, 'status' => 1],
            ['id' => 8, 'name' => 'Mercedes-Benz', 'name_ar' => 'مرسيدس بنز', 'slug' => 'mercedes', 'is_featured' => 1, 'status' => 1],
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->insertOrIgnore($brand);
        }
    }

    private function seedBrandRegions(): void
    {
        $this->command->info('Seeding brand regions...');

        $regions = [
            ['id' => 1, 'brand_id' => 1, 'code' => 'GL', 'label_en' => 'Gulf', 'label_ar' => 'الخليج', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'brand_id' => 1, 'code' => 'EU', 'label_en' => 'Europe', 'label_ar' => 'أوروبا', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'brand_id' => 2, 'code' => 'GL', 'label_en' => 'Gulf', 'label_ar' => 'الخليج', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'brand_id' => 3, 'code' => 'GL', 'label_en' => 'Gulf', 'label_ar' => 'الخليج', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($regions as $region) {
            DB::table('brand_regions')->insertOrIgnore($region);
        }
    }

    private function seedCatalogs(): void
    {
        $this->command->info('Seeding catalogs (vehicles)...');

        $catalogs = [
            ['id' => 1, 'code' => 'Y61GL', 'brand_id' => 1, 'brand_region_id' => 1, 'name' => 'Patrol Y61', 'name_ar' => 'باترول Y61', 'label_en' => 'Patrol Y61', 'label_ar' => 'باترول Y61', 'slug' => 'patrol-y61', 'beginYear' => 1997, 'endYear' => 2010, 'status' => 1],
            ['id' => 2, 'code' => 'Y62GL', 'brand_id' => 1, 'brand_region_id' => 1, 'name' => 'Patrol Y62', 'name_ar' => 'باترول Y62', 'label_en' => 'Patrol Y62', 'label_ar' => 'باترول Y62', 'slug' => 'patrol-y62', 'beginYear' => 2010, 'endYear' => 2024, 'status' => 1],
            ['id' => 3, 'code' => 'R50GL', 'brand_id' => 1, 'brand_region_id' => 1, 'name' => 'Pathfinder R50', 'name_ar' => 'باثفايندر R50', 'label_en' => 'Pathfinder R50', 'label_ar' => 'باثفايندر R50', 'slug' => 'pathfinder-r50', 'beginYear' => 1996, 'endYear' => 2004, 'status' => 1],
            ['id' => 4, 'code' => 'LC200GL', 'brand_id' => 2, 'brand_region_id' => 3, 'name' => 'Land Cruiser 200', 'name_ar' => 'لاندكروزر 200', 'label_en' => 'Land Cruiser 200', 'label_ar' => 'لاندكروزر 200', 'slug' => 'land-cruiser-200', 'beginYear' => 2008, 'endYear' => 2021, 'status' => 1],
            ['id' => 5, 'code' => 'CAMRYGL', 'brand_id' => 2, 'brand_region_id' => 3, 'name' => 'Camry', 'name_ar' => 'كامري', 'label_en' => 'Camry', 'label_ar' => 'كامري', 'slug' => 'camry', 'beginYear' => 2018, 'endYear' => 2024, 'status' => 1],
            ['id' => 6, 'code' => 'SONATAGL', 'brand_id' => 3, 'brand_region_id' => 4, 'name' => 'Sonata', 'name_ar' => 'سوناتا', 'label_en' => 'Sonata', 'label_ar' => 'سوناتا', 'slug' => 'sonata', 'beginYear' => 2020, 'endYear' => 2024, 'status' => 1],
        ];

        foreach ($catalogs as $catalog) {
            DB::table('catalogs')->insertOrIgnore($catalog);
        }
    }

    private function seedQualityBrands(): void
    {
        $this->command->info('Seeding quality brands...');

        $qualityBrands = [
            ['id' => 1, 'code' => 'OEM', 'name_en' => 'OEM Original', 'name_ar' => 'أصلي', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'GEN', 'name_en' => 'Genuine', 'name_ar' => 'وكيل', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'code' => 'AFM', 'name_en' => 'Aftermarket', 'name_ar' => 'بديل', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'code' => 'TW', 'name_en' => 'Taiwan', 'name_ar' => 'تايوان', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'code' => 'CN', 'name_en' => 'China', 'name_ar' => 'صيني', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($qualityBrands as $qb) {
            DB::table('quality_brands')->insertOrIgnore($qb);
        }
    }

    private function seedCatalogItems(): void
    {
        $this->command->info('Seeding catalog items...');

        $items = [
            ['id' => 1, 'part_number' => '15208-65F0E', 'name' => 'Oil Filter', 'label_en' => 'Oil Filter', 'label_ar' => 'فلتر زيت', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'part_number' => '04152-YZZA1', 'name' => 'Oil Filter Element', 'label_en' => 'Oil Filter Element', 'label_ar' => 'عنصر فلتر الزيت', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'part_number' => '26300-35503', 'name' => 'Oil Filter Hyundai', 'label_en' => 'Oil Filter', 'label_ar' => 'فلتر زيت هيونداي', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'part_number' => '16546-30P00', 'name' => 'Air Filter', 'label_en' => 'Air Filter', 'label_ar' => 'فلتر هواء', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'part_number' => '17801-0C010', 'name' => 'Air Filter Toyota', 'label_en' => 'Air Filter', 'label_ar' => 'فلتر هواء تويوتا', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'part_number' => 'D1060-1LB0A', 'name' => 'Front Brake Pads', 'label_en' => 'Front Brake Pads', 'label_ar' => 'تيل فرامل أمامي', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'part_number' => '04465-60320', 'name' => 'Front Brake Pads Toyota', 'label_en' => 'Front Brake Pads', 'label_ar' => 'تيل فرامل أمامي تويوتا', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'part_number' => 'D4060-JA00A', 'name' => 'Rear Brake Pads', 'label_en' => 'Rear Brake Pads', 'label_ar' => 'تيل فرامل خلفي', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'part_number' => '22401-5M015', 'name' => 'Spark Plug', 'label_en' => 'Spark Plug', 'label_ar' => 'شمعة إشعال', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'part_number' => '90919-01253', 'name' => 'Spark Plug Toyota', 'label_en' => 'Spark Plug', 'label_ar' => 'شمعة إشعال تويوتا', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'part_number' => '13028-AD212', 'name' => 'Timing Chain Kit', 'label_en' => 'Timing Chain Kit', 'label_ar' => 'طقم سلسلة التوقيت', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'part_number' => '13568-09130', 'name' => 'Timing Belt', 'label_en' => 'Timing Belt', 'label_ar' => 'سير توقيت', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'part_number' => '11044-AD202', 'name' => 'Head Gasket', 'label_en' => 'Head Gasket', 'label_ar' => 'جوان رأس', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'part_number' => '27277-4BA0A', 'name' => 'Cabin Air Filter', 'label_en' => 'Cabin Air Filter', 'label_ar' => 'فلتر مكيف', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'part_number' => '87139-52040', 'name' => 'Cabin Air Filter Toyota', 'label_en' => 'Cabin Air Filter', 'label_ar' => 'فلتر مكيف تويوتا', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'part_number' => '40206-8H325', 'name' => 'Front Brake Disc', 'label_en' => 'Front Brake Disc', 'label_ar' => 'قرص فرامل أمامي', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'part_number' => '43512-60180', 'name' => 'Front Brake Disc Toyota', 'label_en' => 'Front Brake Disc', 'label_ar' => 'قرص فرامل أمامي تويوتا', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'part_number' => '16400-41B10', 'name' => 'Fuel Filter', 'label_en' => 'Fuel Filter', 'label_ar' => 'فلتر وقود', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 19, 'part_number' => '23300-79525', 'name' => 'Fuel Filter Toyota', 'label_en' => 'Fuel Filter', 'label_ar' => 'فلتر وقود تويوتا', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'part_number' => '21430-8H315', 'name' => 'Radiator', 'label_en' => 'Radiator', 'label_ar' => 'رديتر', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($items as $item) {
            DB::table('catalog_items')->insertOrIgnore($item);
        }
    }

    private function seedMerchantBranches(): void
    {
        $this->command->info('Seeding merchant branches...');

        $branches = [
            ['id' => 1, 'user_id' => 3, 'branch_name' => 'Main Branch', 'warehouse_name' => 'Main Warehouse', 'country_id' => 1, 'city_id' => 1, 'location' => 'Industrial Area, Riyadh', 'latitude' => 24.7136, 'longitude' => 46.6753, 'status' => 1],
            ['id' => 2, 'user_id' => 3, 'branch_name' => 'Jeddah Branch', 'warehouse_name' => 'Jeddah Warehouse', 'country_id' => 1, 'city_id' => 2, 'location' => 'Al-Khayyat District', 'latitude' => 21.5433, 'longitude' => 39.1728, 'status' => 1],
            ['id' => 3, 'user_id' => 4, 'branch_name' => 'Dammam Main', 'warehouse_name' => 'Eastern Warehouse', 'country_id' => 1, 'city_id' => 3, 'location' => 'Dammam Industrial City', 'latitude' => 26.4207, 'longitude' => 50.0888, 'status' => 1],
            ['id' => 4, 'user_id' => 5, 'branch_name' => 'Jeddah Center', 'warehouse_name' => 'JC Warehouse', 'country_id' => 1, 'city_id' => 2, 'location' => 'Al-Khayyat District', 'latitude' => 21.5433, 'longitude' => 39.1728, 'status' => 1],
        ];

        foreach ($branches as $branch) {
            DB::table('merchant_branches')->insertOrIgnore($branch);
        }
    }

    private function seedMerchantItems(): void
    {
        $this->command->info('Seeding merchant items...');

        $merchantItems = [];
        $id = 1;

        // Merchant 3 items
        foreach ([1, 2, 4, 6, 9, 11, 14, 16, 18, 20] as $catalogItemId) {
            $merchantItems[] = [
                'id' => $id++,
                'user_id' => 3,
                'merchant_branch_id' => 1,
                'catalog_item_id' => $catalogItemId,
                'quality_brand_id' => rand(1, 3),
                'price' => rand(50, 500) + (rand(0, 99) / 100),
                'stock' => rand(5, 50),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Merchant 4 items
        foreach ([2, 3, 5, 7, 10, 12, 15, 17, 19] as $catalogItemId) {
            $merchantItems[] = [
                'id' => $id++,
                'user_id' => 4,
                'merchant_branch_id' => 3,
                'catalog_item_id' => $catalogItemId,
                'quality_brand_id' => rand(1, 4),
                'price' => rand(40, 450) + (rand(0, 99) / 100),
                'stock' => rand(10, 100),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Merchant 5 items
        foreach ([1, 3, 5, 6, 8, 10, 13, 15, 17] as $catalogItemId) {
            $merchantItems[] = [
                'id' => $id++,
                'user_id' => 5,
                'merchant_branch_id' => 4,
                'catalog_item_id' => $catalogItemId,
                'quality_brand_id' => rand(2, 5),
                'price' => rand(45, 480) + (rand(0, 99) / 100),
                'stock' => rand(3, 30),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($merchantItems as $item) {
            DB::table('merchant_items')->insertOrIgnore($item);
        }
    }

    private function seedShippings(): void
    {
        $this->command->info('Seeding shippings...');

        $shippings = [
            ['id' => 1, 'title' => 'SMSA Express', 'price' => 25.00, 'user_id' => 0, 'status' => 1],
            ['id' => 2, 'title' => 'Aramex', 'price' => 30.00, 'user_id' => 0, 'status' => 1],
            ['id' => 3, 'title' => 'DHL', 'price' => 45.00, 'user_id' => 0, 'status' => 1],
            ['id' => 4, 'title' => 'Free Pickup', 'price' => 0.00, 'user_id' => 0, 'status' => 1],
        ];

        foreach ($shippings as $shipping) {
            DB::table('shippings')->insertOrIgnore($shipping);
        }
    }

    private function seedPages(): void
    {
        $this->command->info('Seeding pages...');

        $pages = [
            ['id' => 1, 'title' => 'About Us', 'title_ar' => 'من نحن', 'slug' => 'about-us', 'details' => '<p>MUAADH EPC - Auto Parts</p>', 'details_ar' => '<p>معاذ لقطع الغيار</p>', 'status' => 1, 'header' => 1, 'footer' => 1],
            ['id' => 2, 'title' => 'Contact Us', 'title_ar' => 'اتصل بنا', 'slug' => 'contact-us', 'details' => '<p>Contact us</p>', 'details_ar' => '<p>تواصل معنا</p>', 'status' => 1, 'header' => 1, 'footer' => 1],
            ['id' => 3, 'title' => 'Terms', 'title_ar' => 'الشروط', 'slug' => 'terms', 'details' => '<p>Terms</p>', 'details_ar' => '<p>الشروط</p>', 'status' => 1, 'header' => 0, 'footer' => 1],
            ['id' => 4, 'title' => 'Privacy', 'title_ar' => 'الخصوصية', 'slug' => 'privacy', 'details' => '<p>Privacy</p>', 'details_ar' => '<p>الخصوصية</p>', 'status' => 1, 'header' => 0, 'footer' => 1],
        ];

        foreach ($pages as $page) {
            DB::table('pages')->insertOrIgnore($page);
        }
    }

    private function seedModules(): void
    {
        $this->command->info('Seeding modules...');

        $modules = [
            ['id' => 1, 'name' => 'VIN Decoder', 'slug' => 'vin-decoder', 'description' => 'VIN decoder', 'is_active' => 1, 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Shipment Tracking', 'slug' => 'shipment-tracking', 'description' => 'Track shipments', 'is_active' => 1, 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Reviews', 'slug' => 'reviews', 'description' => 'Product reviews', 'is_active' => 1, 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->insertOrIgnore($module);
        }
    }

    private function seedPlatformSettings(): void
    {
        $this->command->info('Seeding platform settings...');

        $settings = [
            ['group' => 'branding', 'key' => 'logo', 'value' => 'logo.png', 'type' => 'image', 'label' => 'Logo', 'label_ar' => 'الشعار', 'order' => 1],
            ['group' => 'branding', 'key' => 'site_name', 'value' => 'MUAADH EPC', 'type' => 'string', 'label' => 'Site Name', 'label_ar' => 'اسم الموقع', 'order' => 2],
            ['group' => 'features', 'key' => 'enable_vin_search', 'value' => '1', 'type' => 'boolean', 'label' => 'VIN Search', 'label_ar' => 'بحث VIN', 'order' => 1],
            ['group' => 'features', 'key' => 'enable_reviews', 'value' => '1', 'type' => 'boolean', 'label' => 'Reviews', 'label_ar' => 'التقييمات', 'order' => 2],
            ['group' => 'contact', 'key' => 'email', 'value' => 'info@muaadh.com', 'type' => 'string', 'label' => 'Email', 'label_ar' => 'البريد', 'order' => 1],
            ['group' => 'contact', 'key' => 'phone', 'value' => '+966500000000', 'type' => 'string', 'label' => 'Phone', 'label_ar' => 'الهاتف', 'order' => 2],
        ];

        foreach ($settings as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            DB::table('platform_settings')->insertOrIgnore($setting);
        }
    }
}
