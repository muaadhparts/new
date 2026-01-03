{{-- resources/views/quick.blade.php --}}

{{-- 
    This view is rendered when opening a quick view via the modal.  
    It wraps the catalogItem fragment inside a `.modal-body` container so that the JavaScript loader
    can extract it and insert it into the modal correctly.  
    It leverages the partial we created (`partials.catalogItem`) to display all relevant catalogItem
    information (images, price, ratings, stock, actions, etc.).
--}}

<div class="modal-body">
    @include('partials.catalogItem', ['catalogItem' => $catalogItem])
</div>
