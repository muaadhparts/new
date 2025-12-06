

<div class="p-3">
    <h5 class="mb-3"><?php echo app('translator')->get('labels.substitutions'); ?></h5>
    <div id="alternatives-container-<?php echo e($sku); ?>" class="alternatives-container">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const sku = '<?php echo e($sku); ?>';
    const container = document.getElementById('alternatives-container-' + sku);

    fetch('/api/product/alternatives/' + encodeURIComponent(sku) + '/html')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                container.innerHTML = data.html;
            } else {
                container.innerHTML = '<p class="text-muted"><?php echo app('translator')->get("labels.no_alternatives"); ?></p>';
            }
        })
        .catch(error => {
            console.error('Error loading alternatives:', error);
            container.innerHTML = '<p class="text-danger"><?php echo app('translator')->get("labels.error_loading"); ?></p>';
        });
})();
</script>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/partials/alternative.blade.php ENDPATH**/ ?>