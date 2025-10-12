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
/* ========================================
   MODERN PAGINATION DESIGN
   ======================================== */
.pagination-modern {
    margin: 0;
    padding: 0;
}

.pagination-modern .page-item .page-link {
    border-radius: 12px;
    border: 2px solid transparent;
    color: #64748b;
    font-weight: 700;
    min-width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
}

.pagination-modern .page-item .page-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.pagination-modern .page-item .page-link:hover {
    color: #fff;
    border-color: transparent;
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.25);
}

.pagination-modern .page-item .page-link:hover::before {
    opacity: 1;
}

.pagination-modern .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
    transform: scale(1.1);
}

.pagination-modern .page-item.disabled .page-link {
    background: #f1f5f9;
    border-color: transparent;
    color: #cbd5e1;
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: none;
}

.pagination-modern .page-item.disabled .page-link:hover {
    transform: none;
    box-shadow: none;
}

/* Arrow Buttons Enhancement */
.pagination-modern .page-item:first-child .page-link,
.pagination-modern .page-item:last-child .page-link {
    min-width: 48px;
    font-size: 1rem;
    font-weight: 800;
}

/* ========================================
   RESPONSIVE PAGINATION
   ======================================== */
@media (max-width: 767px) {
    .pagination-modern .page-item .page-link {
        min-width: 40px;
        height: 40px;
        font-size: 0.9rem;
        border-radius: 10px;
    }

    .pagination-modern .page-item:first-child .page-link,
    .pagination-modern .page-item:last-child .page-link {
        min-width: 44px;
    }

    .pagination-modern {
        gap: 0.35rem !important;
    }
}

@media (max-width: 576px) {
    .pagination-modern .page-item .page-link {
        min-width: 36px;
        height: 36px;
        font-size: 0.85rem;
        border-radius: 8px;
    }

    .pagination-modern .page-item:first-child .page-link,
    .pagination-modern .page-item:last-child .page-link {
        min-width: 40px;
        font-size: 0.9rem;
    }

    .pagination-modern {
        gap: 0.25rem !important;
    }
}

@media (max-width: 375px) {
    .pagination-modern .page-item .page-link {
        min-width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }

    .pagination-modern .page-item:first-child .page-link,
    .pagination-modern .page-item:last-child .page-link {
        min-width: 36px;
    }
}
</style>
