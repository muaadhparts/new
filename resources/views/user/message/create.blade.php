@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.user.sidebar')
                <!-- main content -->
                <div class="gs-user-conversation-content-wrapper gs-dashboard-outlet">
                    <div class="gs-deposit-title ms-0 mb-4 d-flex align-items-center">

                        <a href="{{ url()->previous() }}" class="back-btn">
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </a>
                        <h3>@lang('Conversation with')

                            @if ($user->id == $conv->sent->id)
                                {{ $conv->recieved->name }}
                            @else
                                {{ $conv->sent->name }}
                            @endif
                        </h3>
                    </div>

                    <!-- conversation boxes start -->
                    <div class="conversation-boxes-wrapper w-100 wow-replaced" data-wow-delay=".1s">
                        @foreach ($conv->messages as $message)
                            @if ($message->sent_user != null)
                                <!-- conversation box 1 -->
                                <div class="conversation-box wow-replaced" data-wow-delay=".1s">
                                    <div>
                                        <div class="message-sender-box">
                                            @if ($message->conversation->sent->is_provider == 1)
                                                <img src="{{ $message->conversation->sent->photo != null ? $message->conversation->sent->photo : asset('assets/images/noimage.png') }}"
                                                    alt="" class="avater">
                                            @else
                                                <img src="{{ $message->conversation->sent->photo != null ? asset('assets/images/users/' . $message->conversation->sent->photo) : asset('assets/images/noimage.png') }}"
                                                    alt="" class="avater">
                                            @endif
                                            <p class="message-sender">{{ $message->conversation->sent->name }}</p>
                                        </div>
                                    </div>
                                    <div class="message-and-time-wrapper">
                                        <p class="time">
                                            {{ \Carbon\Carbon::parse($message->created_at)->diffForHumans() }}
                                        </p>
                                        <p class="message">
                                            {{ $message->message }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <!-- conversation box 2 -->
                                <div class="conversation-box conversation-right wow-replaced" data-wow-delay=".1s">
                                    <div class="message-and-time-wrapper flex-grow-1">
                                        <p class="time">
                                            {{ \Carbon\Carbon::parse($message->created_at)->diffForHumans() }}
                                        </p>
                                        <p class="message">
                                            {{ $message->message }}
                                        </p>
                                    </div>
                                    <div>
                                        <div class="message-sender-box">
                                            @if ($message->conversation->sent->is_provider == 1)
                                                <img src="{{ $message->conversation->recieved->photo != null ? $message->conversation->recieved->photo : asset('assets/images/noimage.png') }}"
                                                    alt="" class="avater">
                                            @else
                                                <img src="{{ $message->conversation->recieved->photo != null ? asset('assets/images/users/' . $message->conversation->recieved->photo) : asset('assets/images/noimage.png') }}"
                                                    alt="" class="avater">
                                            @endif
                                            <p class="message-sender">{{ $message->conversation->recieved->name }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                    </div>
                    <!-- conversation boxes end -->
                    <!-- send message form  -->
                    <form action="{{ route('user-message-post') }}" method="POST" class="send-message-form wow-replaced"
                        data-wow-delay=".1s">
                        @csrf

                        <input type="hidden" name="conversation_id" value="{{ $conv->id }}">
                        @if ($user->id == $conv->sent_user)
                            <input type="hidden" name="sent_user" value="{{ $conv->sent->id }}">
                            <input type="hidden" name="reciever" value="{{ $conv->recieved->id }}">
                        @else
                            <input type="hidden" name="reciever" value="{{ $conv->sent->id }}">
                            <input type="hidden" name="recieved_user" value="{{ $conv->recieved->id }}">
                        @endif
                        <textarea placeholder="@lang('Message')" class="message-input" name="message" id="reply-name" rows="10"></textarea>
                        <button type="submit" class="template-btn conversation-reply-btn">Add Reply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection
