@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">@lang('Help Article')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.help-article') }}">@lang('Help Article')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>


    <div class="gs-help-article-section muaadh-section-gray">
      <div class="container">
        <div class="help-article-box">
          <div class="accordion hyp-accordians accordion-flush" id="helpArticleList">
            @foreach($helpArticles as $key => $helpArticle)
            <div class="accordion-item wow-replaced" data-wow-delay=".1s">
              <h2 class="accordion-header">
                <button class="accordion-button {{$loop->first ? '' : 'collapsed'}}" type="button" data-bs-toggle="collapse" data-bs-target="#help-article-content-{{$key}}"
                  aria-expanded="true">
                  {{ $helpArticle->name }}
                </button>
              </h2>
              <div id="help-article-content-{{$key}}" class="accordion-collapse collapse {{$loop->first ? 'show' : ''}}" data-bs-parent="#helpArticleList">
                <div class="accordion-body">
                  {!! clean($helpArticle->details , array('Attr.EnableID' => true)) !!}
                </div>
              </div>
            </div>
            @endforeach



          </div>
        </div>
      </div>
    </div>


@endsection
