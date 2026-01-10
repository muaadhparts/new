<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Checkout Base Controller - Stub
 *
 * This is a minimal base controller kept for backward compatibility
 * with API payment controllers that still extend it.
 *
 * New checkout functionality is in:
 * - App\Http\Controllers\Merchant\CheckoutMerchantController
 * - App\Http\Controllers\Merchant\Payment\*
 *
 * @deprecated Use the new Merchant checkout controllers instead
 */
class CheckoutBaseControlller extends Controller
{
    public function __construct()
    {
        // Minimal constructor - no special initialization needed
    }
}
