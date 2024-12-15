@if (isset($order))

    <div class="wrapper">
        <ul class="stepprogress">
            @foreach ($order->tracks as $track)
                <li class="stepprogress-item is-done mb-3"><strong class="fs-5 mb-2">{{ ucwords($track->title) }}</strong>
                    <div class="track-date">{{ date('d m Y', strtotime($track->created_at)) }}</div>
                    {{ $track->text }}
                </li>
            @endforeach
        </ul>
    </div>
@else
    <h3 class="text-center">{{ __('No Order Found.') }}</h3>
@endif
              