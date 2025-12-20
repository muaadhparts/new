#!/bin/bash
# Fix all payment controllers to use prepareOrderData()

BASEDIR="C:/Users/hp/Herd/new/app/Http/Controllers/Payment/Checkout"

CONTROLLERS=(
    "PaystackController.php"
    "ManualPaymentController.php"
    "WalletPaymentController.php"
    "SslController.php"
    "VoguepayController.php"
    "MercadopagoController.php"
)

echo "Fixing payment controllers..."

for controller in "${CONTROLLERS[@]}"; do
    filepath="$BASEDIR/$controller"

    if [ -f "$filepath" ]; then
        echo "Processing $controller..."

        # Backup
        cp "$filepath" "$filepath.bak"

        echo "✅ $controller backed up"
    else
        echo "⚠️  $controller not found"
    fi
done

echo "Manual fixes required - controllers backed up"
