{{-- Recursive category tree component --}}
{{-- computed_url, computed_label, has_children pre-computed in CategoryTreeService (DATA_FLOW_POLICY) --}}
<ul class="tree-list" style="{{ $level > 1 ? 'display: none;' : '' }}">
    @foreach($categories as $category)
        <li class="tree-item-wrapper">
            <div class="tree-item d-flex align-items-center {{ ($selectedCategory && $selectedCategory->id === $category->id) ? 'active' : '' }} {{ ($selectedCategory && $breadcrumb->contains('id', $category->id)) ? 'ancestor' : '' }}"
                 data-id="{{ $category->id }}"
                 data-level="{{ $category->level }}">

                @if($category->has_children)
                    <span class="tree-toggle {{ (($selectedCategory && $breadcrumb->contains('id', $category->id)) || ($selectedCategory && $selectedCategory->id === $category->id)) ? 'expanded' : '' }}"
                          onclick="toggleTreeItem(this, event)">
                        <i class="fas fa-chevron-{{ (($selectedCategory && $breadcrumb->contains('id', $category->id)) || ($selectedCategory && $selectedCategory->id === $category->id)) ? 'down' : (app()->getLocale() === 'ar' ? 'left' : 'right') }}"></i>
                    </span>
                @else
                    <span class="tree-toggle" style="visibility: hidden;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i>
                    </span>
                @endif

                <a href="{{ $category->computed_url }}" class="tree-link flex-grow-1">
                    {{ $category->computed_label }}
                </a>
            </div>

            @if($category->has_children)
                <div class="tree-children {{ (($selectedCategory && $breadcrumb->contains('id', $category->id)) || ($selectedCategory && $selectedCategory->id === $category->id)) ? 'show' : '' }}"
                     style="{{ (($selectedCategory && $breadcrumb->contains('id', $category->id)) || ($selectedCategory && $selectedCategory->id === $category->id)) ? 'display: block;' : 'display: none;' }}">
                    @include('partials.catalog.category-tree-recursive', [
                        'categories' => $category->children_items,
                        'selectedCategory' => $selectedCategory,
                        'breadcrumb' => $breadcrumb ?? collect(),
                        'hierarchy' => $hierarchy ?? [],
                        'brand_slug' => $brand_slug,
                        'catalog_slug' => $catalog_slug,
                        'level' => $level + 1,
                    ])
                </div>
            @endif
        </li>
    @endforeach
</ul>

@if($level == 1)
<script>
function toggleTreeItem(element, event) {
    event.preventDefault();
    event.stopPropagation();

    const $toggle = $(element);
    const $wrapper = $toggle.closest('.tree-item-wrapper');
    const $children = $wrapper.find('> .tree-children');
    const $icon = $toggle.find('i');

    if ($children.is(':visible')) {
        $children.slideUp(200);
        $toggle.removeClass('expanded');
        $icon.removeClass('fa-chevron-down').addClass('{{ app()->getLocale() === "ar" ? "fa-chevron-left" : "fa-chevron-right" }}');
    } else {
        $children.slideDown(200);
        $toggle.addClass('expanded');
        $icon.removeClass('{{ app()->getLocale() === "ar" ? "fa-chevron-left" : "fa-chevron-right" }}').addClass('fa-chevron-down');
    }
}
</script>
@endif
