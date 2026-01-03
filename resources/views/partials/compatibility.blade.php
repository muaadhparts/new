{{-- resources/views/partials/compatibility.blade.php --}}

<div class="p-3">
    <h5 class="mb-3">@lang('labels.fits')</h5>
    <div id="compatibility-container-{{ $part_number }}" class="compatibility-container">
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const part_number = '{{ $part_number }}';
    const container = document.getElementById('compatibility-container-' + part_number);

    fetch('/api/catalogItem/compatibility/' + encodeURIComponent(part_number) + '/html')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.html) {
                container.innerHTML = data.html;
            } else {
                container.innerHTML = '<p class="text-muted">@lang("labels.no_compatibility")</p>';
            }
        })
        .catch(error => {
            console.error('Error loading compatibility:', error);
            container.innerHTML = '<p class="text-danger">@lang("labels.error_loading")</p>';
        });
})();
</script>
