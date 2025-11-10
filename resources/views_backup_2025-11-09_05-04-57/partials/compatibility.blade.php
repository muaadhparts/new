{{-- <div class="p-3">
    <h5 class="mb-3">@lang('labels.fits')</h5>
    <livewire:compatibility-tabs :sku="$sku" :wire:key="'compat-'.$sku" />
</div> --}}


{{-- resources/views/partials/compatibility.blade.php --}}
<div id="compatibility-inline" class="p-2">
    <h6 class="mb-3">🚗 @lang('catalog.compatibility_modal.title')</h6>
    {{-- استخدم المكوّن الذي عندك --}}
    <livewire:compatibility-tabs :sku="$sku" />
</div>
