<div>
    alternativealternativealternative

    @if($product)
        <h5>Product Details</h5>
        <p><strong>Name:</strong> {{ $product->name }}</p>
        <p><strong>Description:</strong> {{ $product->description }}</p>
    @else
        <p>Loading...</p>
    @endif


{{--    <button type="button" class="btn btn-outline-primary" onclick="Livewire.emit('showAlternativeModal', '{{ $product->sku }}')">--}}
{{--        Alternatives--}}
{{--    </button>--}}

{{--    <!-- Modal -->--}}
{{--    <div class="modal fade" id="alternativeModal" tabindex="-1" aria-labelledby="alternativeModalLabel" aria-hidden="true">--}}
{{--        <div class="modal-dialog">--}}
{{--            <div class="modal-content">--}}
{{--                <div class="modal-header">--}}
{{--                    <h5 class="modal-title" id="alternativeModalLabel">Product Alternatives</h5>--}}
{{--                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>--}}
{{--                </div>--}}
{{--                <div class="modal-body">--}}
{{--                    <livewire:alternative />--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
</div>