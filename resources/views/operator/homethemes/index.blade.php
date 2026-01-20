@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Home Page Themes') }}
                    <a class="add-btn" href="{{ route('operator-homethemes-create') }}">
                        <i class="fas fa-plus"></i> {{ __('Add New Theme') }}
                    </a>
                </h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Home Page Settings') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('operator-homethemes-index') }}">{{ __('Home Page Themes') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="catalogItem-area">
        <div class="row">
            <div class="col-lg-12">
                @include('alerts.operator.form-success')

                <div class="row">
                    @forelse($themes as $theme)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 {{ $theme->is_active ? 'border-success' : '' }}">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ $theme->name }}</h5>
                                @if($theme->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @endif
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-2">{{ __('Slug') }}: {{ $theme->slug }}</p>
                                <p class="text-muted small mb-3">{{ __('Layout') }}: {{ ucfirst($theme->layout) }}</p>

                                <h6 class="mb-2">{{ __('Enabled Sections') }}:</h6>
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @if($theme->show_hero_search)<span class="badge bg-primary">Hero Search</span>@endif
                                    @if($theme->show_brands)<span class="badge bg-primary">Brands</span>@endif
                                    @if($theme->show_categories)<span class="badge bg-primary">Categories</span>@endif
                                    @if($theme->show_arrival)<span class="badge bg-primary">Arrival</span>@endif
                                    @if($theme->show_blogs)<span class="badge bg-primary">Blogs</span>@endif
                                    @if($theme->show_newsletter)<span class="badge bg-primary">Newsletter</span>@endif
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="{{ route('operator-homethemes-edit', $theme->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i> {{ __('Edit') }}
                                        </a>
                                        <a href="{{ route('operator-homethemes-duplicate', $theme->id) }}" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-copy"></i> {{ __('Duplicate') }}
                                        </a>
                                    </div>
                                    <div>
                                        @if(!$theme->is_active)
                                            <a href="{{ route('operator-homethemes-activate', $theme->id) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> {{ __('Activate') }}
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-href="{{ route('operator-homethemes-delete', $theme->id) }}" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="alert alert-info">
                            {{ __('No themes found. Create your first theme!') }}
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-name">{{ __('Confirm Delete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Are you sure you want to delete this theme?') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <a href="#" id="deleteBtn" class="btn btn-danger">{{ __('Delete') }}</a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function($) {
    "use strict";

    $('#deleteModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var href = button.data('href');
        $('#deleteBtn').attr('href', href);
    });

    // Handle delete via AJAX
    $('#deleteBtn').on('click', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');

        $.ajax({
            url: href,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON.error || 'Error deleting theme');
            }
        });
    });

})(jQuery);
</script>
@endsection
