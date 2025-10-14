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
                        <div>
                            <h6 class="mt-5 ms-3">
                                @if ($conv->order_number != null)
                                    {{ __('Order Number:') }} {{ $conv->order_number }}
                                @endif
                            </h6>

                            <h3 class="mt-3">@lang('Conversation with')
                                {{ $conv->subject }}

                            </h3>
                        </div>
                    </div>



                    <!-- conversation boxes start -->
                    <div class="conversation-boxes-wrapper w-100 wow-replaced" data-wow-delay=".1s">
                        @foreach ($conv->messages as $message)
                            @if ($message->user_id != 0)
                                <!-- conversation box 1 -->
                                <div class="conversation-box wow-replaced" data-wow-delay=".1s">
                                    <div>
                                        <div class="message-sender-box">
                                            @if ($message->conversation->user->is_provider == 1)
                                                <img src="{{ $message->conversation->user->photo != null ? $message->conversation->user->photo : asset('assets/images/noimage.png') }}"
                                                    alt="" class="avater">
                                            @else
                                                <img src="{{ $message->conversation->user->photo != null ? asset('assets/images/users/' . $message->conversation->user->photo) : asset('assets/images/noimage.png') }}"
                                                    alt="" class="avater">
                                            @endif
                                            <p class="message-sender">{{ $message->conversation->user->name }}</p>
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

                                            <img src="{{ asset('assets/images/admin.jpg') }}" alt=""
                                                class="avater">

                                            <p class="message-sender">@lang('Admin')</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                    </div>
                    <!-- conversation boxes end -->
                    <!-- send message form  -->
                    <form action="{{ route('user-message-store') }}" method="POST" class="send-message-form wow-replaced"
                        data-wow-delay=".1s">
                        @csrf

                        <input type="hidden" name="conversation_id" value="{{ $conv->id }}">
                        <input type="hidden" name="user_id" value="{{ $conv->user->id }}">
                        <textarea placeholder="@lang('Message')" class="message-input" name="message" id="reply-name" rows="10"></textarea>
                        <button type="submit" class="template-btn conversation-reply-btn">@lang('Add Reply')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- user dashboard wrapper end -->
@endsection
