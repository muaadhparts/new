<div class="container">





    <div class=" product-nav-wrapper">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item "><a  class="text-black" href="{{route('front.index')}}">Home</a></li>
                <li class="breadcrumb-item"><a class="text-black" href="{{route('catlogs.index',$brand->name)}}">{{$brand->name}}</a></li>
                <li class="breadcrumb-item active"><a class="text-primary" href="{{route('tree.level1',['id'=> $brand->name ,'data'=> $vehicle ])}}">{{$vehicle}}</a></li>
{{--                <li class="breadcrumb-item active" aria-current="page">Data</li>--}}
            </ol>
        </nav>
    </div>

    <div class="row gy-4 gy-lg-5 mt-4 mb-10">



        <livewire:vehicle-search-box  :vehicle="$vehicle"/>



    @foreach ($categories  as $catalog)
{{--                    @dd($catalog->data , $catalog );--}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-3 text-center">
            <a href="{{route('tree.level2',['id'=> $brand->name ,'data'=> $catalog->data ,'key1' => $catalog->code ])}}">
                <div class="single-product card border-0 shadow-sm h-100  ">

                    <div class="img-wrapper position-relative">
                        <img class=" img-fluid rounded" src="{{ Storage::url($catalog->thumbnailimage) }}" alt="product img">
                    </div>
                    <div class="p-3 text-center">

                            <h6 class="product-title text-dark fw-bold text-center">{{ $catalog->label }}</h6>


                    </div>
                </div>
            </a>
        </div>
    @endforeach

    </div>





</div>