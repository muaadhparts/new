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
                    @dump($results)
{{--                    @if(filled($results))--}}
                        <div class="container">
                            <h4>Parts List</h4>
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Part Number</th>
                                    <th>Callout</th>
                                    <th>Label  </th>
                                    <th>Applicability</th>
                                    <th>Code</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($results as $result)
                                    <tr>
                                        <td>{{ $result->partnumber }}</td>
                                        <td>{{ $result->callout }}</td>
                                        <td>{{ $result->label_en }}</td>
                                        <td>{{ $result->applicability }}</td>
                                        <td>{{ $result->code }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No data found</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>


{{--                    @foreach($results as $index => $result)--}}

{{--                            {{$result->code}}--}}
{{--                    @endforeach--}}
{{--                    @endif--}}
                </div>
            @endif
        @endforeach
    </div>
</div>
