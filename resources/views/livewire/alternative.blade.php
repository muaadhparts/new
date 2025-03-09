<div>


    @if($alternatives && $alternatives->count() > 0)

    <div class="col">

    <button type="button" class="template-btn w-100" data-bs-toggle="modal" data-bs-target="#alternativeModal">
        @lang('Alternatives')
    </button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="alternativeModal" tabindex="-1" aria-labelledby="alternativeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alternativeModalLabel"> @lang('Product Alternatives'): {{ $sku }} </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">


                    <div class="container">
{{--                        <h4>Parts List</h4>--}}
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>@lang('Part Number')</th>
                                <th>@lang('Name')</th>
                                <th>@lang('Stock ')</th>
                               <th>@lang('View')</th>


                            </tr>
                            </thead>
                            <tbody>
                            @forelse($alternatives as $result)
                                <tr>
                                    <td>{{ $result->sku }}</td>
                                    <td>{{ $result->label_en }} -
                                        {{ $result->label_ar }}
                                    </td>
{{--                                     <td>{{ $result->showPrice() }}</td>--}}
                                    <td>{{ $result->stock ?? 0 }}</td>
                                    <td>
                                            <a class="btn btn-outline-primary" href="{{ route('front.product', $result->slug) }}">
                                                @lang('View')
                                            </a>

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No data found</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

{{--                    <livewire:alternative />--}}
                </div>
            </div>
        </div>
    </div>
    @endif
</div>