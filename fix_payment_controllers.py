#!/usr/bin/env python3
"""
Fix all Payment Controllers to use prepareOrderData() instead of PriceHelper::getOrderTotal()
"""

import re
import os

controllers = [
    'StripeController.php',
    'PaystackController.php',
    'RazorpayController.php',
    'ManualPaymentController.php',
    'WalletPaymentController.php',
    'AuthorizeController.php',
    'FlutterwaveController.php',
    'InstamojoController.php',
    'MercadopagoController.php',
    'PaytmController.php',
    'SslController.php',
    'VoguepayController.php',
]

base_path = r'C:\Users\hp\Herd\new\app\Http\Controllers\Payment\Checkout'

# Pattern to match the old code that calculates order total
old_pattern = re.compile(
    r'\$orderCalculate\s*=\s*PriceHelper::getOrderTotal.*?'
    r'(?:if\s*\(\$this->gs->multiple_shipping\s*==\s*0\).*?'
    r'unset\(\$input\[.*?\]\);.*?\}|'
    r'\$orderTotal\s*=\s*\$orderCalculate\[.*?\];)',
    re.DOTALL
)

# Replacement code
replacement = '''// ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
            $prepared = $this->prepareOrderData($input, $cart);
            $input = $prepared['input'];
            $orderTotal = $prepared['order_total'];'''

for controller in controllers:
    filepath = os.path.join(base_path, controller)

    if not os.path.exists(filepath):
        print(f"⚠️  File not found: {controller}")
        continue

    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Check if already uses prepareOrderData
        if 'prepareOrderData' in content:
            print(f"✅ {controller} already fixed")
            continue

        # Check if uses PriceHelper::getOrderTotal
        if 'PriceHelper::getOrderTotal' not in content and 'PriceHelper::getOrderTotalAmount' not in content:
            print(f"ℹ️  {controller} doesn't use PriceHelper")
            continue

        # Manual fix needed - just report
        print(f"🔧 {controller} needs manual fix")

    except Exception as e:
        print(f"❌ Error processing {controller}: {e}")

print("\n✅ Script completed. Manual fixes required for all controllers.")
