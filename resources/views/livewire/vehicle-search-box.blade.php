<div class="  xmb-10 text-center">


    <div class="autoComplete_wrapper">
        <input  style=""
                type="text"
                id="autoComplete"
                class="form-control  "
                placeholder="VIN / Part Number / Part Code"
                wire:model.debounce.300ms="query"
        >

        @if (!empty($results))
            {{--                @dd($results);--}}
            <ul  id="autoComplete_list_1"   role="listbox" >
                @foreach ($results as $result)
                    <li xclass="list-group-item d-flex justify-content-between" xstyle="display: flex; justify-content: space-between;">
                        <span wire:click="selectItem('{{ $result->partnumber }}')" xstyle="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" >
                            {{ $result->partnumber  }}
                        </span>
                        <small class="text-black"  style="display: flex; align-items: center; font-size: 13px; font-weight: 100; text-transform: uppercase;">{{ $result->label_en }} - {{ $result->label_ar }}</small>
                    </li>
                @endforeach
            </ul>
        @endif

    </div>
</div>
