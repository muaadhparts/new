<?php

namespace Tests\Regression\Merchant;

use Tests\TestCase;
use App\Models\MerchantItem;
use App\Models\MerchantBranch;
use App\Models\MerchantPhoto;
use App\Models\MerchantCommission;
use App\Models\MerchantPayment;
use App\Models\MerchantSetting;
use App\Models\MerchantCredential;
use App\Models\MerchantTaxSetting;
use App\Models\MerchantStockUpdate;
use App\Models\ApiCredential;
use App\Domain\Merchant\Models\MerchantItem as DomainMerchantItem;
use App\Domain\Merchant\Models\MerchantBranch as DomainMerchantBranch;
use App\Domain\Merchant\Models\MerchantPhoto as DomainMerchantPhoto;
use App\Domain\Merchant\Models\MerchantCommission as DomainMerchantCommission;
use App\Domain\Merchant\Models\MerchantPayment as DomainMerchantPayment;
use App\Domain\Merchant\Models\MerchantSetting as DomainMerchantSetting;
use App\Domain\Merchant\Models\MerchantCredential as DomainMerchantCredential;
use App\Domain\Merchant\Models\MerchantTaxSetting as DomainMerchantTaxSetting;
use App\Domain\Merchant\Models\MerchantStockUpdate as DomainMerchantStockUpdate;
use App\Domain\Merchant\Models\ApiCredential as DomainApiCredential;

/**
 * Regression tests for Merchant Domain models
 *
 * These tests verify that the refactored Domain models maintain
 * backward compatibility with the original App\Models classes.
 */
class MerchantModelsTest extends TestCase
{
    // ==========================================
    // MerchantItem Tests
    // ==========================================

    public function test_merchant_item_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantItem::class, DomainMerchantItem::class),
            'MerchantItem should extend Domain MerchantItem'
        );
    }

    public function test_merchant_item_table_name(): void
    {
        $model = new MerchantItem();
        $this->assertEquals('merchant_items', $model->getTable());
    }

    public function test_merchant_item_has_required_relations(): void
    {
        $model = new MerchantItem();

        $this->assertTrue(method_exists($model, 'catalogItem'), 'Should have catalogItem relation');
        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
        $this->assertTrue(method_exists($model, 'photos'), 'Should have photos relation');
        $this->assertTrue(method_exists($model, 'branch'), 'Should have branch relation');
        $this->assertTrue(method_exists($model, 'qualityBrand'), 'Should have qualityBrand relation');
    }

    public function test_merchant_item_has_price_calculation_method(): void
    {
        $model = new MerchantItem();
        $this->assertTrue(method_exists($model, 'merchantSizePrice'), 'Should have merchantSizePrice method');
        $this->assertTrue(method_exists($model, 'showPrice'), 'Should have showPrice method');
        $this->assertTrue(method_exists($model, 'offPercentage'), 'Should have offPercentage method');
    }

    public function test_merchant_item_scopes_exist(): void
    {
        $model = new MerchantItem();

        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
        $this->assertTrue(method_exists($model, 'scopeInStock'), 'Should have inStock scope');
        $this->assertTrue(method_exists($model, 'scopeAffiliate'), 'Should have affiliate scope');
        $this->assertTrue(method_exists($model, 'scopeNormal'), 'Should have normal scope');
    }

    // ==========================================
    // MerchantBranch Tests
    // ==========================================

    public function test_merchant_branch_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantBranch::class, DomainMerchantBranch::class),
            'MerchantBranch should extend Domain MerchantBranch'
        );
    }

    public function test_merchant_branch_table_name(): void
    {
        $model = new MerchantBranch();
        $this->assertEquals('merchant_branches', $model->getTable());
    }

    public function test_merchant_branch_has_required_relations(): void
    {
        $model = new MerchantBranch();

        $this->assertTrue(method_exists($model, 'merchant'), 'Should have merchant relation');
        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
        $this->assertTrue(method_exists($model, 'merchantItems'), 'Should have merchantItems relation');
        $this->assertTrue(method_exists($model, 'city'), 'Should have city relation');
        $this->assertTrue(method_exists($model, 'country'), 'Should have country relation');
    }

    public function test_merchant_branch_has_geo_scopes(): void
    {
        $model = new MerchantBranch();

        $this->assertTrue(method_exists($model, 'scopeWithinRadius'), 'Should have withinRadius scope');
        $this->assertTrue(method_exists($model, 'scopeActive'), 'Should have active scope');
        $this->assertTrue(method_exists($model, 'scopeByMerchant'), 'Should have byMerchant scope');
    }

    // ==========================================
    // MerchantPhoto Tests
    // ==========================================

    public function test_merchant_photo_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantPhoto::class, DomainMerchantPhoto::class),
            'MerchantPhoto should extend Domain MerchantPhoto'
        );
    }

    public function test_merchant_photo_table_name(): void
    {
        $model = new MerchantPhoto();
        $this->assertEquals('merchant_photos', $model->getTable());
    }

    public function test_merchant_photo_has_required_relations(): void
    {
        $model = new MerchantPhoto();

        $this->assertTrue(method_exists($model, 'merchantItem'), 'Should have merchantItem relation');
    }

    public function test_merchant_photo_has_url_accessor(): void
    {
        $model = new MerchantPhoto();
        $this->assertTrue(method_exists($model, 'getPhotoUrlAttribute'), 'Should have photoUrl accessor');
    }

    // ==========================================
    // MerchantCommission Tests
    // ==========================================

    public function test_merchant_commission_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantCommission::class, DomainMerchantCommission::class),
            'MerchantCommission should extend Domain MerchantCommission'
        );
    }

    public function test_merchant_commission_table_name(): void
    {
        $model = new MerchantCommission();
        $this->assertEquals('merchant_commissions', $model->getTable());
    }

    public function test_merchant_commission_has_required_relations(): void
    {
        $model = new MerchantCommission();

        $this->assertTrue(method_exists($model, 'merchant'), 'Should have merchant relation');
        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
    }

    public function test_merchant_commission_has_calculation_methods(): void
    {
        $model = new MerchantCommission();

        $this->assertTrue(method_exists($model, 'calculateCommission'), 'Should have calculateCommission method');
        $this->assertTrue(method_exists($model, 'getPriceWithCommission'), 'Should have getPriceWithCommission method');
    }

    // ==========================================
    // MerchantPayment Tests
    // ==========================================

    public function test_merchant_payment_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantPayment::class, DomainMerchantPayment::class),
            'MerchantPayment should extend Domain MerchantPayment'
        );
    }

    public function test_merchant_payment_table_name(): void
    {
        $model = new MerchantPayment();
        $this->assertEquals('merchant_payments', $model->getTable());
    }

    public function test_merchant_payment_has_required_relations(): void
    {
        $model = new MerchantPayment();

        $this->assertTrue(method_exists($model, 'monetaryUnit'), 'Should have monetaryUnit relation');
    }

    public function test_merchant_payment_has_for_merchant_scope(): void
    {
        $model = new MerchantPayment();

        $this->assertTrue(method_exists($model, 'scopeForMerchant'), 'Should have forMerchant scope');
        $this->assertTrue(method_exists($model, 'scopePlatformOnly'), 'Should have platformOnly scope');
    }

    public function test_merchant_payment_has_ownership_methods(): void
    {
        $model = new MerchantPayment();

        $this->assertTrue(method_exists($model, 'isPlatformOwned'), 'Should have isPlatformOwned method');
        $this->assertTrue(method_exists($model, 'isMerchantOwned'), 'Should have isMerchantOwned method');
        $this->assertTrue(method_exists($model, 'isEnabledForMerchant'), 'Should have isEnabledForMerchant method');
    }

    // ==========================================
    // MerchantSetting Tests
    // ==========================================

    public function test_merchant_setting_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantSetting::class, DomainMerchantSetting::class),
            'MerchantSetting should extend Domain MerchantSetting'
        );
    }

    public function test_merchant_setting_table_name(): void
    {
        $model = new MerchantSetting();
        $this->assertEquals('merchant_settings', $model->getTable());
    }

    public function test_merchant_setting_has_static_methods(): void
    {
        $this->assertTrue(method_exists(MerchantSetting::class, 'get'), 'Should have static get method');
        $this->assertTrue(method_exists(MerchantSetting::class, 'set'), 'Should have static set method');
        $this->assertTrue(method_exists(MerchantSetting::class, 'getGroup'), 'Should have static getGroup method');
    }

    // ==========================================
    // MerchantCredential Tests
    // ==========================================

    public function test_merchant_credential_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantCredential::class, DomainMerchantCredential::class),
            'MerchantCredential should extend Domain MerchantCredential'
        );
    }

    public function test_merchant_credential_table_name(): void
    {
        $model = new MerchantCredential();
        $this->assertEquals('merchant_credentials', $model->getTable());
    }

    public function test_merchant_credential_has_encryption_accessors(): void
    {
        $model = new MerchantCredential();

        $this->assertTrue(method_exists($model, 'getDecryptedValueAttribute'), 'Should have decryptedValue accessor');
        $this->assertTrue(method_exists($model, 'setValueAttribute'), 'Should have value mutator');
    }

    public function test_merchant_credential_has_static_methods(): void
    {
        $this->assertTrue(method_exists(MerchantCredential::class, 'getCredential'), 'Should have getCredential method');
        $this->assertTrue(method_exists(MerchantCredential::class, 'setCredential'), 'Should have setCredential method');
    }

    // ==========================================
    // MerchantTaxSetting Tests
    // ==========================================

    public function test_merchant_tax_setting_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantTaxSetting::class, DomainMerchantTaxSetting::class),
            'MerchantTaxSetting should extend Domain MerchantTaxSetting'
        );
    }

    public function test_merchant_tax_setting_table_name(): void
    {
        $model = new MerchantTaxSetting();
        $this->assertEquals('merchant_tax_settings', $model->getTable());
    }

    public function test_merchant_tax_setting_has_calculation_methods(): void
    {
        $model = new MerchantTaxSetting();

        $this->assertTrue(method_exists($model, 'calculateTax'), 'Should have calculateTax method');
    }

    // ==========================================
    // MerchantStockUpdate Tests
    // ==========================================

    public function test_merchant_stock_update_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(MerchantStockUpdate::class, DomainMerchantStockUpdate::class),
            'MerchantStockUpdate should extend Domain MerchantStockUpdate'
        );
    }

    public function test_merchant_stock_update_table_name(): void
    {
        $model = new MerchantStockUpdate();
        $this->assertEquals('merchant_stock_updates', $model->getTable());
    }

    public function test_merchant_stock_update_has_required_relations(): void
    {
        $model = new MerchantStockUpdate();

        $this->assertTrue(method_exists($model, 'user'), 'Should have user relation');
    }

    public function test_merchant_stock_update_has_status_methods(): void
    {
        $model = new MerchantStockUpdate();

        $this->assertTrue(method_exists($model, 'isPending'), 'Should have isPending method');
        $this->assertTrue(method_exists($model, 'isProcessing'), 'Should have isProcessing method');
        $this->assertTrue(method_exists($model, 'isCompleted'), 'Should have isCompleted method');
        $this->assertTrue(method_exists($model, 'isFailed'), 'Should have isFailed method');
    }

    // ==========================================
    // ApiCredential Tests
    // ==========================================

    public function test_api_credential_extends_domain_model(): void
    {
        $this->assertTrue(
            is_subclass_of(ApiCredential::class, DomainApiCredential::class),
            'ApiCredential should extend Domain ApiCredential'
        );
    }

    public function test_api_credential_table_name(): void
    {
        $model = new ApiCredential();
        $this->assertEquals('api_credentials', $model->getTable());
    }

    public function test_api_credential_has_encryption_accessors(): void
    {
        $model = new ApiCredential();

        $this->assertTrue(method_exists($model, 'getDecryptedValueAttribute'), 'Should have decryptedValue accessor');
        $this->assertTrue(method_exists($model, 'setValueAttribute'), 'Should have value mutator');
    }

    public function test_api_credential_has_static_methods(): void
    {
        $this->assertTrue(method_exists(ApiCredential::class, 'getCredential'), 'Should have getCredential method');
        $this->assertTrue(method_exists(ApiCredential::class, 'setCredential'), 'Should have setCredential method');
    }

    // ==========================================
    // Cross-Domain Relation Tests
    // ==========================================

    public function test_merchant_item_catalog_item_relation_type(): void
    {
        $model = new MerchantItem();
        $relation = $model->catalogItem();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'catalogItem should be a BelongsTo relation'
        );
    }

    public function test_merchant_branch_merchant_items_relation_type(): void
    {
        $model = new MerchantBranch();
        $relation = $model->merchantItems();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation,
            'merchantItems should be a HasMany relation'
        );
    }

    public function test_merchant_item_photos_relation_type(): void
    {
        $model = new MerchantItem();
        $relation = $model->photos();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation,
            'photos should be a HasMany relation'
        );
    }

    // ==========================================
    // Query Builder Tests
    // ==========================================

    public function test_merchant_item_scopes_work(): void
    {
        $activeItems = MerchantItem::active()->toSql();
        $this->assertStringContainsString('status', $activeItems);

        $inStockItems = MerchantItem::inStock()->toSql();
        $this->assertStringContainsString('stock', $inStockItems);
    }

    public function test_merchant_branch_scopes_work(): void
    {
        $activeBranches = MerchantBranch::active()->toSql();
        $this->assertStringContainsString('status', $activeBranches);
    }

    public function test_merchant_payment_for_merchant_scope(): void
    {
        $merchantId = 1;
        $sql = MerchantPayment::forMerchant($merchantId)->toSql();

        // Should include logic for platform OR merchant-owned
        $this->assertNotEmpty($sql);
    }
}
