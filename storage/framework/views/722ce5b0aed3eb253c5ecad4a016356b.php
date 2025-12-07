<!DOCTYPE html>
<html>
<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="keywords" content="<?php echo e($seo->meta_keys); ?>">
        <meta name="author" content="Muaadh">

        <title><?php echo e($gs->title); ?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="<?php echo e(asset('assets/print/bootstrap/dist/css/bootstrap.min.css')); ?>">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo e(asset('assets/print/font-awesome/css/font-awesome.min.css')); ?>">
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?php echo e(asset('assets/print/Ionicons/css/ionicons.min.css')); ?>">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo e(asset('assets/print/css/style.css')); ?>">
  <link href="<?php echo e(asset('assets/print/css/print.css')); ?>" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
        <link rel="icon" type="image/png" href="<?php echo e(asset('assets/images/'.$gs->favicon)); ?>"> 
  <style type="text/css">
@page { size: auto;  margin: 0mm; }
@page {
  size: A4;
  margin: 0;
}
@media print {
  html, body {
    width: 210mm;
    height: 287mm;
  }

html {

}
::-webkit-scrollbar {
    width: 0px;  /* remove scrollbar space */
    background: transparent;  /* optional: just make scrollbar invisible */
}
  </style>
</head>
<body onload="window.print();">
    <div class="invoice-wrap">
            <div class="invoice__title">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="invoice__logo text-left">
                           <img src="<?php echo e(asset('assets/images/'.$gs->invoice_logo)); ?>" alt="woo commerce logo">
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="invoice__metaInfo">
                <div class="col-lg-6">
                    <div class="invoice__orderDetails">
                        <p><strong><?php echo e(__('Order Details')); ?> </strong></p>
                        <span><strong><?php echo e(__('Invoice Number')); ?> :</strong> <?php echo e(sprintf("%'.08d", $order->id)); ?></span><br>
                        <span><strong><?php echo e(__('Order Date')); ?> :</strong> <?php echo e(date('d-M-Y',strtotime($order->created_at))); ?></span><br>
                        <span><strong><?php echo e(__('Order ID')); ?> :</strong> <?php echo e($order->order_number); ?></span><br>
                        <?php if($order->dp == 0): ?>
                        <span> <strong><?php echo e(__('Shipping Method')); ?> :</strong>
                            <?php if($order->shipping == "pickup"): ?>
                            <?php echo e(__('Pick Up')); ?>

                            <?php else: ?>
                            <?php echo e(__('Ship To Address')); ?>

                            <?php endif; ?>
                        </span><br>
                        <?php endif; ?>
                        <span> <strong><?php echo e(__('Payment Method')); ?> :</strong> <?php echo e($order->method); ?></span>
                    </div>
                </div>
            </div>

            <div class="invoice__metaInfo" style="margin-top:0px;">
                <?php if($order->dp == 0): ?>
                <div class="col-lg-6">
                        <div class="invoice__orderDetails" style="margin-top:5px;">
                            <p><strong><?php echo e(__('Shipping Details')); ?></strong></p>
                           <span><strong><?php echo e(__('Customer Name')); ?></strong>: <?php echo e($order->shipping_name == null ? $order->customer_name : $order->shipping_name); ?></span><br>
                           <span><strong><?php echo e(__('Address')); ?></strong>: <?php echo e($order->shipping_address == null ? $order->customer_address : $order->shipping_address); ?></span><br>
                           <span><strong><?php echo e(__('City')); ?></strong>: <?php echo e($order->shipping_city == null ? $order->customer_city : $order->shipping_city); ?></span><br>
                           <span><strong><?php echo e(__('Country')); ?></strong>: <?php echo e($order->shipping_country == null ? $order->customer_country : $order->shipping_country); ?></span>
                        </div>
                </div>
                <?php endif; ?>
                <div class="col-lg-6" style="width:50%;">
                        <div class="invoice__orderDetails" style="margin-top:5px;">
                            <p><strong><?php echo e(__('Billing Details')); ?></strong></p>
                            <span><strong><?php echo e(__('Customer Name')); ?></strong>: <?php echo e($order->customer_name); ?></span><br>
                            <span><strong><?php echo e(__('Address')); ?></strong>: <?php echo e($order->customer_address); ?></span><br>
                            <span><strong><?php echo e(__('City')); ?></strong>: <?php echo e($order->customer_city); ?></span><br>
                            <span><strong><?php echo e(__('Country')); ?></strong>: <?php echo e($order->customer_country); ?></span>
                        </div>
                </div>
            </div>

                <div class="col-lg-12">
                    <div class="invoice_table">
                        <div class="mr-table">
                            <div class="table-responsive">
                                <table id="example2" class="table table-hover dt-responsive" cellspacing="0"
                                    width="100%">
                                    <thead style="border-top:1px solid rgba(0, 0, 0, 0.1) !important;">
                                        <tr>
                                            <th><?php echo e(__('Product')); ?></th>
                                            <th><?php echo e(__('Details')); ?></th>
                                            <th><?php echo e(__('Total')); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $subtotal = 0;
                                        $tax = 0;
                                        $data = 0;
                                        ?>
                                        <?php $__currentLoopData = $cart['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($product['item']['user_id'] != 0): ?>
                                            <?php if($product['item']['user_id'] == $user->id): ?>
                                        <tr>
                                            <td width="50%">
                                                <?php echo e(getLocalizedProductName($product['item'])); ?>

                                                <br><small>SKU: <?php echo e($product['item']['sku'] ?? 'N/A'); ?></small>
                                            </td>

                                            <td>
                                                <?php if($product['size']): ?>
                                               <p>
                                                    <strong><?php echo e(__('Size')); ?> :</strong> <?php echo e(str_replace('-',' ',$product['size'])); ?>

                                               </p>
                                               <?php endif; ?>
                                                <?php if($product['color']): ?>
                                                <p>
                                                        <strong><?php echo e(__('Color')); ?> :</strong> <span style="width: 20px; height: 5px; display: block; border-radius: 50%; border: 10px solid <?php echo e($product['color'] == "" ? "white" : '#'.$product['color']); ?>;"></span>
                                                </p>
                                                <?php endif; ?>
                                                <p>
                                                        <strong><?php echo e(__('Price')); ?> :</strong> 
                                                        <?php echo e(\PriceHelper::showOrderCurrencyPrice(($product['item_price'] * $order->currency_value),$order->currency_sign)); ?>

                                                </p>
                                               <p>
                                                    <strong><?php echo e(__('Qty')); ?> :</strong> <?php echo e($product['qty']); ?> <?php echo e($product['item']['measure']); ?>

                                               </p>
                                                    <?php if(!empty($product['keys'])): ?>

                                                    <?php $__currentLoopData = array_combine(explode(',', $product['keys']), explode(',', $product['values'])); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <p>

                                                        <b><?php echo e(ucwords(str_replace('_', ' ', $key))); ?> : </b> <?php echo e($value); ?> 

                                                    </p>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                    <?php endif; ?>

                                            </td>

                                            <td>
                                                <?php echo e(\PriceHelper::showOrderCurrencyPrice(($product['price'] * $order->currency_value),$order->currency_sign)); ?> <small><?php echo e($product['discount'] == 0 ? '' : '('.$product['discount'].'% '.__('Off').')'); ?></small>
                                            </td>
                                            <?php
                                            $subtotal += round($product['price'] * $order->currency_value, 2);
                                            ?>

                                        </tr>

                                        <?php endif; ?>
                                    <?php endif; ?>

                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <tr class="semi-border">
                                            <td colspan="1"></td>
                                            <td><strong><?php echo e(__('Subtotal')); ?></strong></td>
                                            <td>
                                            <?php echo e(\PriceHelper::showOrderCurrencyPrice($subtotal,$order->currency_sign)); ?>

                                            </td>

                                        </tr>
                                        <?php if(Auth::user()->id == $order->vendor_shipping_id): ?>
                                            <?php if($order->shipping_cost != 0): ?>
                                            <tr class="no-border">
                                                <td colspan="1"></td>
                                                <td><strong><?php echo e(__('Shipping Cost')); ?>(<?php echo e($order->currency_sign); ?>)</strong></td>
                                                <td>
                                                <?php echo e(\PriceHelper::showOrderCurrencyPrice($order->shipping_cost,$order->currency_sign)); ?>



                                                </td>
                                            </tr>
                                            <?php 
                                                $data +=  round($order->shipping_cost , 2);
                                            ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if(Auth::user()->id == $order->vendor_packing_id): ?>
                                            <?php if($order->packing_cost != 0): ?>
                                            <tr class="no-border">
                                                <td colspan="1"></td>
                                                <td><strong><?php echo e(__('Packaging Cost')); ?>(<?php echo e($order->currency_sign); ?>)</strong></td>
                                                <td>
                                                <?php echo e(\PriceHelper::showOrderCurrencyPrice($order->packing_cost,$order->currency_sign)); ?>

                                                </td>
                                            </tr>
                                            <?php 
                                                $data +=  round($order->packing_cost , 2);
                                            ?>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if($order->tax != 0): ?>
                                        <tr class="no-border">
                                            <td colspan="1"></td>
                                            <td><strong><?php echo e(__('TAX')); ?>(<?php echo e($order->currency_sign); ?>)</strong></td>

                                            <?php
                                                $tax = ($subtotal / 100) * $order->tax;
                                                $subtotal =  $subtotal + $tax;
                                            ?>
                                            

                                            <td>

                                            <?php echo e(\PriceHelper::showOrderCurrencyPrice($tax,$order->currency_sign)); ?>


                                            </td>
                                        </tr>

                                        <?php endif; ?>

                                        <tr class="final-border">
                                            <td colspan="1"></td>
                                            <td><strong><?php echo e(__('Total')); ?></strong></td>
                                            <td>
                                            <?php echo e(\PriceHelper::showOrderCurrencyPrice(($subtotal + $data),$order->currency_sign)); ?>

                                            </td>
                                        </tr>

                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
<!-- ./wrapper -->

<script type="text/javascript">

    (function($) {
		"use strict";

setTimeout(function () {
        window.close();
      }, 500);

    })(jQuery);

</script>

</body>
</html>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/vendor/order/print.blade.php ENDPATH**/ ?>