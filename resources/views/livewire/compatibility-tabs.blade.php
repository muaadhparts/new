<div>
    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        @foreach($catalogs as $index => $catalog)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === $catalog->data ? 'active' : '' }}"
                        wire:click="setActiveTab('{{$catalog->data}}')"
                        type="button">
                    {{$catalog->name}}
                </button>
            </li>
        @endforeach
    </ul>

    <!-- Dynamic Content -->
    <div class="tab-content" id="pills-tabContent">
        @foreach($catalogs as $index => $catalog)
            @if($activeTab === $catalog->data)
                <div class="tab-pane fade show active">

                    <h5>{{$catalog->name}}</h5>
                    <p>{{$catalog->data}}</p>
                    @dump($products)
                    @if(filled($products))
                    @foreach($products as $index => $product)

                            {{$product->code}}
                    @endforeach
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</div>
