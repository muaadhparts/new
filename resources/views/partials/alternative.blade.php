{{-- resources/views/partials/alternative.blade.php --}}

<div class="p-3">
    <h5 class="mb-3">@lang('labels.substitutions')</h5>
    <div id="alternatives-container-{{ $sku }}" class="alternatives-container">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const sku = '{{ $sku }}';
    const container = document.getElementById('alternatives-container-' + sku);

    fetch('/api/catalogItem/alternatives/' + encodeURIComponent(sku) + '/html')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                container.innerHTML = data.html;
            } else {
                container.innerHTML = '<p class="text-muted">@lang("labels.no_alternatives")</p>';
            }
        })
        .catch(error => {
            console.error('Error loading alternatives:', error);
            container.innerHTML = '<p class="text-danger">@lang("labels.error_loading")</p>';
        });
})();
</script>
