<div>
    @if (!empty($products))
        <div class="modal fade @if($isOpen) show d-block @endif"
             tabindex="-1"
             role="dialog"
             style="@if($isOpen) background: rgba(0,0,0,0.5); @else display: none; @endif">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content shadow rounded-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            @lang('Part Callout'): {{ $callout }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        @if($isLoading)
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">جاري التحميل...</span>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>@lang('Part Code')</th>
                                            <th>@lang('Part Number')</th>
                                            <th>@lang('Name Part')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($products as $item)
                                            <tr>
                                                <td>{{ $item->callout }}</td>
                                                <td>{{ $item->partnumber }}</td>
                                                <td>
                                                    {{ app()->getLocale() === 'ar'
                                                        ? ($item->label_ar ?? $item->label_en)
                                                        : $item->label_en }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="closeModal">
                            @lang('Close')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        @if($isOpen)
            <div class="modal fade show d-block"
                 tabindex="-1"
                 role="dialog"
                 style="background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                    <div class="modal-content shadow rounded-lg">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title text-dark">@lang('No parts found for this callout.')</h5>
                            <button type="button" class="btn-close" wire:click="closeModal"></button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
