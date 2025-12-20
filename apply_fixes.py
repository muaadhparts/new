#!/usr/bin/env python3
import re
import os

files_to_fix = [
    'ManualPaymentController.php',
    'WalletPaymentController.php',
    'SslController.php',
    'VoguepayController.php',
    'MercadopagoController.php',
    'AuthorizeController.php',
    'FlutterwaveController.php',
    'RazorpayController.php',
    'PaytmController.php',
    'InstamojoController.php',
]

base_path = r'C:\Users\hp\Herd\new\app\Http\Controllers\Payment\Checkout'

replacement = '''// ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
        $prepared = $this->prepareOrderData($input, $cart);
        $input = $prepared['input'];
        $orderTotal = $prepared['order_total'];'''

for filename in files_to_fix:
    filepath = os.path.join(base_path, filename)

    if not os.path.exists(filepath):
        print(f"⚠️  {filename} not found")
        continue

    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Pattern to match everything from $orderCalculate until unset
    pattern = r'(\s*)\$orderCalculate\s*=\s*(?:\\)?PriceHelper::getOrderTotal.*?(?:unset\(\$input\[\'packeging\'\]\);|unset\(\$input\[\'shipping\'\]\);.*?unset\(\$input\[\'packeging\'\]\);)'

    if re.search(pattern, content, re.DOTALL):
        new_content = re.sub(pattern, '\n' + replacement + '\n', content, flags=re.DOTALL)

        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)

        print(f"OK {filename} fixed")
    else:
        print(f"WARN Pattern not found in {filename}")

print("\nOK All files processed")
