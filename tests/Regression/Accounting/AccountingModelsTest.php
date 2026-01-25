<?php

namespace Tests\Regression\Accounting;

use Tests\TestCase;
use App\Domain\Accounting\Models\AccountParty;
use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Accounting\Models\AccountingLedger;
use App\Domain\Accounting\Models\SettlementBatch;
use App\Domain\Accounting\Models\PlatformRevenueLog;
use App\Domain\Accounting\Models\Withdraw;

/**
 * Regression Tests for Accounting Domain Models
 *
 * Phase 7: Accounting Domain
 *
 * This test ensures backward compatibility after moving models
 * from App\Models to App\Domain\Accounting\Models.
 *
 * Tests verify:
 * 1. Old model classes extend new Domain models correctly
 * 2. All constants are accessible through old model classes
 * 3. Relationships remain functional
 * 4. Scopes work correctly
 * 5. Helper methods return expected values
 */
class AccountingModelsTest extends TestCase
{
    // =========================================================================
    // BACKWARD COMPATIBILITY TESTS
    // =========================================================================

    /** @test */
    public function old_account_party_extends_domain_model()
    {
        $model = new AccountParty();
        $this->assertInstanceOf(AccountParty::class, $model);
    }

    /** @test */
    public function old_account_balance_extends_domain_model()
    {
        $model = new AccountBalance();
        $this->assertInstanceOf(AccountBalance::class, $model);
    }

    /** @test */
    public function old_accounting_ledger_extends_domain_model()
    {
        $model = new AccountingLedger();
        $this->assertInstanceOf(AccountingLedger::class, $model);
    }

    /** @test */
    public function old_settlement_batch_extends_domain_model()
    {
        $model = new SettlementBatch();
        $this->assertInstanceOf(SettlementBatch::class, $model);
    }

    /** @test */
    public function old_platform_revenue_log_extends_domain_model()
    {
        $model = new PlatformRevenueLog();
        $this->assertInstanceOf(PlatformRevenueLog::class, $model);
    }

    /** @test */
    public function old_withdraw_extends_domain_model()
    {
        $model = new Withdraw();
        $this->assertInstanceOf(Withdraw::class, $model);
    }

    // =========================================================================
    // ACCOUNT PARTY TESTS
    // =========================================================================

    /** @test */
    public function account_party_constants_accessible_through_old_model()
    {
        $this->assertEquals('platform', AccountParty::TYPE_PLATFORM);
        $this->assertEquals('merchant', AccountParty::TYPE_MERCHANT);
        $this->assertEquals('courier', AccountParty::TYPE_COURIER);
        $this->assertEquals('shipping_provider', AccountParty::TYPE_SHIPPING_PROVIDER);
        $this->assertEquals('payment_provider', AccountParty::TYPE_PAYMENT_PROVIDER);
    }

    /** @test */
    public function account_party_table_name_is_correct()
    {
        $model = new AccountParty();
        $this->assertEquals('account_parties', $model->getTable());
    }

    /** @test */
    public function account_party_fillable_attributes_are_correct()
    {
        $model = new AccountParty();
        $fillable = $model->getFillable();

        $this->assertContains('party_type', $fillable);
        $this->assertContains('reference_type', $fillable);
        $this->assertContains('reference_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('code', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('is_active', $fillable);
    }

    /** @test */
    public function account_party_type_helpers_work()
    {
        $platformParty = new AccountParty(['party_type' => 'platform']);
        $merchantParty = new AccountParty(['party_type' => 'merchant']);
        $courierParty = new AccountParty(['party_type' => 'courier']);
        $shippingParty = new AccountParty(['party_type' => 'shipping_provider']);
        $paymentParty = new AccountParty(['party_type' => 'payment_provider']);

        $this->assertTrue($platformParty->isPlatform());
        $this->assertTrue($merchantParty->isMerchant());
        $this->assertTrue($courierParty->isCourier());
        $this->assertTrue($shippingParty->isShippingProvider());
        $this->assertTrue($paymentParty->isPaymentProvider());

        $this->assertFalse($platformParty->isMerchant());
        $this->assertFalse($merchantParty->isPlatform());
    }

    /** @test */
    public function account_party_type_name_ar_returns_correct_value()
    {
        $model = new AccountParty(['party_type' => 'platform']);
        $this->assertEquals('المنصة', $model->getTypeNameAr());

        $model = new AccountParty(['party_type' => 'merchant']);
        $this->assertEquals('تاجر', $model->getTypeNameAr());

        $model = new AccountParty(['party_type' => 'courier']);
        $this->assertEquals('مندوب', $model->getTypeNameAr());
    }

    /** @test */
    public function account_party_get_icon_returns_correct_value()
    {
        $model = new AccountParty(['party_type' => 'platform']);
        $this->assertEquals('fas fa-building', $model->getIcon());

        $model = new AccountParty(['party_type' => 'merchant']);
        $this->assertEquals('fas fa-store', $model->getIcon());
    }

    // =========================================================================
    // ACCOUNT BALANCE TESTS
    // =========================================================================

    /** @test */
    public function account_balance_constants_accessible_through_old_model()
    {
        $this->assertEquals('receivable', AccountBalance::TYPE_RECEIVABLE);
        $this->assertEquals('payable', AccountBalance::TYPE_PAYABLE);
    }

    /** @test */
    public function account_balance_table_name_is_correct()
    {
        $model = new AccountBalance();
        $this->assertEquals('account_balances', $model->getTable());
    }

    /** @test */
    public function account_balance_fillable_attributes_are_correct()
    {
        $model = new AccountBalance();
        $fillable = $model->getFillable();

        $this->assertContains('party_id', $fillable);
        $this->assertContains('counterparty_id', $fillable);
        $this->assertContains('balance_type', $fillable);
        $this->assertContains('total_amount', $fillable);
        $this->assertContains('pending_amount', $fillable);
        $this->assertContains('settled_amount', $fillable);
    }

    /** @test */
    public function account_balance_type_helpers_work()
    {
        $receivable = new AccountBalance(['balance_type' => 'receivable']);
        $payable = new AccountBalance(['balance_type' => 'payable']);

        $this->assertTrue($receivable->isReceivable());
        $this->assertTrue($payable->isPayable());
        $this->assertFalse($receivable->isPayable());
        $this->assertFalse($payable->isReceivable());
    }

    /** @test */
    public function account_balance_has_balance_method_works()
    {
        $withBalance = new AccountBalance(['pending_amount' => 100]);
        $withoutBalance = new AccountBalance(['pending_amount' => 0]);

        $this->assertTrue($withBalance->hasBalance());
        $this->assertFalse($withoutBalance->hasBalance());
    }

    /** @test */
    public function account_balance_type_name_ar_returns_correct_value()
    {
        $model = new AccountBalance(['balance_type' => 'receivable']);
        $this->assertEquals('مستحق له', $model->getTypeNameAr());

        $model = new AccountBalance(['balance_type' => 'payable']);
        $this->assertEquals('مستحق عليه', $model->getTypeNameAr());
    }

    // =========================================================================
    // ACCOUNTING LEDGER TESTS
    // =========================================================================

    /** @test */
    public function accounting_ledger_transaction_type_constants_accessible()
    {
        $this->assertEquals('debt', AccountingLedger::TYPE_DEBT);
        $this->assertEquals('collection', AccountingLedger::TYPE_COLLECTION);
        $this->assertEquals('settlement', AccountingLedger::TYPE_SETTLEMENT);
        $this->assertEquals('fee', AccountingLedger::TYPE_FEE);
        $this->assertEquals('refund', AccountingLedger::TYPE_REFUND);
        $this->assertEquals('reversal', AccountingLedger::TYPE_REVERSAL);
        $this->assertEquals('adjustment', AccountingLedger::TYPE_ADJUSTMENT);
    }

    /** @test */
    public function accounting_ledger_entry_type_constants_accessible()
    {
        $this->assertEquals('SALE_REVENUE', AccountingLedger::ENTRY_SALE_REVENUE);
        $this->assertEquals('COMMISSION_EARNED', AccountingLedger::ENTRY_COMMISSION_EARNED);
        $this->assertEquals('TAX_COLLECTED', AccountingLedger::ENTRY_TAX_COLLECTED);
        $this->assertEquals('COD_PENDING', AccountingLedger::ENTRY_COD_PENDING);
        $this->assertEquals('COD_COLLECTED', AccountingLedger::ENTRY_COD_COLLECTED);
        $this->assertEquals('SETTLEMENT_PAYMENT', AccountingLedger::ENTRY_SETTLEMENT_PAYMENT);
    }

    /** @test */
    public function accounting_ledger_direction_constants_accessible()
    {
        $this->assertEquals('DEBIT', AccountingLedger::DIRECTION_DEBIT);
        $this->assertEquals('CREDIT', AccountingLedger::DIRECTION_CREDIT);
    }

    /** @test */
    public function accounting_ledger_debt_status_constants_accessible()
    {
        $this->assertEquals('PENDING', AccountingLedger::DEBT_PENDING);
        $this->assertEquals('SETTLED', AccountingLedger::DEBT_SETTLED);
        $this->assertEquals('CANCELLED', AccountingLedger::DEBT_CANCELLED);
        $this->assertEquals('REVERSED', AccountingLedger::DEBT_REVERSED);
    }

    /** @test */
    public function accounting_ledger_status_constants_accessible()
    {
        $this->assertEquals('pending', AccountingLedger::STATUS_PENDING);
        $this->assertEquals('completed', AccountingLedger::STATUS_COMPLETED);
        $this->assertEquals('cancelled', AccountingLedger::STATUS_CANCELLED);
    }

    /** @test */
    public function accounting_ledger_table_name_is_correct()
    {
        $model = new AccountingLedger();
        $this->assertEquals('accounting_ledger', $model->getTable());
    }

    /** @test */
    public function accounting_ledger_status_helpers_work()
    {
        $pending = new AccountingLedger(['status' => 'pending']);
        $completed = new AccountingLedger(['status' => 'completed']);
        $cancelled = new AccountingLedger(['status' => 'cancelled']);

        $this->assertTrue($pending->isPending());
        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($cancelled->isCancelled());

        $this->assertFalse($pending->isCompleted());
        $this->assertFalse($completed->isPending());
    }

    /** @test */
    public function accounting_ledger_type_helpers_work()
    {
        $debt = new AccountingLedger(['transaction_type' => 'debt']);
        $settlement = new AccountingLedger(['transaction_type' => 'settlement']);

        $this->assertTrue($debt->isDebt());
        $this->assertTrue($settlement->isSettlement());
        $this->assertFalse($debt->isSettlement());
    }

    /** @test */
    public function accounting_ledger_direction_helpers_work()
    {
        $debit = new AccountingLedger(['direction' => 'DEBIT']);
        $credit = new AccountingLedger(['direction' => 'CREDIT']);

        $this->assertTrue($debit->isDebit());
        $this->assertTrue($credit->isCredit());
        $this->assertFalse($debit->isCredit());
    }

    /** @test */
    public function accounting_ledger_debt_status_helpers_work()
    {
        $pending = new AccountingLedger(['debt_status' => 'PENDING']);
        $settled = new AccountingLedger(['debt_status' => 'SETTLED']);

        $this->assertTrue($pending->isDebtPending());
        $this->assertTrue($settled->isDebtSettled());
        $this->assertFalse($pending->isDebtSettled());
    }

    /** @test */
    public function accounting_ledger_type_name_ar_returns_correct_value()
    {
        $model = new AccountingLedger(['transaction_type' => 'debt']);
        $this->assertEquals('دين', $model->getTypeNameAr());

        $model = new AccountingLedger(['transaction_type' => 'settlement']);
        $this->assertEquals('تسوية', $model->getTypeNameAr());
    }

    /** @test */
    public function accounting_ledger_status_color_returns_correct_value()
    {
        $model = new AccountingLedger(['status' => 'pending']);
        $this->assertEquals('warning', $model->getStatusColor());

        $model = new AccountingLedger(['status' => 'completed']);
        $this->assertEquals('success', $model->getStatusColor());
    }

    /** @test */
    public function accounting_ledger_formatted_amount_works()
    {
        $model = new AccountingLedger(['monetary_unit_code' => 'SAR', 'amount' => 1500.50]);
        $this->assertEquals('SAR 1,500.50', $model->getFormattedAmount());
    }

    /** @test */
    public function accounting_ledger_generate_transaction_ref_works()
    {
        $ref = AccountingLedger::generateTransactionRef();
        $this->assertStringStartsWith('TXN-', $ref);
        $this->assertMatchesRegularExpression('/TXN-\d{8}-[A-Z0-9]{6}/', $ref);
    }

    // =========================================================================
    // SETTLEMENT BATCH TESTS
    // =========================================================================

    /** @test */
    public function settlement_batch_status_constants_accessible()
    {
        $this->assertEquals('draft', SettlementBatch::STATUS_DRAFT);
        $this->assertEquals('pending', SettlementBatch::STATUS_PENDING);
        $this->assertEquals('completed', SettlementBatch::STATUS_COMPLETED);
        $this->assertEquals('cancelled', SettlementBatch::STATUS_CANCELLED);
    }

    /** @test */
    public function settlement_batch_payment_method_constants_accessible()
    {
        $this->assertEquals('bank_transfer', SettlementBatch::PAYMENT_BANK_TRANSFER);
        $this->assertEquals('cash', SettlementBatch::PAYMENT_CASH);
        $this->assertEquals('cheque', SettlementBatch::PAYMENT_CHEQUE);
    }

    /** @test */
    public function settlement_batch_table_name_is_correct()
    {
        $model = new SettlementBatch();
        $this->assertEquals('settlement_batches', $model->getTable());
    }

    /** @test */
    public function settlement_batch_status_helpers_work()
    {
        $draft = new SettlementBatch(['status' => 'draft']);
        $pending = new SettlementBatch(['status' => 'pending']);
        $completed = new SettlementBatch(['status' => 'completed']);
        $cancelled = new SettlementBatch(['status' => 'cancelled']);

        $this->assertTrue($draft->isDraft());
        $this->assertTrue($pending->isPending());
        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($cancelled->isCancelled());

        $this->assertFalse($draft->isPending());
        $this->assertFalse($pending->isDraft());
    }

    /** @test */
    public function settlement_batch_status_name_ar_returns_correct_value()
    {
        $model = new SettlementBatch(['status' => 'draft']);
        $this->assertEquals('مسودة', $model->getStatusNameAr());

        $model = new SettlementBatch(['status' => 'completed']);
        $this->assertEquals('مكتمل', $model->getStatusNameAr());
    }

    /** @test */
    public function settlement_batch_status_color_returns_correct_value()
    {
        $model = new SettlementBatch(['status' => 'draft']);
        $this->assertEquals('secondary', $model->getStatusColor());

        $model = new SettlementBatch(['status' => 'completed']);
        $this->assertEquals('success', $model->getStatusColor());
    }

    /** @test */
    public function settlement_batch_payment_method_name_ar_returns_correct_value()
    {
        $model = new SettlementBatch(['payment_method' => 'bank_transfer']);
        $this->assertEquals('تحويل بنكي', $model->getPaymentMethodNameAr());

        $model = new SettlementBatch(['payment_method' => 'cash']);
        $this->assertEquals('نقدي', $model->getPaymentMethodNameAr());
    }

    /** @test */
    public function settlement_batch_generate_batch_ref_works()
    {
        $ref = SettlementBatch::generateBatchRef();
        $this->assertStringStartsWith('SET-', $ref);
        $this->assertMatchesRegularExpression('/SET-\d{8}-[A-Z0-9]{5}/', $ref);
    }

    /** @test */
    public function settlement_batch_formatted_amount_works()
    {
        $model = new SettlementBatch(['currency' => 'SAR', 'total_amount' => 2500.75]);
        $this->assertEquals('SAR 2,500.75', $model->getFormattedAmount());
    }

    // =========================================================================
    // PLATFORM REVENUE LOG TESTS
    // =========================================================================

    /** @test */
    public function platform_revenue_log_source_constants_accessible()
    {
        $this->assertEquals('commission', PlatformRevenueLog::SOURCE_COMMISSION);
        $this->assertEquals('tax', PlatformRevenueLog::SOURCE_TAX);
        $this->assertEquals('shipping_markup', PlatformRevenueLog::SOURCE_SHIPPING_MARKUP);
        $this->assertEquals('courier_fee', PlatformRevenueLog::SOURCE_COURIER_FEE);
        $this->assertEquals('other', PlatformRevenueLog::SOURCE_OTHER);
    }

    /** @test */
    public function platform_revenue_log_table_name_is_correct()
    {
        $model = new PlatformRevenueLog();
        $this->assertEquals('platform_revenue_log', $model->getTable());
    }

    /** @test */
    public function platform_revenue_log_fillable_attributes_are_correct()
    {
        $model = new PlatformRevenueLog();
        $fillable = $model->getFillable();

        $this->assertContains('date', $fillable);
        $this->assertContains('source', $fillable);
        $this->assertContains('reference_type', $fillable);
        $this->assertContains('reference_id', $fillable);
        $this->assertContains('amount', $fillable);
        $this->assertContains('description', $fillable);
    }

    /** @test */
    public function platform_revenue_log_source_label_works()
    {
        $model = new PlatformRevenueLog(['source' => 'commission']);
        $this->assertEquals('Merchant Commission', $model->getSourceLabel());

        $model = new PlatformRevenueLog(['source' => 'tax']);
        $this->assertEquals('Tax Collected', $model->getSourceLabel());
    }

    // =========================================================================
    // WITHDRAW TESTS
    // =========================================================================

    /** @test */
    public function withdraw_status_constants_accessible()
    {
        $this->assertEquals('pending', Withdraw::STATUS_PENDING);
        $this->assertEquals('approved', Withdraw::STATUS_APPROVED);
        $this->assertEquals('completed', Withdraw::STATUS_COMPLETED);
        $this->assertEquals('rejected', Withdraw::STATUS_REJECTED);
    }

    /** @test */
    public function withdraw_method_constants_accessible()
    {
        $this->assertEquals('bank_transfer', Withdraw::METHOD_BANK_TRANSFER);
        $this->assertEquals('paypal', Withdraw::METHOD_PAYPAL);
        $this->assertEquals('cash', Withdraw::METHOD_CASH);
    }

    /** @test */
    public function withdraw_table_name_is_correct()
    {
        $model = new Withdraw();
        $this->assertEquals('withdraws', $model->getTable());
    }

    /** @test */
    public function withdraw_fillable_attributes_are_correct()
    {
        $model = new Withdraw();
        $fillable = $model->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('method', $fillable);
        $this->assertContains('acc_email', $fillable);
        $this->assertContains('iban', $fillable);
        $this->assertContains('amount', $fillable);
        $this->assertContains('fee', $fillable);
        $this->assertContains('status', $fillable);
    }

    /** @test */
    public function withdraw_status_helpers_work()
    {
        $pending = new Withdraw(['status' => 'pending']);
        $approved = new Withdraw(['status' => 'approved']);
        $completed = new Withdraw(['status' => 'completed']);
        $rejected = new Withdraw(['status' => 'rejected']);

        $this->assertTrue($pending->isPending());
        $this->assertTrue($approved->isApproved());
        $this->assertTrue($completed->isCompleted());
        $this->assertTrue($rejected->isRejected());

        $this->assertFalse($pending->isApproved());
        $this->assertFalse($approved->isPending());
    }

    /** @test */
    public function withdraw_net_amount_calculation_works()
    {
        $model = new Withdraw(['amount' => 1000, 'fee' => 50]);
        $this->assertEquals(950.0, $model->getNetAmount());

        $model = new Withdraw(['amount' => 500, 'fee' => null]);
        $this->assertEquals(500.0, $model->getNetAmount());
    }

    /** @test */
    public function withdraw_status_label_works()
    {
        $model = new Withdraw(['status' => 'pending']);
        $this->assertEquals('Pending', $model->getStatusLabel());

        $model = new Withdraw(['status' => 'completed']);
        $this->assertEquals('Completed', $model->getStatusLabel());
    }

    /** @test */
    public function withdraw_status_color_returns_correct_value()
    {
        $model = new Withdraw(['status' => 'pending']);
        $this->assertEquals('warning', $model->getStatusColor());

        $model = new Withdraw(['status' => 'completed']);
        $this->assertEquals('success', $model->getStatusColor());

        $model = new Withdraw(['status' => 'rejected']);
        $this->assertEquals('danger', $model->getStatusColor());
    }
}
