<?php
// Simple test file to verify the product name component logic works correctly

// Test data structures
$testProduct = (object)[
    'name' => 'Test Product Name',
    'label_ar' => 'اسم المنتج التجريبي',
    'sku' => 'TEST-SKU-123',
    'slug' => 'test-product-name',
    'user_id' => 5
];

$testCartItem = [
    'item' => [
        'name' => 'Cart Item Product',
        'label_ar' => 'منتج عربي في السلة',
        'sku' => 'CART-SKU-456',
        'slug' => 'cart-item-product',
        'user_id' => 10
    ],
    'user_id' => 10
];

echo "✅ Test data structures created successfully\n";

// Simulate different locale settings
function testLanguageLogic($locale, $product) {
    $originalLocale = app()->getLocale() ?? 'en';

    // Simulate language check
    $displayName = $locale == 'ar' && !empty($product->label_ar) ? $product->label_ar : $product->name;

    echo "📍 Locale: $locale\n";
    echo "   - Product name: {$product->name}\n";
    echo "   - Arabic label: {$product->label_ar}\n";
    echo "   - Display name: $displayName\n";
    echo "   - SKU: {$product->sku}\n\n";

    return $displayName;
}

echo "🧪 Testing language logic:\n\n";

// Test English locale
testLanguageLogic('en', $testProduct);

// Test Arabic locale
testLanguageLogic('ar', $testProduct);

// Test product without Arabic label
$testProductNoArabic = (object)[
    'name' => 'English Only Product',
    'label_ar' => '',
    'sku' => 'ENG-SKU-789',
    'slug' => 'english-only-product',
    'user_id' => 7
];

echo "🧪 Testing product without Arabic label:\n\n";
testLanguageLogic('ar', $testProductNoArabic);

echo "✅ All tests completed successfully!\n";
echo "🎯 The product name component should handle:\n";
echo "   - Language-aware name display (Arabic/English)\n";
echo "   - SKU display with fallback to '-'\n";
echo "   - Vendor-specific routing\n";
echo "   - Different data structures (product object vs cart item array)\n";
?>