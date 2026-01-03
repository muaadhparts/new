<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename all vendor references to merchant throughout the database
     */
    public function up(): void
    {
        // 1. Rename merchant_id to merchant_id in catalog_events
        if (Schema::hasColumn('catalog_events', 'merchant_id')) {
            Schema::table('catalog_events', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 2. Rename merchant_id to merchant_id in delivery_riders
        if (Schema::hasColumn('delivery_riders', 'merchant_id')) {
            Schema::table('delivery_riders', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 3. Rename merchant_id to merchant_id in favorite_sellers
        if (Schema::hasColumn('favorite_sellers', 'merchant_id')) {
            Schema::table('favorite_sellers', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 4. Rename merchant_id to merchant_id in shipment_status_logs
        if (Schema::hasColumn('shipment_status_logs', 'merchant_id')) {
            Schema::table('shipment_status_logs', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 5. Rename is_vendor to is_merchant in users
        if (Schema::hasColumn('users', 'is_vendor')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_vendor', 'is_merchant');
            });
        }

        // 6. Rename vendor columns in purchases
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'vendor_shipping_id')) {
                $table->renameColumn('vendor_shipping_id', 'merchant_shipping_id');
            }
            if (Schema::hasColumn('purchases', 'vendor_packing_id')) {
                $table->renameColumn('vendor_packing_id', 'merchant_packing_id');
            }
            if (Schema::hasColumn('purchases', 'vendor_ids')) {
                $table->renameColumn('vendor_ids', 'merchant_ids');
            }
        });

        // 7. Rename vendor columns in purchases_clone
        if (Schema::hasTable('purchases_clone')) {
            Schema::table('purchases_clone', function (Blueprint $table) {
                if (Schema::hasColumn('purchases_clone', 'vendor_shipping_id')) {
                    $table->renameColumn('vendor_shipping_id', 'merchant_shipping_id');
                }
                if (Schema::hasColumn('purchases_clone', 'vendor_packing_id')) {
                    $table->renameColumn('vendor_packing_id', 'merchant_packing_id');
                }
                if (Schema::hasColumn('purchases_clone', 'vendor_ids')) {
                    $table->renameColumn('vendor_ids', 'merchant_ids');
                }
            });
        }

        // 8. Rename vendor columns in muaadhsettings
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (Schema::hasColumn('muaadhsettings', 'vendor_ship_info')) {
                $table->renameColumn('vendor_ship_info', 'merchant_ship_info');
            }
            if (Schema::hasColumn('muaadhsettings', 'reg_vendor')) {
                $table->renameColumn('reg_vendor', 'reg_merchant');
            }
            if (Schema::hasColumn('muaadhsettings', 'vendor_color')) {
                $table->renameColumn('vendor_color', 'merchant_color');
            }
            if (Schema::hasColumn('muaadhsettings', 'vendor_page_count')) {
                $table->renameColumn('vendor_page_count', 'merchant_page_count');
            }
        });

        // 9. Rename vendor_credentials table to merchant_credentials
        if (Schema::hasTable('vendor_credentials') && !Schema::hasTable('merchant_credentials')) {
            Schema::rename('vendor_credentials', 'merchant_credentials');
        }

        // 10. Update withdraws type enum (vendor → merchant)
        // Note: MySQL requires recreating the column to change enum values
        DB::statement("ALTER TABLE withdraws MODIFY type ENUM('user', 'merchant', 'rider', 'vendor') NOT NULL");
        DB::statement("UPDATE withdraws SET type = 'merchant' WHERE type = 'vendor'");
        DB::statement("ALTER TABLE withdraws MODIFY type ENUM('user', 'merchant', 'rider') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Rename merchant_id back to merchant_id in catalog_events
        if (Schema::hasColumn('catalog_events', 'merchant_id')) {
            Schema::table('catalog_events', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 2. Rename merchant_id back to merchant_id in delivery_riders
        if (Schema::hasColumn('delivery_riders', 'merchant_id')) {
            Schema::table('delivery_riders', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 3. Rename merchant_id back to merchant_id in favorite_sellers
        if (Schema::hasColumn('favorite_sellers', 'merchant_id')) {
            Schema::table('favorite_sellers', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 4. Rename merchant_id back to merchant_id in shipment_status_logs
        if (Schema::hasColumn('shipment_status_logs', 'merchant_id')) {
            Schema::table('shipment_status_logs', function (Blueprint $table) {
                $table->renameColumn('merchant_id', 'merchant_id');
            });
        }

        // 5. Rename is_merchant back to is_vendor in users
        if (Schema::hasColumn('users', 'is_merchant')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_merchant', 'is_vendor');
            });
        }

        // 6. Rename merchant columns back to vendor in purchases
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'merchant_shipping_id')) {
                $table->renameColumn('merchant_shipping_id', 'vendor_shipping_id');
            }
            if (Schema::hasColumn('purchases', 'merchant_packing_id')) {
                $table->renameColumn('merchant_packing_id', 'vendor_packing_id');
            }
            if (Schema::hasColumn('purchases', 'merchant_ids')) {
                $table->renameColumn('merchant_ids', 'vendor_ids');
            }
        });

        // 7. Rename merchant columns back to vendor in purchases_clone
        if (Schema::hasTable('purchases_clone')) {
            Schema::table('purchases_clone', function (Blueprint $table) {
                if (Schema::hasColumn('purchases_clone', 'merchant_shipping_id')) {
                    $table->renameColumn('merchant_shipping_id', 'vendor_shipping_id');
                }
                if (Schema::hasColumn('purchases_clone', 'merchant_packing_id')) {
                    $table->renameColumn('merchant_packing_id', 'vendor_packing_id');
                }
                if (Schema::hasColumn('purchases_clone', 'merchant_ids')) {
                    $table->renameColumn('merchant_ids', 'vendor_ids');
                }
            });
        }

        // 8. Rename merchant columns back to vendor in muaadhsettings
        Schema::table('muaadhsettings', function (Blueprint $table) {
            if (Schema::hasColumn('muaadhsettings', 'merchant_ship_info')) {
                $table->renameColumn('merchant_ship_info', 'vendor_ship_info');
            }
            if (Schema::hasColumn('muaadhsettings', 'reg_merchant')) {
                $table->renameColumn('reg_merchant', 'reg_vendor');
            }
            if (Schema::hasColumn('muaadhsettings', 'merchant_color')) {
                $table->renameColumn('merchant_color', 'vendor_color');
            }
            if (Schema::hasColumn('muaadhsettings', 'merchant_page_count')) {
                $table->renameColumn('merchant_page_count', 'vendor_page_count');
            }
        });

        // 9. Rename merchant_credentials table back to vendor_credentials
        if (Schema::hasTable('merchant_credentials') && !Schema::hasTable('vendor_credentials')) {
            Schema::rename('merchant_credentials', 'vendor_credentials');
        }

        // 10. Revert withdraws type enum (merchant → vendor)
        DB::statement("ALTER TABLE withdraws MODIFY type ENUM('user', 'merchant', 'rider', 'vendor') NOT NULL");
        DB::statement("UPDATE withdraws SET type = 'vendor' WHERE type = 'merchant'");
        DB::statement("ALTER TABLE withdraws MODIFY type ENUM('user', 'vendor', 'rider') NOT NULL");
    }
};
