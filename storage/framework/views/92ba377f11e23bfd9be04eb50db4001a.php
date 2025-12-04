

<div class="p-3">
    <h5 class="mb-3"><?php echo app('translator')->get('labels.substitutions'); ?></h5>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('alternative', ['sku' => $sku]);

$__html = app('livewire')->mount($__name, $__params, 'alt-'.$sku, $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
</div>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/alternative.blade.php ENDPATH**/ ?>