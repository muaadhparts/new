<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use App\Domain\Commerce\Enums\PurchaseStatus;
use App\Domain\Commerce\Enums\PaymentStatus;
use App\Domain\Commerce\Enums\PaymentMethod;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\ShippingType;
use App\Domain\Merchant\Enums\MerchantStatus;
use App\Domain\Merchant\Enums\StockStatus;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Enums\VerificationStatus;
use App\Domain\Catalog\Enums\ReviewStatus;
use App\Domain\Catalog\Enums\CategoryLevel;
use App\Domain\Accounting\Enums\TransactionType;
use App\Domain\Accounting\Enums\WithdrawalStatus;
use App\Domain\Platform\Enums\Language;
use App\Domain\Platform\Enums\Currency;

/**
 * Phase 25: Domain Enums Tests
 *
 * Tests for PHP 8.1+ enums across all domains.
 */
class DomainEnumsTest extends TestCase
{
    // ============================================
    // Commerce Domain Enums
    // ============================================

    /** @test */
    public function purchase_status_enum_exists()
    {
        $this->assertTrue(enum_exists(PurchaseStatus::class));
    }

    /** @test */
    public function purchase_status_has_all_cases()
    {
        $cases = PurchaseStatus::cases();
        $this->assertCount(7, $cases);
        $this->assertContains(PurchaseStatus::PENDING, $cases);
        $this->assertContains(PurchaseStatus::DELIVERED, $cases);
    }

    /** @test */
    public function purchase_status_has_label()
    {
        $this->assertEquals('قيد الانتظار', PurchaseStatus::PENDING->label());
        $this->assertEquals('تم التوصيل', PurchaseStatus::DELIVERED->label());
    }

    /** @test */
    public function purchase_status_has_color()
    {
        $this->assertEquals('warning', PurchaseStatus::PENDING->color());
        $this->assertEquals('success', PurchaseStatus::DELIVERED->color());
    }

    /** @test */
    public function purchase_status_can_be_cancelled_check()
    {
        $this->assertTrue(PurchaseStatus::PENDING->canBeCancelled());
        $this->assertFalse(PurchaseStatus::DELIVERED->canBeCancelled());
    }

    /** @test */
    public function purchase_status_is_complete_check()
    {
        $this->assertFalse(PurchaseStatus::PENDING->isComplete());
        $this->assertTrue(PurchaseStatus::DELIVERED->isComplete());
    }

    /** @test */
    public function purchase_status_next_statuses()
    {
        $next = PurchaseStatus::PENDING->nextStatuses();
        $this->assertContains(PurchaseStatus::CONFIRMED, $next);
        $this->assertContains(PurchaseStatus::CANCELLED, $next);
    }

    /** @test */
    public function payment_status_enum_exists()
    {
        $this->assertTrue(enum_exists(PaymentStatus::class));
    }

    /** @test */
    public function payment_status_is_successful()
    {
        $this->assertTrue(PaymentStatus::COMPLETED->isSuccessful());
        $this->assertFalse(PaymentStatus::PENDING->isSuccessful());
    }

    /** @test */
    public function payment_status_is_final()
    {
        $this->assertTrue(PaymentStatus::COMPLETED->isFinal());
        $this->assertFalse(PaymentStatus::PENDING->isFinal());
    }

    /** @test */
    public function payment_method_enum_exists()
    {
        $this->assertTrue(enum_exists(PaymentMethod::class));
    }

    /** @test */
    public function payment_method_has_label()
    {
        $this->assertEquals('الدفع عند الاستلام', PaymentMethod::CASH_ON_DELIVERY->label());
        $this->assertEquals('مدى', PaymentMethod::MADA->label());
    }

    /** @test */
    public function payment_method_requires_online()
    {
        $this->assertFalse(PaymentMethod::CASH_ON_DELIVERY->requiresOnlineProcessing());
        $this->assertTrue(PaymentMethod::MADA->requiresOnlineProcessing());
    }

    /** @test */
    public function payment_method_is_installment()
    {
        $this->assertTrue(PaymentMethod::TABBY->isInstallment());
        $this->assertFalse(PaymentMethod::MADA->isInstallment());
    }

    // ============================================
    // Shipping Domain Enums
    // ============================================

    /** @test */
    public function shipment_status_enum_exists()
    {
        $this->assertTrue(enum_exists(ShipmentStatus::class));
    }

    /** @test */
    public function shipment_status_has_label()
    {
        $this->assertEquals('في الطريق', ShipmentStatus::IN_TRANSIT->label());
        $this->assertEquals('تم التوصيل', ShipmentStatus::DELIVERED->label());
    }

    /** @test */
    public function shipment_status_is_complete()
    {
        $this->assertTrue(ShipmentStatus::DELIVERED->isComplete());
        $this->assertFalse(ShipmentStatus::IN_TRANSIT->isComplete());
    }

    /** @test */
    public function shipment_status_progress()
    {
        $this->assertEquals(50, ShipmentStatus::IN_TRANSIT->progress());
        $this->assertEquals(100, ShipmentStatus::DELIVERED->progress());
    }

    /** @test */
    public function shipping_type_enum_exists()
    {
        $this->assertTrue(enum_exists(ShippingType::class));
    }

    /** @test */
    public function shipping_type_has_label()
    {
        $this->assertEquals('شحن عادي', ShippingType::STANDARD->label());
        $this->assertEquals('شحن سريع', ShippingType::EXPRESS->label());
    }

    /** @test */
    public function shipping_type_requires_address()
    {
        $this->assertTrue(ShippingType::STANDARD->requiresAddress());
        $this->assertFalse(ShippingType::PICKUP->requiresAddress());
    }

    /** @test */
    public function shipping_type_has_estimated_days()
    {
        $this->assertEquals('3-5 أيام', ShippingType::STANDARD->estimatedDays());
        $this->assertEquals('نفس اليوم', ShippingType::SAME_DAY->estimatedDays());
    }

    // ============================================
    // Merchant Domain Enums
    // ============================================

    /** @test */
    public function merchant_status_enum_exists()
    {
        $this->assertTrue(enum_exists(MerchantStatus::class));
    }

    /** @test */
    public function merchant_status_has_label()
    {
        $this->assertEquals('نشط', MerchantStatus::ACTIVE->label());
        $this->assertEquals('موقوف', MerchantStatus::SUSPENDED->label());
    }

    /** @test */
    public function merchant_status_can_sell()
    {
        $this->assertTrue(MerchantStatus::ACTIVE->canSell());
        $this->assertFalse(MerchantStatus::SUSPENDED->canSell());
    }

    /** @test */
    public function merchant_status_can_login()
    {
        $this->assertTrue(MerchantStatus::ACTIVE->canLogin());
        $this->assertFalse(MerchantStatus::BANNED->canLogin());
    }

    /** @test */
    public function stock_status_enum_exists()
    {
        $this->assertTrue(enum_exists(StockStatus::class));
    }

    /** @test */
    public function stock_status_has_label()
    {
        $this->assertEquals('متوفر', StockStatus::IN_STOCK->label());
        $this->assertEquals('غير متوفر', StockStatus::OUT_OF_STOCK->label());
    }

    /** @test */
    public function stock_status_is_purchasable()
    {
        $this->assertTrue(StockStatus::IN_STOCK->isPurchasable());
        $this->assertFalse(StockStatus::OUT_OF_STOCK->isPurchasable());
    }

    /** @test */
    public function stock_status_from_quantity()
    {
        $this->assertEquals(StockStatus::OUT_OF_STOCK, StockStatus::fromQuantity(0));
        $this->assertEquals(StockStatus::LOW_STOCK, StockStatus::fromQuantity(3));
        $this->assertEquals(StockStatus::IN_STOCK, StockStatus::fromQuantity(100));
    }

    // ============================================
    // Identity Domain Enums
    // ============================================

    /** @test */
    public function user_role_enum_exists()
    {
        $this->assertTrue(enum_exists(UserRole::class));
    }

    /** @test */
    public function user_role_has_label()
    {
        $this->assertEquals('عميل', UserRole::CUSTOMER->label());
        $this->assertEquals('تاجر', UserRole::MERCHANT->label());
    }

    /** @test */
    public function user_role_has_guard()
    {
        $this->assertEquals('web', UserRole::CUSTOMER->guard());
        $this->assertEquals('admin', UserRole::OPERATOR->guard());
    }

    /** @test */
    public function user_role_can_manage_orders()
    {
        $this->assertTrue(UserRole::MERCHANT->canManageOrders());
        $this->assertFalse(UserRole::CUSTOMER->canManageOrders());
    }

    /** @test */
    public function verification_status_enum_exists()
    {
        $this->assertTrue(enum_exists(VerificationStatus::class));
    }

    /** @test */
    public function verification_status_is_verified()
    {
        $this->assertTrue(VerificationStatus::VERIFIED->isVerified());
        $this->assertFalse(VerificationStatus::PENDING->isVerified());
    }

    /** @test */
    public function verification_status_can_resend()
    {
        $this->assertTrue(VerificationStatus::PENDING->canResend());
        $this->assertFalse(VerificationStatus::VERIFIED->canResend());
    }

    // ============================================
    // Catalog Domain Enums
    // ============================================

    /** @test */
    public function review_status_enum_exists()
    {
        $this->assertTrue(enum_exists(ReviewStatus::class));
    }

    /** @test */
    public function review_status_has_label()
    {
        $this->assertEquals('معتمد', ReviewStatus::APPROVED->label());
        $this->assertEquals('مرفوض', ReviewStatus::REJECTED->label());
    }

    /** @test */
    public function review_status_is_visible()
    {
        $this->assertTrue(ReviewStatus::APPROVED->isVisible());
        $this->assertFalse(ReviewStatus::PENDING->isVisible());
    }

    /** @test */
    public function category_level_enum_exists()
    {
        $this->assertTrue(enum_exists(CategoryLevel::class));
    }

    /** @test */
    public function category_level_has_depth()
    {
        $this->assertEquals(0, CategoryLevel::ROOT->depth());
        $this->assertEquals(3, CategoryLevel::CHILD->depth());
    }

    /** @test */
    public function category_level_can_have_children()
    {
        $this->assertTrue(CategoryLevel::MAIN->canHaveChildren());
        $this->assertFalse(CategoryLevel::CHILD->canHaveChildren());
    }

    /** @test */
    public function category_level_next_level()
    {
        $this->assertEquals(CategoryLevel::SUB, CategoryLevel::MAIN->nextLevel());
        $this->assertNull(CategoryLevel::CHILD->nextLevel());
    }

    // ============================================
    // Accounting Domain Enums
    // ============================================

    /** @test */
    public function transaction_type_enum_exists()
    {
        $this->assertTrue(enum_exists(TransactionType::class));
    }

    /** @test */
    public function transaction_type_has_label()
    {
        $this->assertEquals('إيداع', TransactionType::CREDIT->label());
        $this->assertEquals('خصم', TransactionType::DEBIT->label());
    }

    /** @test */
    public function transaction_type_has_sign()
    {
        $this->assertEquals(1, TransactionType::CREDIT->sign());
        $this->assertEquals(-1, TransactionType::DEBIT->sign());
    }

    /** @test */
    public function transaction_type_increases_balance()
    {
        $this->assertTrue(TransactionType::CREDIT->increasesBalance());
        $this->assertFalse(TransactionType::DEBIT->increasesBalance());
    }

    /** @test */
    public function withdrawal_status_enum_exists()
    {
        $this->assertTrue(enum_exists(WithdrawalStatus::class));
    }

    /** @test */
    public function withdrawal_status_can_be_cancelled()
    {
        $this->assertTrue(WithdrawalStatus::PENDING->canBeCancelled());
        $this->assertFalse(WithdrawalStatus::COMPLETED->canBeCancelled());
    }

    /** @test */
    public function withdrawal_status_is_final()
    {
        $this->assertTrue(WithdrawalStatus::COMPLETED->isFinal());
        $this->assertFalse(WithdrawalStatus::PENDING->isFinal());
    }

    // ============================================
    // Platform Domain Enums
    // ============================================

    /** @test */
    public function language_enum_exists()
    {
        $this->assertTrue(enum_exists(Language::class));
    }

    /** @test */
    public function language_has_native_name()
    {
        $this->assertEquals('العربية', Language::ARABIC->nativeName());
        $this->assertEquals('English', Language::ENGLISH->nativeName());
    }

    /** @test */
    public function language_has_direction()
    {
        $this->assertEquals('rtl', Language::ARABIC->direction());
        $this->assertEquals('ltr', Language::ENGLISH->direction());
    }

    /** @test */
    public function language_is_rtl()
    {
        $this->assertTrue(Language::ARABIC->isRtl());
        $this->assertFalse(Language::ENGLISH->isRtl());
    }

    /** @test */
    public function language_default()
    {
        $this->assertEquals(Language::ARABIC, Language::default());
    }

    /** @test */
    public function currency_enum_exists()
    {
        $this->assertTrue(enum_exists(Currency::class));
    }

    /** @test */
    public function currency_has_name()
    {
        $this->assertEquals('ريال سعودي', Currency::SAR->name());
        $this->assertEquals('دولار أمريكي', Currency::USD->name());
    }

    /** @test */
    public function currency_has_symbol()
    {
        $this->assertEquals('ر.س', Currency::SAR->symbol());
        $this->assertEquals('$', Currency::USD->symbol());
    }

    /** @test */
    public function currency_has_decimals()
    {
        $this->assertEquals(2, Currency::SAR->decimals());
        $this->assertEquals(3, Currency::KWD->decimals());
    }

    /** @test */
    public function currency_format()
    {
        $this->assertEquals('100.00 ر.س', Currency::SAR->format(100));
        $this->assertEquals('50.000 د.ك', Currency::KWD->format(50));
    }

    /** @test */
    public function currency_default()
    {
        $this->assertEquals(Currency::SAR, Currency::default());
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_enums_have_values_method()
    {
        $enums = [
            PurchaseStatus::class,
            PaymentStatus::class,
            PaymentMethod::class,
            ShipmentStatus::class,
            ShippingType::class,
            MerchantStatus::class,
            StockStatus::class,
            UserRole::class,
            VerificationStatus::class,
            ReviewStatus::class,
            CategoryLevel::class,
            TransactionType::class,
            WithdrawalStatus::class,
            Language::class,
            Currency::class,
        ];

        foreach ($enums as $enum) {
            $this->assertTrue(
                method_exists($enum, 'values'),
                "{$enum} should have values method"
            );
            $this->assertIsArray($enum::values());
        }
    }

    /** @test */
    public function all_enums_have_label_method()
    {
        $enums = [
            PurchaseStatus::PENDING,
            PaymentStatus::PENDING,
            PaymentMethod::CASH_ON_DELIVERY,
            ShipmentStatus::PENDING,
            ShippingType::STANDARD,
            MerchantStatus::ACTIVE,
            StockStatus::IN_STOCK,
            UserRole::CUSTOMER,
            VerificationStatus::VERIFIED,
            ReviewStatus::APPROVED,
            CategoryLevel::MAIN,
            TransactionType::CREDIT,
            WithdrawalStatus::PENDING,
            Language::ARABIC,
            Currency::SAR,
        ];

        foreach ($enums as $case) {
            $this->assertTrue(
                method_exists($case, 'label') || method_exists($case, 'nativeName') || method_exists($case, 'name'),
                get_class($case) . " should have label/name method"
            );
        }
    }

    /** @test */
    public function enums_are_backed_enums()
    {
        $this->assertEquals('pending', PurchaseStatus::PENDING->value);
        $this->assertEquals('ar', Language::ARABIC->value);
        $this->assertEquals(1, MerchantStatus::ACTIVE->value);
    }
}
