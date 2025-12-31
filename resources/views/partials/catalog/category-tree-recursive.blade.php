{{-- Recursive category tree component --}}
@php
    $locale = app()->getLocale();
    $breadcrumb = $breadcrumb ?? collect();
@endphp

<ul class="tree-list" style="{{ $level > 1 ? 'display: none;' : '' }}">
    @foreach($categories as $category)
        @php
            $isSelected = $selectedCategory && $selectedCategory->id === $category->id;
            $isAncestor = $selectedCategory && $breadcrumb->contains('id', $category->id);
            $hasChildren = $category->children_items && $category->children_items->count() > 0;
            $label = $locale === 'ar' ? ($category->label_ar ?: $category->label_en) : $category->label_en;

            // Build URL for this category based on its actual parent chain
            $catParams = [
                'brand_slug' => $brand_slug,
                'catalog_slug' => $catalog_slug,
            ];

            if ($category->level == 1) {
                $catParams['cat1'] = $category->slug;
            } elseif ($category->level == 2) {
                // Use parent slug passed from recursion
                $catParams['cat1'] = $parentCat1Slug ?? null;
                $catParams['cat2'] = $category->slug;
            } elseif ($category->level == 3) {
                // Use parent slugs passed from recursion
                $catParams['cat1'] = $parentCat1Slug ?? null;
                $catParams['cat2'] = $parentCat2Slug ?? null;
                $catParams['cat3'] = $category->slug;
            }

            $categoryUrl = route('front.catalog.category', $catParams);
        @endphp

        <li class="tree-item-wrapper">
            <div class="tree-item d-flex align-items-center {{ $isSelected ? 'active' : '' }} {{ $isAncestor ? 'ancestor' : '' }}"
                 data-id="{{ $category->id }}"
                 data-level="{{ $category->level }}">

                @if($hasChildren)
                    <span class="tree-toggle {{ $isAncestor || $isSelected ? 'expanded' : '' }}"
                          onclick="toggleTreeItem(this, event)">
                        <i class="fas fa-chevron-{{ ($isAncestor || $isSelected) ? 'down' : (app()->getLocale() === 'ar' ? 'left' : 'right') }}"></i>
                    </span>
                @else
                    <span class="tree-toggle" style="visibility: hidden;">
                        <i class="fas fa-circle" style="font-size: 6px;"></i>
                    </span>
                @endif

                <a href="{{ $categoryUrl }}" class="tree-link flex-grow-1">
                    {{ $label }}
                </a>
            </div>

            @if($hasChildren)
                <div class="tree-children {{ ($isAncestor || $isSelected) ? 'show' : '' }}"
                     style="{{ ($isAncestor || $isSelected) ? 'display: block;' : 'display: none;' }}">
                    @include('partials.catalog.category-tree-recursive', [
                        'categories' => $category->children_items,
                        'selectedCategory' => $selectedCategory,
                        'breadcrumb' => $breadcrumb,
                        'hierarchy' => $hierarchy ?? [],
                        'brand_slug' => $brand_slug,
                        'catalog_slug' => $catalog_slug,
                        'level' => $level + 1,
                        // Pass parent slugs for URL building without N+1 queries
                        'parentCat1Slug' => $category->level == 1 ? $category->slug : ($parentCat1Slug ?? null),
                        'parentCat2Slug' => $category->level == 2 ? $category->slug : ($parentCat2Slug ?? null),
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
