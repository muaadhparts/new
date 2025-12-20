<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance indexes to speed up slow queries
     */
    public function up(): void
    {
        // 1. Add index on products.category_id (speeds up withCount('products'))
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!$this->hasIndex('products', 'idx_products_category_id')) {
                    $table->index('category_id', 'idx_products_category_id');
                }
            });
        }

        // 2. Add composite index for status queries on merchant_products
        if (Schema::hasTable('merchant_products')) {
            Schema::table('merchant_products', function (Blueprint $table) {
                if (!$this->hasIndex('merchant_products', 'idx_mp_product_status')) {
                    $table->index(['product_id', 'status'], 'idx_mp_product_status');
                }
                if (!$this->hasIndex('merchant_products', 'idx_mp_status')) {
                    $table->index('status', 'idx_mp_status');
                }
            });
        }

        // 3. Add index on categories for featured queries
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (!$this->hasIndex('categories', 'idx_categories_is_featured')) {
                    $table->index('is_featured', 'idx_categories_is_featured');
                }
            });
        }

        // 4. Add indexes for newcategories (catalog browsing) - note: table name is 'newcategories' not 'new_categories'
        if (Schema::hasTable('newcategories')) {
            Schema::table('newcategories', function (Blueprint $table) {
                if (!$this->hasIndex('newcategories', 'idx_nc_catalog_brand_level')) {
                    $table->index(['catalog_id', 'brand_id', 'level'], 'idx_nc_catalog_brand_level');
                }
                if (!$this->hasIndex('newcategories', 'idx_nc_full_code')) {
                    $table->index('full_code', 'idx_nc_full_code');
                }
                if (!$this->hasIndex('newcategories', 'idx_nc_spec_key')) {
                    $table->index(['catalog_id', 'spec_key', 'level'], 'idx_nc_spec_key');
                }
            });
        }

        // 5. Add indexes for sections table
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                if (!$this->hasIndex('sections', 'idx_sections_full_code_catalog')) {
                    $table->index(['full_code', 'catalog_id'], 'idx_sections_full_code_catalog');
                }
            });
        }

        // 6. Add indexes for category_periods
        if (Schema::hasTable('category_periods')) {
            Schema::table('category_periods', function (Blueprint $table) {
                if (!$this->hasIndex('category_periods', 'idx_cp_category_dates')) {
                    $table->index(['category_id', 'begin_date', 'end_date'], 'idx_cp_category_dates');
                }
            });
        }

        // 7. Add indexes for parts_index (compatibility queries)
        if (Schema::hasTable('parts_index')) {
            Schema::table('parts_index', function (Blueprint $table) {
                if (!$this->hasIndex('parts_index', 'idx_pi_part_number')) {
                    $table->index('part_number', 'idx_pi_part_number');
                }
            });
        }

        // 8. Add indexes for sku_alternatives
        if (Schema::hasTable('sku_alternatives')) {
            Schema::table('sku_alternatives', function (Blueprint $table) {
                if (!$this->hasIndex('sku_alternatives', 'idx_ska_sku')) {
                    $table->index('sku', 'idx_ska_sku');
                }
                if (!$this->hasIndex('sku_alternatives', 'idx_ska_group_id')) {
                    $table->index('group_id', 'idx_ska_group_id');
                }
            });
        }

        // 9. Add indexes for specification_items
        if (Schema::hasTable('specification_items')) {
            Schema::table('specification_items', function (Blueprint $table) {
                if (!$this->hasIndex('specification_items', 'idx_si_catalog_spec')) {
                    $table->index(['catalog_id', 'specification_id'], 'idx_si_catalog_spec');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if ($this->hasIndex('products', 'idx_products_category_id')) {
                    $table->dropIndex('idx_products_category_id');
                }
            });
        }

        if (Schema::hasTable('merchant_products')) {
            Schema::table('merchant_products', function (Blueprint $table) {
                if ($this->hasIndex('merchant_products', 'idx_mp_product_status')) {
                    $table->dropIndex('idx_mp_product_status');
                }
                if ($this->hasIndex('merchant_products', 'idx_mp_status')) {
                    $table->dropIndex('idx_mp_status');
                }
            });
        }

        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if ($this->hasIndex('categories', 'idx_categories_is_featured')) {
                    $table->dropIndex('idx_categories_is_featured');
                }
            });
        }

        if (Schema::hasTable('newcategories')) {
            Schema::table('newcategories', function (Blueprint $table) {
                if ($this->hasIndex('newcategories', 'idx_nc_catalog_brand_level')) {
                    $table->dropIndex('idx_nc_catalog_brand_level');
                }
                if ($this->hasIndex('newcategories', 'idx_nc_full_code')) {
                    $table->dropIndex('idx_nc_full_code');
                }
                if ($this->hasIndex('newcategories', 'idx_nc_spec_key')) {
                    $table->dropIndex('idx_nc_spec_key');
                }
            });
        }

        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                if ($this->hasIndex('sections', 'idx_sections_full_code_catalog')) {
                    $table->dropIndex('idx_sections_full_code_catalog');
                }
            });
        }

        if (Schema::hasTable('category_periods')) {
            Schema::table('category_periods', function (Blueprint $table) {
                if ($this->hasIndex('category_periods', 'idx_cp_category_dates')) {
                    $table->dropIndex('idx_cp_category_dates');
                }
            });
        }

        if (Schema::hasTable('parts_index')) {
            Schema::table('parts_index', function (Blueprint $table) {
                if ($this->hasIndex('parts_index', 'idx_pi_part_number')) {
                    $table->dropIndex('idx_pi_part_number');
                }
            });
        }

        if (Schema::hasTable('sku_alternatives')) {
            Schema::table('sku_alternatives', function (Blueprint $table) {
                if ($this->hasIndex('sku_alternatives', 'idx_ska_sku')) {
                    $table->dropIndex('idx_ska_sku');
                }
                if ($this->hasIndex('sku_alternatives', 'idx_ska_group_id')) {
                    $table->dropIndex('idx_ska_group_id');
                }
            });
        }

        if (Schema::hasTable('specification_items')) {
            Schema::table('specification_items', function (Blueprint $table) {
                if ($this->hasIndex('specification_items', 'idx_si_catalog_spec')) {
                    $table->dropIndex('idx_si_catalog_spec');
                }
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
