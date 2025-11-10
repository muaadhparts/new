@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Faq')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.faq') }}">@lang('Faq')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <div class="gs-faq-section">
      <div class="container">
        <div class="faq-box">
          <div class="accordion hyp-accordians accordion-flush" id="faqlist">
            @foreach($faqs as $key => $faq)
            <div class="accordion-item wow-replaced" data-wow-delay=".1s">
              <h2 class="accordion-header">
                <button class="accordion-button {{$loop->first ? '' : 'collapsed'}}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-{{$key}}"
                  aria-expanded="true">
                  {{ $faq->title }}
                </button>
              </h2>
              <div id="faq-content-{{$key}}" class="accordion-collapse collapse {{$loop->first ? 'show' : ''}}" data-bs-parent="#faqlist">
                <div class="accordion-body">
                  {!! clean($faq->details , array('Attr.EnableID' => true)) !!}
                </div>
              </div>
            </div>
            @endforeach
  
      
           
          </div>
        </div>
      </div>
    </div>


@endsection
