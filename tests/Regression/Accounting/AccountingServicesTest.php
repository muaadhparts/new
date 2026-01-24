<?php

namespace Tests\Regression\Accounting;

use Tests\TestCase;
use App\Services\AccountLedgerService;
use App\Services\AccountingEntryService;
use App\Services\AccountingReportService;
use App\Services\MerchantAccountingService;
use App\Services\CourierAccountingService;
use App\Domain\Accounting\Services\AccountLedgerService as DomainAccountLedgerService;
use App\Domain\Accounting\Services\AccountingEntryService as DomainAccountingEntryService;
use App\Domain\Accounting\Services\AccountingReportService as DomainAccountingReportService;
use App\Domain\Accounting\Services\MerchantAccountingService as DomainMerchantAccountingService;
use App\Domain\Accounting\Services\CourierAccountingService as DomainCourierAccountingService;

/**
 * Regression Tests for Accounting Domain Services
 *
 * Phase 8: Services Migration
 *
 * This test ensures backward compatibility after moving services
 * from App\Services to App\Domain\Accounting\Services.
 */
class AccountingServicesTest extends TestCase
{
    // =========================================================================
    // BACKWARD COMPATIBILITY TESTS
    // =========================================================================

    /** @test */
    public function old_account_ledger_service_extends_domain_service()
    {
        $service = new AccountLedgerService();
        $this->assertInstanceOf(DomainAccountLedgerService::class, $service);
    }

    /** @test */
    public function old_accounting_entry_service_extends_domain_service()
    {
        $service = new AccountingEntryService();
        $this->assertInstanceOf(DomainAccountingEntryService::class, $service);
    }

    // =========================================================================
    // ACCOUNT LEDGER SERVICE TESTS
    // =========================================================================

    /** @test */
    public function account_ledger_service_has_party_management_methods()
    {
        $service = new AccountLedgerService();

        $this->assertTrue(method_exists($service, 'getPlatformParty'));
        $this->assertTrue(method_exists($service, 'getPartyFromReference'));
        $this->assertTrue(method_exists($service, 'syncMerchantParties'));
        $this->assertTrue(method_exists($service, 'syncCourierParties'));
        $this->assertTrue(method_exists($service, 'syncShippingProviders'));
        $this->assertTrue(method_exists($service, 'syncPaymentProviders'));
    }

    /** @test */
    public function account_ledger_service_has_debt_recording_methods()
    {
        $service = new AccountLedgerService();

        $this->assertTrue(method_exists($service, 'recordDebt'));
        $this->assertTrue(method_exists($service, 'recordFee'));
        $this->assertTrue(method_exists($service, 'recordDebtsForMerchantPurchase'));
    }

    /** @test */
    public function account_ledger_service_has_settlement_methods()
    {
        $service = new AccountLedgerService();

        $this->assertTrue(method_exists($service, 'recordSettlement'));
        $this->assertTrue(method_exists($service, 'createSettlementBatch'));
    }

    /** @test */
    public function account_ledger_service_has_report_methods()
    {
        $service = new AccountLedgerService();

        $this->assertTrue(method_exists($service, 'getPartySummary'));
        $this->assertTrue(method_exists($service, 'getAccountStatement'));
        $this->assertTrue(method_exists($service, 'getSummaryByPartyType'));
        $this->assertTrue(method_exists($service, 'getPlatformDashboard'));
    }

    // =========================================================================
    // ACCOUNTING ENTRY SERVICE TESTS
    // =========================================================================

    /** @test */
    public function accounting_entry_service_has_party_management_methods()
    {
        $service = new AccountingEntryService();

        $this->assertTrue(method_exists($service, 'getPlatformParty'));
        $this->assertTrue(method_exists($service, 'getOrCreateMerchantParty'));
        $this->assertTrue(method_exists($service, 'getOrCreateCourierParty'));
        $this->assertTrue(method_exists($service, 'getOrCreateShippingParty'));
        $this->assertTrue(method_exists($service, 'getTaxAuthorityParty'));
    }

    /** @test */
    public function accounting_entry_service_has_order_entry_methods()
    {
        $service = new AccountingEntryService();

        $this->assertTrue(method_exists($service, 'createOrderEntries'));
    }

    /** @test */
    public function accounting_entry_service_has_collection_methods()
    {
        $service = new AccountingEntryService();

        $this->assertTrue(method_exists($service, 'recordCodCollection'));
    }

    /** @test */
    public function accounting_entry_service_has_settlement_methods()
    {
        $service = new AccountingEntryService();

        $this->assertTrue(method_exists($service, 'recordSettlementToMerchant'));
        $this->assertTrue(method_exists($service, 'recordCourierSettlement'));
        $this->assertTrue(method_exists($service, 'recordShippingCompanySettlement'));
        $this->assertTrue(method_exists($service, 'recordShippingCompanySettlementToMerchant'));
    }

    /** @test */
    public function accounting_entry_service_has_cancellation_methods()
    {
        $service = new AccountingEntryService();

        $this->assertTrue(method_exists($service, 'reversePurchaseEntries'));
    }

    /** @test */
    public function accounting_entry_service_has_reporting_methods()
    {
        $service = new AccountingEntryService();

        $this->assertTrue(method_exists($service, 'getSalesSummaryFromLedger'));
        $this->assertTrue(method_exists($service, 'getMerchantStatement'));
        $this->assertTrue(method_exists($service, 'getTaxReport'));
        $this->assertTrue(method_exists($service, 'getReceivablesPayablesReport'));
    }

    // =========================================================================
    // DEPENDENCY INJECTION TESTS
    // =========================================================================

    /** @test */
    public function account_ledger_service_can_be_resolved_from_container()
    {
        $service = app(AccountLedgerService::class);
        $this->assertInstanceOf(DomainAccountLedgerService::class, $service);
    }

    /** @test */
    public function accounting_entry_service_can_be_resolved_from_container()
    {
        $service = app(AccountingEntryService::class);
        $this->assertInstanceOf(DomainAccountingEntryService::class, $service);
    }

    /** @test */
    public function domain_account_ledger_service_can_be_resolved_from_container()
    {
        $service = app(DomainAccountLedgerService::class);
        $this->assertInstanceOf(DomainAccountLedgerService::class, $service);
    }

    /** @test */
    public function domain_accounting_entry_service_can_be_resolved_from_container()
    {
        $service = app(DomainAccountingEntryService::class);
        $this->assertInstanceOf(DomainAccountingEntryService::class, $service);
    }

    // =========================================================================
    // ACCOUNTING REPORT SERVICE TESTS
    // =========================================================================

    /** @test */
    public function old_accounting_report_service_extends_domain_service()
    {
        $service = app(AccountingReportService::class);
        $this->assertInstanceOf(DomainAccountingReportService::class, $service);
    }

    /** @test */
    public function accounting_report_service_has_platform_report_method()
    {
        $service = new AccountingReportService(new AccountingEntryService());
        $this->assertTrue(method_exists($service, 'getPlatformReport'));
    }

    /** @test */
    public function accounting_report_service_has_tax_report_method()
    {
        $service = new AccountingReportService(new AccountingEntryService());
        $this->assertTrue(method_exists($service, 'getTaxReport'));
    }

    /** @test */
    public function accounting_report_service_has_receivables_payables_method()
    {
        $service = new AccountingReportService(new AccountingEntryService());
        $this->assertTrue(method_exists($service, 'getReceivablesPayablesReport'));
    }

    /** @test */
    public function accounting_report_service_has_merchants_summary_method()
    {
        $service = new AccountingReportService(new AccountingEntryService());
        $this->assertTrue(method_exists($service, 'getMerchantsSummaryReport'));
    }

    /** @test */
    public function accounting_report_service_has_couriers_report_method()
    {
        $service = new AccountingReportService(new AccountingEntryService());
        $this->assertTrue(method_exists($service, 'getCouriersReport'));
    }

    /** @test */
    public function accounting_report_service_has_shipping_companies_report_method()
    {
        $service = new AccountingReportService(new AccountingEntryService());
        $this->assertTrue(method_exists($service, 'getShippingCompaniesReport'));
    }

    // =========================================================================
    // MERCHANT ACCOUNTING SERVICE TESTS
    // =========================================================================

    /** @test */
    public function old_merchant_accounting_service_extends_domain_service()
    {
        $service = new MerchantAccountingService();
        $this->assertInstanceOf(DomainMerchantAccountingService::class, $service);
    }

    /** @test */
    public function merchant_accounting_service_has_merchant_report_method()
    {
        $service = new MerchantAccountingService();
        $this->assertTrue(method_exists($service, 'getMerchantReport'));
    }

    /** @test */
    public function merchant_accounting_service_has_merchant_statement_method()
    {
        $service = new MerchantAccountingService();
        $this->assertTrue(method_exists($service, 'getMerchantStatement'));
    }

    /** @test */
    public function merchant_accounting_service_has_merchant_tax_report_method()
    {
        $service = new MerchantAccountingService();
        $this->assertTrue(method_exists($service, 'getMerchantTaxReport'));
    }

    /** @test */
    public function merchant_accounting_service_has_admin_report_methods()
    {
        $service = new MerchantAccountingService();
        $this->assertTrue(method_exists($service, 'getAdminMerchantReport'));
        $this->assertTrue(method_exists($service, 'getAdminTaxReport'));
        $this->assertTrue(method_exists($service, 'getAdminCommissionReport'));
    }

    /** @test */
    public function merchant_accounting_service_has_helper_methods()
    {
        $service = new MerchantAccountingService();
        $this->assertTrue(method_exists($service, 'calculateCommission'));
        $this->assertTrue(method_exists($service, 'getMerchantTaxSetting'));
        $this->assertTrue(method_exists($service, 'getMerchantCommissionSetting'));
        $this->assertTrue(method_exists($service, 'hasMerchantPaymentGateway'));
        $this->assertTrue(method_exists($service, 'hasMerchantShipping'));
    }

    // =========================================================================
    // COURIER ACCOUNTING SERVICE TESTS
    // =========================================================================

    /** @test */
    public function old_courier_accounting_service_extends_domain_service()
    {
        $service = new CourierAccountingService();
        $this->assertInstanceOf(DomainCourierAccountingService::class, $service);
    }

    /** @test */
    public function courier_accounting_service_has_constants()
    {
        $this->assertEquals('pay_to_courier', CourierAccountingService::TYPE_PAY_TO_COURIER);
        $this->assertEquals('receive_from_courier', CourierAccountingService::TYPE_RECEIVE_FROM_COURIER);
    }

    /** @test */
    public function courier_accounting_service_has_courier_methods()
    {
        $service = new CourierAccountingService();
        $this->assertTrue(method_exists($service, 'getAvailableCouriersForCity'));
        $this->assertTrue(method_exists($service, 'canDeliverToCity'));
        $this->assertTrue(method_exists($service, 'getCouriersWithPricesForCity'));
    }

    /** @test */
    public function courier_accounting_service_has_recording_methods()
    {
        $service = new CourierAccountingService();
        $this->assertTrue(method_exists($service, 'recordCodCollection'));
        $this->assertTrue(method_exists($service, 'recordDeliveryFeeEarned'));
    }

    /** @test */
    public function courier_accounting_service_has_report_methods()
    {
        $service = new CourierAccountingService();
        $this->assertTrue(method_exists($service, 'getCourierBalance'));
        $this->assertTrue(method_exists($service, 'getCourierReport'));
        $this->assertTrue(method_exists($service, 'getAdminCouriersReport'));
    }

    /** @test */
    public function courier_accounting_service_has_settlement_methods()
    {
        $service = new CourierAccountingService();
        $this->assertTrue(method_exists($service, 'getUnsettledDeliveriesForCourier'));
        $this->assertTrue(method_exists($service, 'calculateSettlementAmount'));
        $this->assertTrue(method_exists($service, 'markDeliveryAsDelivered'));
    }

    // =========================================================================
    // CONTAINER RESOLUTION TESTS
    // =========================================================================

    /** @test */
    public function domain_accounting_report_service_can_be_resolved_from_container()
    {
        $service = app(DomainAccountingReportService::class);
        $this->assertInstanceOf(DomainAccountingReportService::class, $service);
    }

    /** @test */
    public function domain_merchant_accounting_service_can_be_resolved_from_container()
    {
        $service = app(DomainMerchantAccountingService::class);
        $this->assertInstanceOf(DomainMerchantAccountingService::class, $service);
    }

    /** @test */
    public function domain_courier_accounting_service_can_be_resolved_from_container()
    {
        $service = app(DomainCourierAccountingService::class);
        $this->assertInstanceOf(DomainCourierAccountingService::class, $service);
    }
}
