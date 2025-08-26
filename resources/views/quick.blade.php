{{-- resources/views/quick.blade.php --}}

{{-- 
    This view is rendered when opening a quick view via the modal.  
    It wraps the product fragment inside a `.modal-body` container so that the JavaScript loader
    can extract it and insert it into the modal correctly.  
    It leverages the partial we created (`partials.product`) to display all relevant product
    information (images, price, ratings, stock, actions, etc.).
--}}

<div class="modal-body">
    @include('partials.product', ['product' => $product])
</div>
