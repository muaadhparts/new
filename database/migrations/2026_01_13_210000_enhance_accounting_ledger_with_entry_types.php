<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Enhance Accounting Ledger with Entry Types
 *
 * Adds specific entry types for proper journal entries:
 * - SALE_REVENUE, COMMISSION_EARNED, TAX_COLLECTED, etc.
 * - Direction (DEBIT/CREDIT)
 * - Debt status tracking
 * - Tax Authority party
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_ledger', function (Blueprint $table) {
            // نوع القيد المحاسبي المحدد
            $table->string('entry_type', 50)->after('transaction_type')->nullable()
                ->comment('Specific entry type: SALE_REVENUE, COMMISSION_EARNED, TAX_COLLECTED, etc.');

            // اتجاه القيد (مدين/دائن)
            $table->enum('direction', ['DEBIT', 'CREDIT'])->after('entry_type')->default('CREDIT');

            // حالة الدين
            $table->enum('debt_status', [
                'PENDING',      // معلق
                'SETTLED',      // مسدد
                'CANCELLED',    // ملغي
                'REVERSED'      // معكوس
            ])->after('status')->default('PENDING');

            // مرجع للقيد الأصلي (في حالة العكس)
            $table->unsignedBigInteger('original_entry_id')->nullable()->after('debt_status');
            $table->foreign('original_entry_id')
                ->references('id')
                ->on('accounting_ledger')
                ->onDelete('set null');

            // Index للبحث
            $table->index('entry_type');
            $table->index('debt_status');
            $table->index('direction');
        });

        // إضافة نوع "الجهة الضريبية" للأطراف
        Schema::table('account_parties', function (Blueprint $table) {
            // نحتاج تعديل الـ enum - في MySQL لا يمكن تعديل enum بسهولة
            // لذا نستخدم طريقة بديلة
        });

        // تحويل party_type إلى string بدلاً من enum للمرونة
        DB::statement("ALTER TABLE account_parties MODIFY COLUMN party_type VARCHAR(50) NOT NULL");

        // إدراج طرف الجهة الضريبية
        $taxAuthorityExists = DB::table('account_parties')
            ->where('code', 'tax_authority')
            ->exists();

        if (!$taxAuthorityExists) {
            DB::table('account_parties')->insert([
                'party_type' => 'tax_authority',
                'reference_type' => null,
                'reference_id' => null,
                'name' => 'Tax Authority',
                'code' => 'tax_authority',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // تحديث القيود الموجودة لإضافة entry_type بناءً على transaction_type
        DB::statement("
            UPDATE accounting_ledger
            SET entry_type = CASE
                WHEN transaction_type = 'debt' AND JSON_EXTRACT(metadata, '$.type') = 'sale' THEN 'SALE_REVENUE'
                WHEN transaction_type = 'debt' AND JSON_EXTRACT(metadata, '$.type') = 'commission' THEN 'COMMISSION_EARNED'
                WHEN transaction_type = 'fee' AND JSON_EXTRACT(metadata, '$.fee_type') = 'tax_collected' THEN 'TAX_COLLECTED'
                WHEN transaction_type = 'fee' AND JSON_EXTRACT(metadata, '$.fee_type') = 'shipping' THEN 'SHIPPING_FEE'
                WHEN transaction_type = 'collection' THEN 'COD_COLLECTED'
                WHEN transaction_type = 'settlement' THEN 'SETTLEMENT_PAYMENT'
                WHEN transaction_type = 'refund' THEN 'REFUND'
                WHEN transaction_type = 'reversal' THEN 'CANCELLATION_REVERSAL'
                WHEN transaction_type = 'adjustment' THEN 'ADJUSTMENT'
                ELSE 'GENERAL'
            END
            WHERE entry_type IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('accounting_ledger', function (Blueprint $table) {
            $table->dropForeign(['original_entry_id']);
            $table->dropIndex(['entry_type']);
            $table->dropIndex(['debt_status']);
            $table->dropIndex(['direction']);
            $table->dropColumn(['entry_type', 'direction', 'debt_status', 'original_entry_id']);
        });

        // إزالة طرف الجهة الضريبية
        DB::table('account_parties')->where('code', 'tax_authority')->delete();
    }
};
