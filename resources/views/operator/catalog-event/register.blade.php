		<a class="clear">{{ __('New Event(s).') }}</a>
		@if(count($datas) > 0)
		<a id="user-event-clear" data-href="{{ route('user-event-clear') }}" class="clear" href="javascript:;">
			{{ __('Clear All') }}
		</a>
		<ul>
		@foreach($datas as $data)
			<li>
				<a href="{{ route('operator-user-show',$data->user_id) }}"> <i class="fas fa-user"></i> {{ __('A New User Has Registered.') }}</a>
			</li>
		@endforeach

		</ul>

		@else

		<a class="clear" href="javascript:;">
			{{ __('No New Events.') }}
		</a>

		@endif
