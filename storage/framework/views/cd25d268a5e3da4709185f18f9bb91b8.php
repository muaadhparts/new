

<?php
    $currentStep = $step ?? 1;
    $isDigital = $digital ?? false;
?>

<!-- Price Details -->
<div class="summary-inner-box">
    <h6 class="summary-title"><?php echo app('translator')->get('Price Details'); ?></h6>
    <div class="details-wrapper">

        
        <div class="price-details">
            <span><?php echo app('translator')->get('Total MRP'); ?></span>
            <?php if($currentStep == 1): ?>
                <span class="right-side"><?php echo e(App\Models\Product::convertPrice($productsTotal)); ?></span>
            <?php else: ?>
                <span class="right-side"><?php echo e(App\Models\Product::convertPrice($step1->products_total ?? 0)); ?></span>
            <?php endif; ?>
        </div>

        
        <?php if(Session::has('coupon')): ?>
            <div class="price-details">
                <span>
                    <?php echo app('translator')->get('Discount'); ?>
                    <?php if(Session::get('coupon_percentage') > 0): ?>
                        <span class="dpercent">(<?php echo e(Session::get('coupon_percentage')); ?>%)</span>
                    <?php endif; ?>
                </span>
                <?php if($gs->currency_format == 0): ?>
                    <span class="right-side"><?php echo e($curr->sign); ?><?php echo e(Session::get('coupon')); ?></span>
                <?php else: ?>
                    <span class="right-side"><?php echo e(Session::get('coupon')); ?><?php echo e($curr->sign); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        

        
        <?php if($currentStep == 1): ?>
            <div class="price-details tax-display-wrapper d-none" id="tax-display">
                <span>
                    <?php echo app('translator')->get('Tax'); ?>
                    <span class="tax-rate-text"></span>
                </span>
                <span class="right-side tax-amount-value"><?php echo e(App\Models\Product::convertPrice(0)); ?></span>
            </div>

            <div class="price-details tax-location-wrapper d-none" id="tax-location-display">
                <small class="text-muted tax-location-text"></small>
            </div>
        <?php endif; ?>

        
        <?php if($currentStep >= 2 && isset($step1) && isset($step1->tax_rate) && $step1->tax_rate > 0): ?>
            <div class="price-details">
                <span><?php echo app('translator')->get('Tax'); ?> (<?php echo e($step1->tax_rate); ?>%)</span>
                <span class="right-side"><?php echo e(App\Models\Product::convertPrice($step1->tax_amount)); ?></span>
            </div>

            <?php if(isset($step1->tax_location) && $step1->tax_location): ?>
                <div class="price-details">
                    <small class="text-muted"><?php echo e($step1->tax_location); ?></small>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        

        
        <?php if($currentStep == 2 && !$isDigital): ?>
            <div class="price-details">
                <span><?php echo app('translator')->get('Shipping Cost'); ?></span>
                <span class="right-side shipping_cost_view"><?php echo e(App\Models\Product::convertPrice(0)); ?></span>
            </div>

            <div class="price-details">
                <span><?php echo app('translator')->get('Packaging Cost'); ?></span>
                <span class="right-side packing_cost_view"><?php echo e(App\Models\Product::convertPrice(0)); ?></span>
            </div>
        <?php endif; ?>

        
        <?php if($currentStep == 3 && !$isDigital && isset($step2)): ?>
            <div class="price-details">
                <span><?php echo app('translator')->get('Shipping Cost'); ?></span>
                <span class="right-side"><?php echo e(App\Models\Product::convertPrice($step2->shipping_cost ?? 0)); ?></span>
            </div>

            <?php if(isset($step2->shipping_company) && $step2->shipping_company): ?>
                <div class="price-details">
                    <small class="text-muted"><?php echo e($step2->shipping_company); ?></small>
                </div>
            <?php endif; ?>

            <div class="price-details">
                <span><?php echo app('translator')->get('Packaging Cost'); ?></span>
                <span class="right-side"><?php echo e(App\Models\Product::convertPrice($step2->packing_cost ?? 0)); ?></span>
            </div>

            <?php if(isset($step2->packing_company) && $step2->packing_company): ?>
                <div class="price-details">
                    <small class="text-muted"><?php echo e($step2->packing_company); ?></small>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    
    <hr>
    <div class="final-price">
        <span style="font-size: 16px; font-weight: 700;">
            <?php if($currentStep == 1): ?>
                <?php echo app('translator')->get('Total Amount'); ?>
            <?php else: ?>
                <?php echo app('translator')->get('Final Price'); ?>
            <?php endif; ?>
        </span>

        
        <?php if($currentStep == 1 || $currentStep == 2): ?>
            <?php
                // Initial display value
                $initialTotal = $productsTotal ?? ($step1->total_with_tax ?? 0);
            ?>

            <?php if($gs->currency_format == 0): ?>
                <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: #EE1243;">
                    <?php echo e($curr->sign); ?><?php echo e(number_format($initialTotal, 2)); ?>

                </span>
            <?php else: ?>
                <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: #EE1243;">
                    <?php echo e(number_format($initialTotal, 2)); ?><?php echo e($curr->sign); ?>

                </span>
            <?php endif; ?>
        <?php endif; ?>

        
        <?php if($currentStep == 3 && isset($step2)): ?>
            <?php
                // Use final_total if available, fallback to total for backward compatibility
                $finalTotal = $step2->final_total ?? $step2->total ?? 0;
            ?>

            <?php if($gs->currency_format == 0): ?>
                <span class="total-amount" style="font-size: 18px; font-weight: 700; color: #EE1243;">
                    <?php echo e($curr->sign); ?><?php echo e(number_format($finalTotal, 2)); ?>

                </span>
            <?php else: ?>
                <span class="total-amount" style="font-size: 18px; font-weight: 700; color: #EE1243;">
                    <?php echo e(number_format($finalTotal, 2)); ?><?php echo e($curr->sign); ?>

                </span>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    
    <?php if($currentStep == 1): ?>
        <div class="text-muted" style="margin-top: 5px; font-size: 12px; text-align: center;">
            <small>* <?php echo app('translator')->get('Tax and shipping costs will be calculated in next steps'); ?></small>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/checkout-price-summary.blade.php ENDPATH**/ ?>