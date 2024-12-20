<div class="container">


    <div class="row gy-4 gy-lg-5 mt-4 mb-10">

        <livewire:attributes  :vehicle="$vehicle" />

    @foreach ($categories  as $catalog)
{{--                    @dd($catalog->data , $catalog );--}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
            <a href="{{route('illustrations',['id'=> $brand->name ,'data'=> $catalog->data ,'key1' => $catalog->key1  , 'key2'=>$catalog->key2,'code'=>$catalog->code])}}">

                 <div class="single-product card border-0 shadow-sm h-100  ">

                    <div class="img-wrapper position-relative">
                        <img class=" img-fluid rounded" src="{{ Storage::url($catalog->thumbnailimage) }}" alt="product img">
                    </div>
                    <div class="p-3 text-center">

                            <h6 class="product-title text-dark fw-bold text-center">{{ preg_replace('/\s*\(.*?\)/', '', $catalog->label)  }}</h6>
                    </div>
                </div>
            </a>
        </div>
    @endforeach

    </div>





</div>