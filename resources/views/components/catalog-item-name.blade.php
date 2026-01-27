{{-- Catalog Item Name Component --}}
{{-- DISPLAY ONLY - All data must come pre-computed from controller/service --}}
@props([
    'name' => null,           // Localized name (pre-computed)
    'partNumber' => null,     // PART_NUMBER
    'url' => null,            // Pre-computed URL
    'showSku' => false,
    'target' => '_self',
    'class' => '',
    'nameClass' => '',
    'skuClass' => 'text-muted small',
])

<div class="{{ $class }}">
    @if($url)
        <a href="{{ $url }}" target="{{ $target }}" class="{{ $nameClass }}">
            {{ $name ?? __('N/A') }}
        </a>
    @else
        <span class="{{ $nameClass }}">{{ $name ?? __('N/A') }}</span>
    @endif

    @if($showSku && $partNumber)
        <br>
        <small class="{{ $skuClass }}">
            @if($url)
                <a href="{{ $url }}" target="{{ $target }}">
                    @lang('PART_NUMBER'): {{ $partNumber }}
                </a>
            @else
                @lang('PART_NUMBER'): {{ $partNumber }}
            @endif
        </small>
    @endif
</div>

<style>
/* Catalog Item Name Component Styles */
.catalog-item-name-component a {
    color: var(--dark-color);
    text-decoration: none;
    transition: color var(--transition-fast);
    font-weight: 600;
}

.catalog-item-name-component a:hover {
    color: var(--theme-primary);
}

.catalog-item-name-component small a {
    color: var(--theme-text-muted, #6c757d);
    font-weight: 500;
}

.catalog-item-name-component small a:hover {
    color: var(--theme-primary);
    text-decoration: underline;
}

/* RTL Support */
[dir="rtl"] .catalog-item-name-component {
    text-align: right;
}
</style>
