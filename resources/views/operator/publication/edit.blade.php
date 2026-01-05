@extends('layouts.operator')

@section('content')
            <div class="content-area">
              <div class="mr-breadcrumb">
                <div class="row">
                  <div class="col-lg-12">
                      <h4 class="heading">{{ __('Edit Post') }} <a class="add-btn" href="{{route('operator-publication-index')}}"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                      <ul class="links">
                        <li>
                          <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                        </li>
                        <li><a href="javascript:;">{{ __('Publications') }}</a></li>
                        <li>
                          <a href="{{ route('operator-publication-index') }}">{{ __('Posts') }}</a>
                        </li>
                        <li>
                          <a href="{{ route('operator-publication-edit',$publication->id) }}">{{ __('Edit Post') }}</a>
                        </li>
                      </ul>
                  </div>
                </div>
              </div>
              <div class="add-catalogItem-content">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="catalogItem-description">
                      <div class="body-area">
                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                        @include('includes.admin.form-both')  
                      <form id="muaadhform" action="{{route('operator-publication-update',$publication->id)}}" method="POST" enctype="multipart/form-data">
                        {{csrf_field()}}


                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Category') }}*</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <select name="category_id" required="">
                                  <option value="">{{ __('Select Category') }}</option>
                                    @foreach($cats as $cat)
                                      <option value="{{ $cat->id }}" {{ $publication->category_id == $cat->id ? 'selected' :'' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Title') }} *</h4>
                                <p class="sub-heading">{{ __('(In Any Language)') }}</p>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="title" placeholder="Title" value="{{$publication->title}}" required="">
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Current Featured Image') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <div class="img-upload">
                                <div id="image-preview" class="img-preview" style="background: url({{ $publication->photo ? asset('assets/images/publications/'.$publication->photo):asset('assets/images/noimage.png') }});">
                                    <label for="image-upload" class="img-label" id="image-label"><i class="icofont-upload-alt"></i>{{ __('Upload Image') }}</label>
                                    <input type="file" name="photo" class="img-upload" id="image-upload">
                                  </div>
                            </div>

                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                              <h4 class="heading">
                                   {{ __('Description') }} *
                              </h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                              <textarea class="nic-edit" name="details" placeholder="{{ __('Details') }}">{{ $publication->details }}</textarea> 
                          </div>
                        </div>


                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Source') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <input type="text" class="form-control" name="source" placeholder="{{ __('Source') }}" required="" value="{{$publication->source}}">

                            <div class="checkbox-wrapper">
                              <input type="checkbox" name="secheck" class="checkclick" id="allowProductSEO" {{ ($publication->meta_tag != null || strip_tags($publication->meta_description) != null) ? 'checked':'' }}>
                              <label for="allowProductSEO">{{ __('Allow Blog SEO') }}</label>
                            </div>

                          </div>
                        </div>

                        <div class="{{ ($publication->meta_tag == null && strip_tags($publication->meta_description) == null) ? "showbox":"" }}">
                          <div class="row">
                            <div class="col-lg-4">
                              <div class="left-area">
                                  <h4 class="heading">{{ __('Meta Tags') }} *</h4>
                              </div>
                            </div>
                            <div class="col-lg-7">
                              <ul id="metatags" class="myTags">
                                @foreach (explode(',',$publication->meta_tag) as $element)
                                  <li>{{  $element }}</li>
                                @endforeach
                              </ul>
                            </div>
                          </div>  

                          <div class="row">
                            <div class="col-lg-4">
                              <div class="left-area">
                                <h4 class="heading">
                                    {{ __('Meta Description') }} *
                                </h4>
                              </div>
                            </div>
                            <div class="col-lg-7">
                              <div class="text-editor">
                                <textarea class="form-control"  name="meta_description" placeholder="{{ __('Meta Description') }}">{{ $publication->meta_description }}</textarea> 
                              </div>
                            </div>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                                <h4 class="heading">{{ __('Tags') }} *</h4>
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <ul id="tags" class="myTags">
                                @foreach (explode(',',$publication->tags) as $element)
                                  <li>{{  $element }}</li>
                                @endforeach
                            </ul>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-lg-4">
                            <div class="left-area">
                              
                            </div>
                          </div>
                          <div class="col-lg-7">
                            <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                          </div>
                        </div>
                      </form>


                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>


@endsection

@section('scripts')

<script type="text/javascript">

(function($) {
		"use strict";

          $("#metatags").tagit({
          fieldName: "meta_tag[]",
          allowSpaces: true 
          });

          $("#tags").tagit({
          fieldName: "tags[]",
          allowSpaces: true 
        });

})(jQuery);

</script>

@endsection
