<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting System Tables
 *
 * Creates the complete accounting ledger system with:
 * - account_parties: Dynamic parties (merchants, couriers, shipping companies, payment providers)
 * - accounting_ledger: Full transaction log with double-entry style
 * - account_balances: Cached balances for quick reporting
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. ACCOUNT PARTIES - الأطراف المحاسبية
        // ═══════════════════════════════════════════════════════════════
        Schema::create('account_parties', function (Blueprint $table) {
            $table->id();

            // نوع الطرف
            $table->enum('party_type', [
                'platform',           // المنصة (واحد فقط)
                'merchant',           // التجار (من users)
                'courier',            // المناديب (من couriers)
                'shipping_provider',  // شركات الشحن (tryoto, aramex, etc)
                'payment_provider',   // شركات الدفع (stripe, myfatoorah, etc)
            ]);

            // ربط ديناميكي مع الجدول المصدر
            $table->string('reference_type', 100)->nullable()
                ->comment('Source table: users, couriers, shippings, etc');
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('ID in source table');

            // معلومات الطرف
            $table->string('name', 255);
            $table->string('code', 100)->unique()
                ->comment('Unique code: platform, merchant_5, courier_12, shipping_tryoto');
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();

            // الحالة
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('party_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('is_active');
        });

        // ═══════════════════════════════════════════════════════════════
        // 2. ACCOUNTING LEDGER - سجل الحركات المالية
        // ═══════════════════════════════════════════════════════════════
        Schema::create('accounting_ledger', function (Blueprint $table) {
            $table->id();

            // مرجع المعاملة
            $table->string('transaction_ref', 50)->unique()
                ->comment('Unique transaction reference: TXN-20260113-XXXXX');

            // ربط مع الطلبات
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('merchant_purchase_id')->nullable();

            // الأطراف
            $table->foreignId('from_party_id')
                ->constrained('account_parties')
                ->onDelete('restrict');
            $table->foreignId('to_party_id')
                ->constrained('account_parties')
                ->onDelete('restrict');

            // المبلغ
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('SAR');

            // نوع المعاملة
            $table->enum('transaction_type', [
                'debt',           // دين جديد (عند الشيك-آوت)
                'collection',     // تحصيل COD
                'settlement',     // تسوية دين
                'fee',            // رسوم (عمولة، توصيل، إلخ)
                'refund',         // استرداد
                'reversal',       // إلغاء/عكس
                'adjustment',     // تعديل يدوي
            ]);

            // الوصف
            $table->string('description', 500)->nullable();
            $table->string('description_ar', 500)->nullable();

            // بيانات إضافية
            $table->json('metadata')->nullable()
                ->comment('Additional data: payment_method, tracking_number, etc');

            // الحالة
            $table->enum('status', [
                'pending',    // في الانتظار
                'completed',  // مكتمل
                'cancelled',  // ملغي
            ])->default('pending');

            // التواريخ
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            // من أنشأ/عدّل
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('settled_by')->nullable();

            // Indexes
            $table->index('purchase_id');
            $table->index('merchant_purchase_id');
            $table->index('transaction_type');
            $table->index('status');
            $table->index('transaction_date');
            $table->index(['from_party_id', 'to_party_id']);
            $table->index(['to_party_id', 'from_party_id']);
        });

        // ═══════════════════════════════════════════════════════════════
        // 3. ACCOUNT BALANCES - أرصدة محسوبة (للتقارير السريعة)
        // ═══════════════════════════════════════════════════════════════
        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();

            // الطرفان
            $table->foreignId('party_id')
                ->constrained('account_parties')
                ->onDelete('cascade');
            $table->foreignId('counterparty_id')
                ->constrained('account_parties')
                ->onDelete('cascade');

            // نوع الرصيد
            $table->enum('balance_type', [
                'receivable',  // مستحق للطرف (party_id يستحق من counterparty_id)
                'payable',     // مستحق على الطرف (party_id مدين لـ counterparty_id)
            ]);

            // المبالغ
            $table->decimal('total_amount', 12, 2)->default(0)
                ->comment('Total outstanding balance');
            $table->decimal('pending_amount', 12, 2)->default(0)
                ->comment('Amount pending collection/settlement');
            $table->decimal('settled_amount', 12, 2)->default(0)
                ->comment('Amount already settled');
            $table->string('currency', 10)->default('SAR');

            // عدد المعاملات
            $table->unsignedInteger('transaction_count')->default(0);

            // آخر تحديث
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['party_id', 'counterparty_id', 'balance_type'], 'unique_balance');

            // Indexes
            $table->index(['party_id', 'balance_type']);
            $table->index(['counterparty_id', 'balance_type']);
        });

        // ═══════════════════════════════════════════════════════════════
        // 4. SETTLEMENT BATCHES - دفعات التسوية
        // ═══════════════════════════════════════════════════════════════
        Schema::create('settlement_batches', function (Blueprint $table) {
            $table->id();

            // مرجع التسوية
            $table->string('batch_ref', 50)->unique()
                ->comment('Unique batch reference: SET-20260113-XXXXX');

            // الأطراف
            $table->foreignId('from_party_id')
                ->constrained('account_parties')
                ->onDelete('restrict');
            $table->foreignId('to_party_id')
                ->constrained('account_parties')
                ->onDelete('restrict');

            // المبلغ الإجمالي
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 10)->default('SAR');

            // تفاصيل الدفع
            $table->string('payment_method', 100)->nullable()
                ->comment('bank_transfer, cash, wallet, etc');
            $table->string('payment_reference', 255)->nullable()
                ->comment('Bank transfer ref, cheque number, etc');
            $table->text('notes')->nullable();

            // الحالة
            $table->enum('status', [
                'draft',      // مسودة
                'pending',    // في انتظار التأكيد
                'completed',  // مكتمل
                'cancelled',  // ملغي
            ])->default('draft');

            // التواريخ
            $table->date('settlement_date')->nullable();
            $table->timestamps();

            // من أنشأ/عدّل
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            // Indexes
            $table->index(['from_party_id', 'to_party_id']);
            $table->index('status');
            $table->index('settlement_date');
        });

        // ربط الـ ledger entries بـ settlement batches
        Schema::table('accounting_ledger', function (Blueprint $table) {
            $table->unsignedBigInteger('settlement_batch_id')->nullable()->after('settled_by');
            $table->foreign('settlement_batch_id')
                ->references('id')
                ->on('settlement_batches')
                ->onDelete('set null');
        });

        // ═══════════════════════════════════════════════════════════════
        // 5. INSERT PLATFORM PARTY - إدراج طرف المنصة
        // ═══════════════════════════════════════════════════════════════
        DB::table('account_parties')->insert([
            'party_type' => 'platform',
            'reference_type' => null,
            'reference_id' => null,
            'name' => 'Platform',
            'code' => 'platform',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('accounting_ledger', function (Blueprint $table) {
            $table->dropForeign(['settlement_batch_id']);
            $table->dropColumn('settlement_batch_id');
        });

        Schema::dropIfExists('settlement_batches');
        Schema::dropIfExists('account_balances');
        Schema::dropIfExists('accounting_ledger');
        Schema::dropIfExists('account_parties');
    }
};
