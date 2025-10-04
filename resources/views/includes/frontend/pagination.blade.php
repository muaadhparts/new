@if ($paginator->hasPages())
  <div class="col-12">
    <nav class="d-flex justify-content-center mt-5" aria-label="@lang('Page navigation')">
      <ul class="pagination pagination-modern gap-2">

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
          <li class="page-item disabled">
            <span class="page-link" aria-label="@lang('Previous')">
              <i class="fas fa-chevron-left"></i>
            </span>
          </li>
        @else
          <li class="page-item">
            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('Previous')">
              <i class="fas fa-chevron-left"></i>
            </a>
          </li>
        @endif

        {{-- Page Number Links --}}
        @foreach ($elements as $element)
          @if (is_array($element) && count($element) < 2)
            {{-- Skip single page --}}
          @else
            @if (is_array($element))
              @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                  <li class="page-item active" aria-current="page">
                    <span class="page-link">{{ $page }}</span>
                  </li>
                @else
                  <li class="page-item">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                  </li>
                @endif
              @endforeach
            @endif
          @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
          <li class="page-item">
            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('Next')">
              <i class="fas fa-chevron-right"></i>
            </a>
          </li>
        @else
          <li class="page-item disabled">
            <span class="page-link" aria-label="@lang('Next')">
              <i class="fas fa-chevron-right"></i>
            </span>
          </li>
        @endif

      </ul>
    </nav>
  </div>
@endif

<style>
/* Enhanced Modern Pagination */
.pagination-modern {
    margin: 0;
}

.pagination-modern .page-item .page-link {
    border-radius: var(--border-radius-sm);
    border: 1.5px solid var(--border-color);
    color: var(--dark-color);
    font-weight: 500;
    min-width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    background: #fff;
    box-shadow: var(--box-shadow-sm);
}

.pagination-modern .page-item .page-link:hover {
    background: var(--primary-color);
    color: #fff;
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--box-shadow);
}

.pagination-modern .page-item.active .page-link {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: #fff;
    box-shadow: var(--box-shadow);
}

.pagination-modern .page-item.disabled .page-link {
    background: #f8f9fa;
    border-color: var(--border-color);
    color: #6c757d;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .pagination-modern .page-item .page-link {
        min-width: 36px;
        height: 36px;
        font-size: 0.875rem;
    }

    .pagination-modern {
        gap: 0.25rem !important;
    }
}
</style>
