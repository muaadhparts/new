<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration to clean up old catalog_item_id/merchant_item_id columns
 * and keep only the new catalog_item_id/merchant_item_id columns
 *
 * Tables affected:
 * - catalog_reviews
 * - favorites
 * - comments
 * - galleries
 * - notifications
 * - reports
 * - stock_reservations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. catalog_reviews - copy data and drop old columns
        if (Schema::hasColumn('catalog_reviews', 'catalog_item_id') && Schema::hasColumn('catalog_reviews', 'catalog_item_id')) {
            // Copy data from old to new if new is empty
            DB::statement('UPDATE catalog_reviews SET catalog_item_id = catalog_item_id WHERE catalog_item_id IS NULL AND catalog_item_id IS NOT NULL');
            DB::statement('UPDATE catalog_reviews SET merchant_item_id = merchant_item_id WHERE merchant_item_id IS NULL AND merchant_item_id IS NOT NULL');

            // Drop old columns
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->dropColumn('catalog_item_id');
            });
            if (Schema::hasColumn('catalog_reviews', 'merchant_item_id')) {
                try {
                    Schema::table('catalog_reviews', function (Blueprint $table) {
                        $table->dropIndex('catalog_reviews_merchant_catalog_item_id_index');
                    });
                } catch (\Exception $e) {}
                Schema::table('catalog_reviews', function (Blueprint $table) {
                    $table->dropColumn('merchant_item_id');
                });
            }
        } elseif (Schema::hasColumn('catalog_reviews', 'catalog_item_id') && !Schema::hasColumn('catalog_reviews', 'catalog_item_id')) {
            // Rename old to new
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'catalog_item_id');
            });
            if (Schema::hasColumn('catalog_reviews', 'merchant_item_id')) {
                try {
                    Schema::table('catalog_reviews', function (Blueprint $table) {
                        $table->dropIndex('catalog_reviews_merchant_catalog_item_id_index');
                    });
                } catch (\Exception $e) {}
                Schema::table('catalog_reviews', function (Blueprint $table) {
                    $table->renameColumn('merchant_item_id', 'merchant_item_id');
                });
                Schema::table('catalog_reviews', function (Blueprint $table) {
                    $table->index('merchant_item_id', 'catalog_reviews_merchant_item_id_index');
                });
            }
        }

        // 2. favorites - copy data and drop old columns
        if (Schema::hasColumn('favorites', 'catalog_item_id') && Schema::hasColumn('favorites', 'catalog_item_id')) {
            DB::statement('UPDATE favorites SET catalog_item_id = catalog_item_id WHERE catalog_item_id IS NULL AND catalog_item_id IS NOT NULL');
            DB::statement('UPDATE favorites SET merchant_item_id = merchant_item_id WHERE merchant_item_id IS NULL AND merchant_item_id IS NOT NULL');

            Schema::table('favorites', function (Blueprint $table) {
                $table->dropColumn('catalog_item_id');
            });
            if (Schema::hasColumn('favorites', 'merchant_item_id')) {
                Schema::table('favorites', function (Blueprint $table) {
                    $table->dropColumn('merchant_item_id');
                });
            }
        } elseif (Schema::hasColumn('favorites', 'catalog_item_id') && !Schema::hasColumn('favorites', 'catalog_item_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'catalog_item_id');
            });
            if (Schema::hasColumn('favorites', 'merchant_item_id')) {
                Schema::table('favorites', function (Blueprint $table) {
                    $table->renameColumn('merchant_item_id', 'merchant_item_id');
                });
            }
        }

        // 3. comments - copy data and drop old columns
        if (Schema::hasColumn('comments', 'catalog_item_id') && Schema::hasColumn('comments', 'catalog_item_id')) {
            DB::statement('UPDATE comments SET catalog_item_id = catalog_item_id WHERE catalog_item_id IS NULL AND catalog_item_id IS NOT NULL');
            DB::statement('UPDATE comments SET merchant_item_id = merchant_item_id WHERE merchant_item_id IS NULL AND merchant_item_id IS NOT NULL');

            Schema::table('comments', function (Blueprint $table) {
                $table->dropColumn('catalog_item_id');
            });
            if (Schema::hasColumn('comments', 'merchant_item_id')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->dropColumn('merchant_item_id');
                });
            }
        } elseif (Schema::hasColumn('comments', 'catalog_item_id') && !Schema::hasColumn('comments', 'catalog_item_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'catalog_item_id');
            });
            if (Schema::hasColumn('comments', 'merchant_item_id')) {
                Schema::table('comments', function (Blueprint $table) {
                    $table->renameColumn('merchant_item_id', 'merchant_item_id');
                });
            }
        }

        // 4. galleries - copy data and drop old columns
        if (Schema::hasColumn('galleries', 'catalog_item_id') && Schema::hasColumn('galleries', 'catalog_item_id')) {
            DB::statement('UPDATE galleries SET catalog_item_id = catalog_item_id WHERE catalog_item_id IS NULL AND catalog_item_id IS NOT NULL');

            Schema::table('galleries', function (Blueprint $table) {
                $table->dropColumn('catalog_item_id');
            });
        } elseif (Schema::hasColumn('galleries', 'catalog_item_id') && !Schema::hasColumn('galleries', 'catalog_item_id')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'catalog_item_id');
            });
        }

        // 5. notifications - copy data and drop old columns
        if (Schema::hasColumn('notifications', 'catalog_item_id') && Schema::hasColumn('notifications', 'catalog_item_id')) {
            DB::statement('UPDATE notifications SET catalog_item_id = catalog_item_id WHERE catalog_item_id IS NULL AND catalog_item_id IS NOT NULL');

            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('catalog_item_id');
            });
        } elseif (Schema::hasColumn('notifications', 'catalog_item_id') && !Schema::hasColumn('notifications', 'catalog_item_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'catalog_item_id');
            });
        }

        // 6. reports - copy data and drop old columns
        if (Schema::hasColumn('reports', 'catalog_item_id') && Schema::hasColumn('reports', 'catalog_item_id')) {
            DB::statement('UPDATE reports SET catalog_item_id = catalog_item_id WHERE catalog_item_id IS NULL AND catalog_item_id IS NOT NULL');
            DB::statement('UPDATE reports SET merchant_item_id = merchant_item_id WHERE merchant_item_id IS NULL AND merchant_item_id IS NOT NULL');

            Schema::table('reports', function (Blueprint $table) {
                $table->dropColumn('catalog_item_id');
            });
            if (Schema::hasColumn('reports', 'merchant_item_id')) {
                try {
                    Schema::table('reports', function (Blueprint $table) {
                        $table->dropIndex('reports_merchant_catalog_item_id_index');
                    });
                } catch (\Exception $e) {}
                Schema::table('reports', function (Blueprint $table) {
                    $table->dropColumn('merchant_item_id');
                });
            }
        } elseif (Schema::hasColumn('reports', 'catalog_item_id') && !Schema::hasColumn('reports', 'catalog_item_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->renameColumn('catalog_item_id', 'catalog_item_id');
            });
            if (Schema::hasColumn('reports', 'merchant_item_id')) {
                try {
                    Schema::table('reports', function (Blueprint $table) {
                        $table->dropIndex('reports_merchant_catalog_item_id_index');
                    });
                } catch (\Exception $e) {}
                Schema::table('reports', function (Blueprint $table) {
                    $table->renameColumn('merchant_item_id', 'merchant_item_id');
                });
                Schema::table('reports', function (Blueprint $table) {
                    $table->index('merchant_item_id', 'reports_merchant_item_id_index');
                });
            }
        }

        // 7. stock_reservations - copy data and drop old columns
        if (Schema::hasColumn('stock_reservations', 'merchant_item_id') && Schema::hasColumn('stock_reservations', 'merchant_item_id')) {
            DB::statement('UPDATE stock_reservations SET merchant_item_id = merchant_item_id WHERE merchant_item_id IS NULL AND merchant_item_id IS NOT NULL');

            try {
                Schema::table('stock_reservations', function (Blueprint $table) {
                    $table->dropIndex('stock_reservations_expires_at_merchant_catalog_item_id_index');
                });
            } catch (\Exception $e) {}
            Schema::table('stock_reservations', function (Blueprint $table) {
                $table->dropColumn('merchant_item_id');
            });
        } elseif (Schema::hasColumn('stock_reservations', 'merchant_item_id') && !Schema::hasColumn('stock_reservations', 'merchant_item_id')) {
            try {
                Schema::table('stock_reservations', function (Blueprint $table) {
                    $table->dropIndex('stock_reservations_expires_at_merchant_catalog_item_id_index');
                });
            } catch (\Exception $e) {}
            Schema::table('stock_reservations', function (Blueprint $table) {
                $table->renameColumn('merchant_item_id', 'merchant_item_id');
            });
            Schema::table('stock_reservations', function (Blueprint $table) {
                $table->index(['expires_at', 'merchant_item_id'], 'stock_reservations_expires_at_merchant_item_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back old columns (without dropping new ones - safer for rollback)

        // 1. catalog_reviews
        if (!Schema::hasColumn('catalog_reviews', 'catalog_item_id')) {
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->integer('catalog_item_id')->nullable()->after('user_id');
            });
            DB::statement('UPDATE catalog_reviews SET catalog_item_id = catalog_item_id');
        }
        if (!Schema::hasColumn('catalog_reviews', 'merchant_item_id')) {
            Schema::table('catalog_reviews', function (Blueprint $table) {
                $table->unsignedInteger('merchant_item_id')->nullable()->after('catalog_item_id');
                $table->index('merchant_item_id', 'catalog_reviews_merchant_catalog_item_id_index');
            });
            DB::statement('UPDATE catalog_reviews SET merchant_item_id = merchant_item_id');
        }

        // 2. favorites
        if (!Schema::hasColumn('favorites', 'catalog_item_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->unsignedInteger('catalog_item_id')->nullable()->after('user_id');
            });
            DB::statement('UPDATE favorites SET catalog_item_id = catalog_item_id');
        }
        if (!Schema::hasColumn('favorites', 'merchant_item_id')) {
            Schema::table('favorites', function (Blueprint $table) {
                $table->unsignedInteger('merchant_item_id')->nullable()->after('catalog_item_id');
            });
            DB::statement('UPDATE favorites SET merchant_item_id = merchant_item_id');
        }

        // 3. comments
        if (!Schema::hasColumn('comments', 'catalog_item_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->unsignedInteger('catalog_item_id')->nullable()->after('user_id');
            });
            DB::statement('UPDATE comments SET catalog_item_id = catalog_item_id');
        }
        if (!Schema::hasColumn('comments', 'merchant_item_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->unsignedBigInteger('merchant_item_id')->nullable()->after('catalog_item_id');
            });
            DB::statement('UPDATE comments SET merchant_item_id = merchant_item_id');
        }

        // 4. galleries
        if (!Schema::hasColumn('galleries', 'catalog_item_id')) {
            Schema::table('galleries', function (Blueprint $table) {
                $table->unsignedInteger('catalog_item_id')->nullable()->after('id');
            });
            DB::statement('UPDATE galleries SET catalog_item_id = catalog_item_id');
        }

        // 5. notifications
        if (!Schema::hasColumn('notifications', 'catalog_item_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->integer('catalog_item_id')->nullable()->after('merchant_id');
            });
            DB::statement('UPDATE notifications SET catalog_item_id = catalog_item_id');
        }

        // 6. reports
        if (!Schema::hasColumn('reports', 'catalog_item_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->integer('catalog_item_id')->nullable()->after('user_id');
            });
            DB::statement('UPDATE reports SET catalog_item_id = catalog_item_id');
        }
        if (!Schema::hasColumn('reports', 'merchant_item_id')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->unsignedInteger('merchant_item_id')->nullable()->after('catalog_item_id');
                $table->index('merchant_item_id', 'reports_merchant_catalog_item_id_index');
            });
            DB::statement('UPDATE reports SET merchant_item_id = merchant_item_id');
        }

        // 7. stock_reservations
        if (!Schema::hasColumn('stock_reservations', 'merchant_item_id')) {
            Schema::table('stock_reservations', function (Blueprint $table) {
                $table->unsignedInteger('merchant_item_id')->nullable()->after('user_id');
                $table->index(['expires_at', 'merchant_item_id'], 'stock_reservations_expires_at_merchant_catalog_item_id_index');
            });
            DB::statement('UPDATE stock_reservations SET merchant_item_id = merchant_item_id');
        }
    }
};
