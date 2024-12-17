<div class="p-5">

{{--    <form action="{{ route('your.route.name.here') }}" method="POST"> <!-- Change action to your desired route -->--}}
        @csrf
        <!-- Loop through the array to create a select input for each item -->


    <div class="row" >


    @foreach ($attributes as $attribute)
            <div class="col">

            <div class="form-group col  ">
                <label for="select-{{ $attribute['id'] }}">{{ $attribute['label'] }}</label>
                <select class="form-control" id="select-{{ $attribute['id'] }}" name="{{ $attribute['name'] }}">
                    <!-- Loop through the items to add options -->
                    @foreach ($attribute['items'] as $subitem)
                        <option value="{{ $subitem['id'] }}" @if($subitem['disabled']) disabled @endif>
                            {{ $subitem['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            </div>
        @endforeach
        </div>
{{--        <button type="submit" class="btn btn-primary">Submit</button>--}}
{{--    </form>--}}

</div>
