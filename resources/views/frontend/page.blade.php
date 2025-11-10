@extends('layouts.front')

@section('content')
<section class="gs-breadcrumb-section bg-class" data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/'.$gs->breadcrumb_banner):asset('assets/images/noimage.png') }}">
    <div class="container">
      <div class="row justify-content-center content-wrapper">
        <div class="col-12">
          <h2 class="breadcrumb-title">{{ $page->title }}</h2>
          <ul class="bread-menu">
            <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
            <li><a href="#">{{ $page->title }}</a></li>
          </ul>
        </div>
      </div>
    </div>
  </section>


<!-- blog wrapper start -->
<div class="gs-blog-wrapper wow-replaced" data-wow-delay=".1s">
    <div class="container">
      <div class="row">
        <div class="col-12 gs-main-blog-wrapper">
          <div class="gs-blog-details-wrapper">
            <div class="gs-blog-card">
              

              <h4 class="fea-title mb-24">
                {{ $page->title }}
              </h4>
              <p class="mb-10">
                {!! clean($page->details , array('Attr.EnableID' => true)) !!}
              </p>
              
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- blog wrapper end -->


@endsection
