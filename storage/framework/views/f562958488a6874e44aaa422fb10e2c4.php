<?php $__env->startSection('content'); ?>
<div class="content-area">
    <?php echo $__env->make('alerts.form-success', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if($activation_notify != ""): ?>
    <div class="alert alert-danger validation">
        <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">×</span></button>
        <h3 class="text-center"><?php echo clean($activation_notify, array('Attr.EnableID' => true)); ?></h3>
        
    </div>
    <?php endif; ?>

    <?php if(Session::has('cache')): ?>

    <div class="alert alert-success validation">
        <button type="button" class="close" data-bs-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">×</span></button>
        <h3 class="text-center"><?php echo e(Session::get("cache")); ?></h3>
    </div>

    <?php endif; ?>

    <div class="row row-cards-one">
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg1">
                <div class="left">
                    <h5 class="title"><?php echo e(__('Orders Pending!')); ?> </h5>
                    <span class="number"><?php echo e(count($pending)); ?></span>
                    <a href="<?php echo e(route('admin-orders-all')); ?>?status=pending" class="link"><?php echo e(__('View All')); ?></a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg2">
                <div class="left">
                    <h5 class="title"><?php echo e(__('Orders Procsessing!')); ?></h5>
                    <span class="number"><?php echo e(count($processing)); ?></span>
                    <a href="<?php echo e(route('admin-orders-all')); ?>?status=processing" class="link"><?php echo e(__('View All')); ?></a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-truck-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg3">
                <div class="left">
                    <h5 class="title"><?php echo e(__('Orders Completed!')); ?></h5>
                    <span class="number"><?php echo e(count($completed)); ?></span>
                    <a href="<?php echo e(route('admin-orders-all')); ?>?status=completed" class="link"><?php echo e(__('View All')); ?></a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-check-circled"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg4">
                <div class="left">
                    <h5 class="title"><?php echo e(__('Total Products!')); ?></h5>
                    <span class="number"><?php echo e($products); ?></span>
                    <a href="<?php echo e(route('admin-prod-index')); ?>" class="link"><?php echo e(__('View All')); ?></a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-cart-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg5">
                <div class="left">
                    <h5 class="title"><?php echo e(__('Total Customers!')); ?></h5>
                    <span class="number"><?php echo e($users); ?></span>
                    <a href="<?php echo e(route('admin-user-index')); ?>" class="link"><?php echo e(__('View All')); ?></a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-users-alt-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 col-xl-4">
            <div class="mycard bg6">
                <div class="left">
                    <h5 class="title"><?php echo e(__('Total Posts!')); ?></h5>
                    <span class="number"><?php echo e($blogs); ?></span>
                    <a href="<?php echo e(route('admin-blog-index')); ?>" class="link"><?php echo e(__('View All')); ?></a>
                </div>
                <div class="right d-flex align-self-center">
                    <div class="icon">
                        <i class="icofont-newspaper"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row row-cards-one">
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box1">
                    <p><?php echo e(App\Models\User::where( 'created_at', '>', Carbon\Carbon::now()->subDays(30))->get()->count()); ?></p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title"><?php echo e(__('New Customers')); ?></h6>
                    <p class="text"><?php echo e(__('Last 30 Days')); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box2">
                    <p><?php echo e(App\Models\User::count()); ?></p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title"><?php echo e(__('Total Customers')); ?></h6>
                    <p class="text"><?php echo e(__('All Time')); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box3">
                    <p><?php echo e(App\Models\Order::where('status','=','completed')->where( 'created_at', '>', Carbon\Carbon::now()->subDays(30))->get()->count()); ?></p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title"><?php echo e(__('Total Sales')); ?></h6>
                    <p class="text"><?php echo e(__('Last 30 days')); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card c-info-box-area">
                <div class="c-info-box box4">
                     <p><?php echo e(App\Models\Order::where('status','=','completed')->get()->count()); ?></p>
                </div>
                <div class="c-info-box-content">
                    <h6 class="title"><?php echo e(__('Total Sales')); ?></h6>
                    <p class="text"><?php echo e(__('All Time')); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards-one">

        <div class="col-md-12 col-lg-6 col-sm-12 col-xl-6">
            <div class="card">
                <h5 class="card-header"><?php echo e(__('Recent Order(s)')); ?></h5>
                <div class="card-body">

                <div class="table-responsive  dashboard-home-table">
                                    <table id="poproducts" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>
                                <tr>

                                    <th><?php echo e(__('Order Number')); ?></th>
                                    <th><?php echo e(__('Order Date')); ?></th>
                                </tr>
                                <?php $__currentLoopData = $rorders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($data->order_number); ?></td>
                                    <td><?php echo e(date('Y-m-d',strtotime($data->created_at))); ?></td>
                                    <td>
                                        <div class="action-list"><a href="<?php echo e(route('admin-order-show',$data->id)); ?>"><i
                                                    class="fas fa-eye"></i> <?php echo e(__('Details')); ?></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </thead>
                        </table>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-12 col-lg-6 col-sm-12 col-xl-6">
                <div class="card">
                        <h5 class="card-header"><?php echo e(__('Recent Customer(s)')); ?></h5>
                        <div class="card-body">
        
                             <div class="table-responsive  dashboard-home-table">
                                    <table id="poproducts" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th><?php echo e(__('Customer Email')); ?></th>
                                            <th><?php echo e(__('Joined')); ?></th>
                                        </tr>
                                        <?php $__currentLoopData = $rusers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($data->email); ?></td>
                                            <td><?php echo e($data->created_at); ?></td>
                                            <td>
                                                <div class="action-list"><a href="<?php echo e(route('admin-user-show',$data->id)); ?>"><i
                                                            class="fas fa-eye"></i> <?php echo e(__('Details')); ?></a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </thead>
                                </table>
                            </div>
        
                        </div>
                    </div>
        </div>
    </div>

    <div class="row row-cards-one">

            <div class="col-md-12 col-lg-12 col-sm-12 col-xl-12">
                    <div class="card">
                            <h5 class="card-header"><?php echo e(__('Popular Product(s)')); ?></h5>
                            <div class="card-body">
            
                                <div class="table-responsive  dashboard-home-table">
                                    <table id="poproducts" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                        <thead>
                                            <tr>
                                                <th><?php echo e(__('Featured Image')); ?></th>
                                                <th><?php echo e(__('Name')); ?></th>
                                                <th><?php echo e(__('Category')); ?></th>
                                                <th><?php echo e(__('Type')); ?></th>
                                                <th><?php echo e(__('Price')); ?></th>
                                                <th></th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $poproducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                            <td><img src="<?php echo e(filter_var($data->product->photo ?? '', FILTER_VALIDATE_URL) ? $data->product->photo : ($data->product->photo ?? null ? \Illuminate\Support\Facades\Storage::url($data->product->photo) : asset('assets/images/noimage.png'))); ?>"></td>
                                            <td><?php echo e($data->product ? getLocalizedProductName($data->product, 50) : 'N/A'); ?></td>
                                            <td><?php echo e($data->product && $data->product->category ? getLocalizedCategoryName($data->product->category) : 'N/A'); ?>

                                                    <?php if(isset($data->product->subcategory)): ?>
                                                    <br>
                                                    <?php echo e(getLocalizedCategoryName($data->product->subcategory)); ?>

                                                    <?php endif; ?>
                                                    <?php if(isset($data->product->childcategory)): ?>
                                                    <br>
                                                    <?php echo e(getLocalizedCategoryName($data->product->childcategory)); ?>

                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo e($data->product->type ?? 'N/A'); ?></td>

                                                <td> <?php echo e($data->showPrice()); ?> </td>

                                                <td>
                                                    <div class="action-list"><a href="<?php echo e(route('admin-prod-edit',$data->id)); ?>"><i
                                                                class="fas fa-eye"></i> <?php echo e(__('Details')); ?></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
    
            </div>
    
        </div>

    <div class="row row-cards-one">

            <div class="col-md-12 col-lg-12 col-sm-12 col-xl-12">
                    <div class="card">
                            <h5 class="card-header"><?php echo e(__('Recent Product(s)')); ?></h5>
                            <div class="card-body">
            
                                <div class="table-responsive dashboard-home-table">
                                    <table id="pproducts" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                            <thead>
                                                    <tr>
                                                        <th><?php echo e(__('Featured Image')); ?></th>
                                                        <th><?php echo e(__('Name')); ?></th>
                                                        <th><?php echo e(__('Category')); ?></th>
                                                        <th><?php echo e(__('Type')); ?></th>
                                                        <th><?php echo e(__('Price')); ?></th>
                                                        <th></th>
                                                        
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $pproducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                    <td><img src="<?php echo e(filter_var($data->product->photo ?? '', FILTER_VALIDATE_URL) ? $data->product->photo : ($data->product->photo ?? null ? \Illuminate\Support\Facades\Storage::url($data->product->photo) : asset('assets/images/noimage.png'))); ?>"></td>
                                                    <td><?php echo e($data->product ? getLocalizedProductName($data->product, 50) : 'N/A'); ?></td>
                                                    <td><?php echo e($data->product && $data->product->category ? getLocalizedCategoryName($data->product->category) : 'N/A'); ?>

                                                        <?php if(isset($data->product->subcategory)): ?>
                                                        <br>
                                                        <?php echo e(getLocalizedCategoryName($data->product->subcategory)); ?>

                                                        <?php endif; ?>
                                                        <?php if(isset($data->product->childcategory)): ?>
                                                        <br>
                                                        <?php echo e(getLocalizedCategoryName($data->product->childcategory)); ?>

                                                        <?php endif; ?>
                                                    </td>
                                                        <td><?php echo e($data->product->type ?? 'N/A'); ?></td>
                                                        <td> <?php echo e($data->showPrice()); ?> </td>
                                                        <td>
                                                            <div class="action-list"><a href="<?php echo e(route('admin-prod-edit',$data->product->id ?? $data->id)); ?>"><i
                                                                        class="fas fa-eye"></i> <?php echo e(__('Details')); ?></a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </tbody>
                                    </table>
                                </div>
            
                            </div>
                        </div>
    
            </div>
    
    </div>

    <div class="row row-cards-one">

        <div class="col-md-12 col-lg-12 col-sm-12 col-xl-12">
            <div class="card">
                <h5 class="card-header"><?php echo e(__('Total Sales in Last 30 Days')); ?></h5>
                <div class="card-body">

                    <canvas  id="lineChart"></canvas>

                </div>
            </div>

        </div>

    </div>




    <div class="row row-cards-one">

        <div class="col-md-12 col-sm-12 col-lg-6 col-xl-6">
            <div class="card">
                <h5 class="card-header"><?php echo e(__('Top Referrals')); ?></h5>
                <div class="card-body">
                    <div class="admin-fix-height-card">
                         <div id="chartContainer-topReference"></div>
                    </div>
                       
                </div>
            </div>

        </div>

        <div class="col-md-12 col-lg-6 col-sm-12 col-xl-6">
                <div class="card">
                        <h5 class="card-header"><?php echo e(__('Most Used OS')); ?></h5>
                        <div class="card-body">
                        <div class="admin-fix-height-card">
                            <div id="chartContainer-os"></div>
                        </div>
                        </div>
                    </div>
        </div>
        
    </div>



</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>

<script type="text/javascript">
    
    (function($) {
		"use strict";

    displayLineChart();

    function displayLineChart() {
        var data = {
            labels: [
            <?php echo $days; ?>

            ],
            datasets: [{
                label: "Prime and Fibonacci",
                fillColor: "#3dbcff",
                strokeColor: "#0099ff",
                pointColor: "rgba(220,220,220,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: [
                <?php echo $sales; ?>

                ]
            }]
        };
        var ctx = document.getElementById("lineChart").getContext("2d");
        var options = {
            responsive: true
        };
        var lineChart = new Chart(ctx).Line(data, options);
    }

    $('#poproducts').dataTable( {
      "ordering": false,
          'lengthChange': false,
          'searching'   : false,
          'ordering'    : false,
          'info'        : false,
          'autoWidth'   : false,
          'responsive'  : true,
          'paging'  : false
    } );

    $('#pproducts').dataTable( {
      "ordering": false,
      'lengthChange': false,
          'searching'   : false,
          'ordering'    : false,
          'info'        : false,
          'autoWidth'   : false,
          'responsive'  : true,
          'paging'  : false
    } );

        var chart1 = new CanvasJS.Chart("chartContainer-topReference",
            {
                exportEnabled: true,
                animationEnabled: true,

                legend: {
                    cursor: "pointer",
                    horizontalAlign: "right",
                    verticalAlign: "center",
                    fontSize: 16,
                    padding: {
                        top: 20,
                        bottom: 2,
                        right: 20,
                    },
                },
                data: [
                    {
                        type: "pie",
                        showInLegend: true,
                        legendText: "",
                        toolTipContent: "{name}: <strong>{#percent%} (#percent%)</strong>",
                        indexLabel: "#percent%",
                        indexLabelFontColor: "white",
                        indexLabelPlacement: "inside",
                        dataPoints: [
                                <?php $__currentLoopData = $referrals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $browser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    {y:<?php echo e($browser->total_count); ?>, name: "<?php echo e($browser->referral); ?>"},
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        ]
                    }
                ]
            });
        chart1.render();

        var chart = new CanvasJS.Chart("chartContainer-os",
            {
                exportEnabled: true,
                animationEnabled: true,
                legend: {
                    cursor: "pointer",
                    horizontalAlign: "right",
                    verticalAlign: "center",
                    fontSize: 16,
                    padding: {
                        top: 20,
                        bottom: 2,
                        right: 20,
                    },
                },
                data: [
                    {
                        type: "pie",
                        showInLegend: true,
                        legendText: "",
                        toolTipContent: "{name}: <strong>{#percent%} (#percent%)</strong>",
                        indexLabel: "#percent%",
                        indexLabelFontColor: "white",
                        indexLabelPlacement: "inside",
                        dataPoints: [
                            <?php $__currentLoopData = $browsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $browser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                {y:<?php echo e($browser->total_count); ?>, name: "<?php echo e($browser->referral); ?>"},
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        ]
                    }
                ]
            });
        chart.render();    

    })(jQuery);
    
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\hp\Herd\new\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>