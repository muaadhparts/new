<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * إضافة عمود payment_method لتتبع طريقة الدفع (cod أو online)
     * يستخدم من قبل AccountingEntryService لتحديد نوع القيود المحاسبية
     */
    public function up(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->string('payment_method', 20)
                ->nullable()
                ->after('packing_owner_id')
                ->comment('Payment method: cod or online');
        });

        // تحديث السجلات الموجودة بناءً على payment_type
        \DB::statement("
            UPDATE merchant_purchases
            SET payment_method = CASE
                WHEN payment_type = 'platform' AND cod_amount > 0 THEN 'cod'
                WHEN cod_amount > 0 THEN 'cod'
                ELSE 'online'
            END
            WHERE payment_method IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_purchases', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
