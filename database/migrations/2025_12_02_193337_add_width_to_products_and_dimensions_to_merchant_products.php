<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration لإضافة أعمدة الأبعاد المفقودة
 *
 * products table:
 * - width: مطلوب لحساب الوزن الحجمي (L × W × H / 5000)
 *
 * merchant_products table:
 * - weight, length, width, height: لتخزين أبعاد خاصة بالتاجر
 *   (الأولوية للتاجر، ثم fallback للمنتج الأساسي)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إضافة width لجدول products (مفقود)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 10, 2)->nullable()->after('height')
                    ->comment('Product width in cm for volumetric weight calculation');
            }
        });

        // إضافة أعمدة الأبعاد لجدول merchant_products
        Schema::table('merchant_products', function (Blueprint $table) {
            if (!Schema::hasColumn('merchant_products', 'weight')) {
                $table->decimal('weight', 10, 3)->nullable()->after('stock')
                    ->comment('Product weight in kg (overrides product.weight)');
            }

            if (!Schema::hasColumn('merchant_products', 'length')) {
                $table->decimal('length', 10, 2)->nullable()->after('weight')
                    ->comment('Product length in cm (overrides product.length)');
            }

            if (!Schema::hasColumn('merchant_products', 'width')) {
                $table->decimal('width', 10, 2)->nullable()->after('length')
                    ->comment('Product width in cm (overrides product.width)');
            }

            if (!Schema::hasColumn('merchant_products', 'height')) {
                $table->decimal('height', 10, 2)->nullable()->after('width')
                    ->comment('Product height in cm (overrides product.height)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'width')) {
                $table->dropColumn('width');
            }
        });

        Schema::table('merchant_products', function (Blueprint $table) {
            $columns = ['weight', 'length', 'width', 'height'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('merchant_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
