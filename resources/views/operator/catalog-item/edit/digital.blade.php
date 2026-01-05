@extends('layouts.operator')

@section('content')
	<div class="content-area">
		<div class="mr-breadcrumb">
			<div class="row">
				<div class="col-lg-12">
						<h4 class="heading"> {{ __("Edit CatalogItem") }}<a class="add-btn" href="{{ url()->previous() }}"><i class="fas fa-arrow-left"></i> {{ __("Back") }}</a></h4>
						<ul class="links">
							<li>
								<a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }} </a>
							</li>
							<li>
								<a href="{{ route('operator-catalog-item-index') }}">{{ __("Catalog Items") }} </a>
							</li>
							<li>
								<a href="javascript:;">{{ __("Digital CatalogItem") }}</a>
							</li>
							<li>
								<a href="{{ url()->previous() }}">{{ __("Edit") }}</a>
							</li>
						</ul>
				</div>
			</div>
		</div>

		<form id="muaadhform" action="{{route('operator-catalog-item-update',$merchantItem->id)}}" method="POST" enctype="multipart/form-data">
			{{csrf_field()}}
			@include('alerts.operator.form-both')
			<div class="row">
				<div class="col-lg-8">
					<div class="add-catalogItem-content">
						<div class="row">
							<div class="col-lg-12">
								<div class="catalogItem-description">
									<div class="body-area">
										<div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>

										{{-- Merchant Selection --}}
										<div class="row">
											<div class="col-lg-12">
												<div class="left-area">
													<h4 class="heading">{{ __('Merchant') }}*</h4>
												</div>
											</div>
											<div class="col-lg-12">
												<select name="merchant_id" required="">
													<option value="">{{ __('Select Merchant') }}</option>
													@foreach ($merchants as $merchant)
														<option value="{{ $merchant->id }}"
															{{ $merchantItem->user_id == $merchant->id ? 'selected' : '' }}>
															{{ $merchant->shop_name ?: $merchant->name }} ({{ $merchant->email }})
														</option>
													@endforeach
												</select>
											</div>
										</div>

										{{-- Brand (العلامة التجارية) --}}
										<div class="row">
											<div class="col-lg-12">
												<div class="left-area">
													<h4 class="heading">{{ __('Brand') }} ({{ __('Trademark') }})</h4>
												</div>
											</div>
											<div class="col-lg-12">
												<select name="brand_id" class="form-control">
													<option value="">{{ __('Select Brand') }}</option>
													@foreach (\App\Models\Brand::all() as $brand)
														<option value="{{ $brand->id }}"
															{{ $data->brand_id == $brand->id ? 'selected' : '' }}>
															{{ $brand->name }} {{ $brand->name_ar ? '- ' . $brand->name_ar : '' }}
														</option>
													@endforeach
												</select>
											</div>
										</div>

										{{-- Quality Brand (جودة التصنيع) --}}
										<div class="row">
											<div class="col-lg-12">
												<div class="left-area">
													<h4 class="heading">{{ __('Quality Brand') }} ({{ __('Manufacturing Quality') }})</h4>
												</div>
											</div>
											<div class="col-lg-12">
												<select name="brand_quality_id" class="form-control">
													<option value="">{{ __('Select Quality Brand') }}</option>
													@foreach ($qualityBrands as $qb)
														<option value="{{ $qb->id }}"
															{{ $merchantItem->brand_quality_id == $qb->id ? 'selected' : '' }}>
															{{ $qb->name_en }} {{ $qb->name_ar ? '- ' . $qb->name_ar : '' }} {{ $qb->country ? '(' . $qb->country . ')' : '' }}
														</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="row">
											<div class="col-lg-12">
												<div class="left-area">
														<h4 class="heading">{{ __("CatalogItem Name") }}* </h4>
														<p class="sub-heading">{{ __("(In Any Language)") }}</p>
												</div>
											</div>
											<div class="col-lg-12">
												<input type="text" class="form-control" placeholder="{{ __("Enter CatalogItem Name") }}" name="name" required="" value="{{ $data->name }}">
											</div>
										</div>

										{{-- Label English --}}
										<div class="row">
											<div class="col-lg-12">
												<div class="left-area">
													<h4 class="heading">{{ __('CatalogItem Name (English)') }}</h4>
												</div>
											</div>
											<div class="col-lg-12">
												<input type="text" class="form-control"
													placeholder="{{ __('Enter CatalogItem Name in English') }}"
													name="label_en" value="{{ $data->label_en }}">
											</div>
										</div>

										{{-- Label Arabic --}}
										<div class="row">
											<div class="col-lg-12">
												<div class="left-area">
													<h4 class="heading">{{ __('CatalogItem Name (Arabic)') }}</h4>
												</div>
											</div>
											<div class="col-lg-12">
												<input type="text" class="form-control" dir="rtl"
													placeholder="{{ __('Enter CatalogItem Name in Arabic') }}"
													name="label_ar" value="{{ $data->label_ar }}">
											</div>
										</div>


										{{-- Old category system removed - Categories are now linked via parts tables (TreeCategories) --}}

										<div class="row">
											<div class="col-lg-12">
												<div class="left-area">
														<h4 class="heading">{{ __("Select Upload Type") }}*</h4>
												</div>
											</div>
											<div class="col-lg-12">
													<select id="type_check" name="type_check">
													  <option value="1" {{ $data->file != null ? 'selected':'' }}>{{ __("Upload By File") }}</option>
													  <option value="2" {{ $data->link != null ? 'selected':'' }}>{{ __("Upload By Link") }}</option>
													</select>
											</div>
										</div>

										<div class="row file {{ $data->file != null ? '':'hidden' }}">
											<div class="col-lg-12">
												<div class="left-area">
														<h4 class="heading">{{ __("Select File") }}*</h4>
												</div>
											</div>
											<div class="col-lg-12">
													<input type="file" name="file">
											</div>
										</div>

										<div class="row link {{ $data->link != null ? '':'hidden' }}">
											<div class="col-lg-12">
												<div class="left-area">
														<h4 class="heading">{{ __("Link") }}*</h4>
												</div>
											</div>
											<div class="col-lg-12">
													<textarea class="form-control" rows="4" name="link" placeholder="{{ __("Link") }}" {{ $data->link != null ? 'required':'' }}>{{ $data->link }}</textarea> 
											</div>
										</div>


									<div class="row">
										<div class="col-lg-12">
											<div class="left-area">
												<h4 class="heading">
													{{ __('CatalogItem Description') }}*
												</h4>
											</div>
										</div>
										<div class="col-lg-12">
											<div class="text-editor">
												<textarea name="details" class="nic-edit">{{$data->details}}</textarea>
											</div>
										</div>
									</div>

									<div class="row">
										<div class="col-lg-12">
											<div class="left-area">
												<h4 class="heading">
														{{ __('CatalogItem Buy/Return Policy') }}*
												</h4>
											</div>
										</div>
										<div class="col-lg-12">
											<div class="text-editor">
												<textarea name="policy" class="nic-edit">{{$data->policy}}</textarea>
											</div>
										</div>
									</div>

									<div class="row">
										<div class="col-lg-12">
											<div class="checkbox-wrapper">
												<input type="checkbox" name="seo_check" value="1" class="checkclick" id="allowProductSEO" {{ ($data->meta_tag != null || strip_tags($data->meta_description) != null) ? 'checked':'' }}>
												<label for="allowProductSEO">{{ __('Allow CatalogItem SEO') }}</label>
											  </div>
										</div>
									</div>

									<div class="{{ ($data->meta_tag == null && strip_tags($data->meta_description) == null) ? "showbox":"" }}">
										<div class="row">
										  <div class="col-lg-12">
											<div class="left-area">
												<h4 class="heading">{{ __('Meta Tags') }} *</h4>
											</div>
										  </div>
										  <div class="col-lg-12">
											<ul id="metatags" class="myTags">
												@if(!empty($data->meta_tag))
												  @foreach ($data->meta_tag as $element)
													<li>{{  $element }}</li>
												  @endforeach
											  @endif
											</ul>
										  </div>
										</div>

										<div class="row">
										  <div class="col-lg-12">
											<div class="left-area">
											  <h4 class="heading">
												  {{ __('Meta Description') }} *
											  </h4>
											</div>
										  </div>
										  <div class="col-lg-12">
											<div class="text-editor">
											  <textarea name="meta_description" class="form-control" placeholder="{{ __('Details') }}">{{ $data->meta_description }}</textarea>
											</div>
										  </div>
										</div>
									  </div>

								
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
				<div class="col-lg-4">
					<div class="add-catalogItem-content">
						<div class="row">
							<div class="col-lg-12">
								<div class="catalogItem-description">
									<div class="body-area">

										<div class="row">
											<div class="col-lg-12">
											  <div class="left-area">
												  <h4 class="heading">{{ __('Feature Image') }} *</h4>
											  </div>
											</div>
											<div class="col-lg-12">
												<div class="panel panel-body">
													<div class="span4 cropme text-center" id="landscape" class="m-upload-zone">
														<a href="javascript:;" id="crop-image" class="d-inline-block btn btn-primary">
															<i class="icofont-upload-alt"></i> {{ __('Upload Image Here') }}
														</a>
													</div>
												</div>
											</div>
										  </div>

										  <input type="hidden" id="feature_photo" name="photo" value="{{ $data->photo }}" accept="image/*">
											<div class="row">
												<div class="col-lg-12">
													<div class="left-area">
														<h4 class="heading">
															{{ __('CatalogItem Gallery Images') }} *
														</h4>
													</div>
												</div>
												<div class="col-lg-12">
													<a href="javascript" class="set-gallery"  data-bs-toggle="modal" data-bs-target="#setgallery">
														<input type="hidden" value="{{$data->id}}">
															<i class="icofont-plus"></i> {{ __('Set Gallery') }}
													</a>
												</div>
											</div>

											<div class="row">
												<div class="col-lg-12">
													<div class="left-area">
														<h4 class="heading">
															{{ __('CatalogItem Current Price') }}*
														</h4>
														<p class="sub-heading">
															({{ __('In') }} {{$sign->name}})
														</p>
													</div>
												</div>
												<div class="col-lg-12">
													<input name="price" type="number" class="form-control" placeholder="e.g 20" step="0.1" min="0" value="{{round($data->price * $sign->value , 2)}}" required="">
												</div>
											</div>

											<div class="row">
												<div class="col-lg-12">
													<div class="left-area">
															<h4 class="heading">{{ __('CatalogItem Discount Price') }}*</h4>
															<p class="sub-heading">{{ __('(Optional)') }}</p>
													</div>
												</div>
												<div class="col-lg-12">
													<input name="previous_price" step="0.1" type="number" class="form-control" placeholder="e.g 20" value="{{round($data->previous_price * $sign->value , 2)}}" min="0">
												</div>
											</div>

											<div class="row">
												<div class="col-lg-12">
													<div class="left-area">
															<h4 class="heading">{{ __('Youtube Video URL') }}*</h4>
															<p class="sub-heading">{{ __('(Optional)') }}</p>
													</div>
												</div>
												<div class="col-lg-12">
													<input  name="youtube" type="text" class="form-control" placeholder="Enter Youtube Video URL" value="{{$data->youtube}}">
											</div>
									</div>

									<div class="row">
										<div class="col-lg-12">
											<div class="left-area">

											</div>
										</div>
										<div class="col-lg-12">
											<div class="featured-keyword-area">
												<div class="left-area">
													<h4 class="title">{{ __('Feature Tags') }}</h4>
												</div>

												<div class="feature-tag-top-filds" id="feature-section">
													@if(!empty($data->features))

														 @foreach($data->features as $key => $data1)

													<div class="feature-area">
														<span class="remove feature-remove"><i class="fas fa-times"></i></span>
														<div class="row">
															<div class="col-lg-6">
															<input type="text" name="features[]" class="form-control" placeholder="{{ __('Enter Your Keyword') }}" value="{{ $data->features[$key] }}">
															</div>

															<div class="col-lg-6">
																<div class="input-group colorpicker-component cp">
																  <input type="text" name="colors[]" value="{{ $data->colors[$key] }}" class="form-control cp"/>
																  <span class="input-group-module"><i></i></span>
																</div>
															</div>
														</div>
													</div>

														@endforeach
													@else

													<div class="feature-area">
														<span class="remove feature-remove"><i class="fas fa-times"></i></span>
														<div class="row">
															<div class="col-lg-6">
															<input type="text" name="features[]" class="form-control" placeholder="{{ __('Enter Your Keyword') }}">
															</div>

															<div class="col-lg-6">
																<div class="input-group colorpicker-component cp">
																  <input type="text" name="colors[]" value="#000000" class="form-control cp"/>
																  <span class="input-group-module"><i></i></span>
																</div>
															</div>
														</div>
													</div>

													@endif
												</div>

												<a href="javascript:;" id="feature-btn" class="add-fild-btn"><i class="icofont-plus"></i> {{ __('Add More Field') }}</a>
											</div>
										</div>
									</div>

									<div class="row">
										<div class="col-lg-12">
										  <div class="left-area">
											  <h4 class="heading">{{ __('Tags') }} *</h4>
										  </div>
										</div>
										<div class="col-lg-12">
										  <ul id="tags" class="myTags">
											  @if(!empty($data->tags))
												  @foreach ($data->tags as $element)
													<li>{{  $element }}</li>
												  @endforeach
											  @endif
										  </ul>
										</div>
									  </div>

									  <div class="row text-center">
										<div class="col-6 offset-3">
											<button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
										</div>
									</div>

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>

		<div class="modal fade" id="setgallery" tabindex="-1" role="dialog" aria-labelledby="setgallery" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered  modal-lg" role="document">
				<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalCenterTitle">{{ __("Image Gallery") }}</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					
					</button>
				</div>
				<div class="modal-body">
					<div class="top-area">
						<div class="row">
							<div class="col-sm-6 text-right">
								<div class="upload-img-btn">
									<form  method="POST" enctype="multipart/form-data" id="form-gallery">
										@csrf
									<input type="hidden" id="pid" name="catalog_item_id" value="">
									<input type="file" name="gallery[]" class="hidden" id="uploadgallery" accept="image/*" multiple>
											<label for="image-upload" id="prod_gallery"><i class="icofont-upload-alt"></i>{{ __("Upload File") }}</label>
									</form>
								</div>
							</div>
							<div class="col-sm-6">
								<a href="javascript:;" class="upload-done" data-bs-dismiss="modal"> <i class="fas fa-check"></i> {{ __("Done") }}</a>
							</div>
							<div class="col-sm-12 text-center">( <small>{{ __("You can upload multiple Images.") }}</small> )</div>
						</div>
					</div>
					<div class="gallery-images">
						<div class="selected-image">
							<div class="row">


							</div>
						</div>
					</div>
				</div>
				</div>
			</div>
		</div>

@endsection

@section('scripts')

<script type="text/javascript">
	
    $(function($) {
		"use strict";

    $(document).on("click", ".set-gallery" , function(){
        var pid = $(this).find('input[type=hidden]').val();
        $('#pid').val(pid);
        $('.selected-image .row').html('');
            $.ajax({
                    type: "GET",
                    url:"{{ route('operator-gallery-show') }}",
                    data:{id:pid},
                    success:function(data){
                      if(data[0] == 0)
                      {
	                    $('.selected-image .row').addClass('justify-content-center');
	      				$('.selected-image .row').html('<h3>{{ __("No Images Found.") }}</h3>');
     				  }
                      else {
	                    $('.selected-image .row').removeClass('justify-content-center');
	      				$('.selected-image .row h3').remove();      
                          var arr = $.map(data[1], function(el) {
                          return el });

                          for(var k in arr)
                          {
        				$('.selected-image .row').append('<div class="col-sm-6">'+
                                        '<div class="img gallery-img">'+
                                            '<span class="remove-img"><i class="fas fa-times"></i>'+
                                            '<input type="hidden" value="'+arr[k]['id']+'">'+
                                            '</span>'+
                                            '<a href="'+'{{asset('assets/images/merchant-photos').'/'}}'+arr[k]['photo']+'" target="_blank">'+
                                            '<img src="'+'{{asset('assets/images/merchant-photos').'/'}}'+arr[k]['photo']+'" alt="gallery image">'+
                                            '</a>'+
                                        '</div>'+
                                  	'</div>');
                          }                         
                       }
 
                    }
                  });
      });


  $(document).on('click', '.remove-img' ,function() {
    var id = $(this).find('input[type=hidden]').val();
    $(this).parent().parent().remove();
	    $.ajax({
	        type: "GET",
	        url:"{{ route('operator-gallery-delete') }}",
	        data:{id:id}
	    });
  });

  $(document).on('click', '#prod_gallery' ,function() {
    $('#uploadgallery').click();
  });
                                        
                                
  $("#uploadgallery").change(function(){
    $("#form-gallery").submit();  
  });

  $(document).on('submit', '#form-gallery' ,function() {
		  $.ajax({
		   url:"{{ route('operator-gallery-store') }}",
		   method:"POST",
		   data:new FormData(this),
		   dataType:'JSON',
		   contentType: false,
		   cache: false,
		   processData: false,
		   success:function(data)
		   {
		    if(data != 0)
		    {
	                    $('.selected-image .row').removeClass('justify-content-center');
	      				$('.selected-image .row h3').remove();   
		        var arr = $.map(data, function(el) {
		        return el });
		        for(var k in arr)
		           {
        				$('.selected-image .row').append('<div class="col-sm-6">'+
                                        '<div class="img gallery-img">'+
                                            '<span class="remove-img"><i class="fas fa-times"></i>'+
                                            '<input type="hidden" value="'+arr[k]['id']+'">'+
                                            '</span>'+
                                            '<a href="'+'{{asset('assets/images/merchant-photos').'/'}}'+arr[k]['photo']+'" target="_blank">'+
                                            '<img src="'+'{{asset('assets/images/merchant-photos').'/'}}'+arr[k]['photo']+'" alt="gallery image">'+
                                            '</a>'+
                                        '</div>'+
                                  	'</div>');
		            }          
		    }
		                     
		                       }

		  });
		  return false;
 }); 


})(jQuery);

</script>

<script src="{{asset('assets/admin/js/jquery.Jcrop.js')}}"></script>
<script src="{{asset('assets/admin/js/jquery.SimpleCropper.js')}}"></script>
<script src="{{asset('assets/admin/js/select2.js')}}"></script>
<script type="text/javascript">
	    (function($) {
		"use strict";
$('.cropme').simpleCropper();

$(document).ready(function() {
    $('.select2').select2({
		placeholder: "Select Catalog Items",
		maximumSelectionLength: 4,
	});
});

})(jQuery);

</script>


  <script type="text/javascript">

(function($) {
		"use strict";

  $(document).ready(function() {

    let html = `<img src="{{ empty($data->photo) ? asset('assets/images/noimage.png') : (filter_var($data->photo, FILTER_VALIDATE_URL) ? $data->photo : ($data->photo ? \Illuminate\Support\Facades\Storage::url($data->photo) : asset('assets/images/noimage.png'))) }}" alt="">`;
    $(".span4.cropme").html(html);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

  });


  $('.ok').on('click', function () {

 setTimeout(
    function() {


  	var img = $('#feature_photo').val();

      $.ajax({
        url: "{{route('operator-catalog-item-upload-update',$data->id)}}",
        type: "POST",
        data: {"image":img},
        success: function (data) {
          if (data.status) {
            $('#feature_photo').val(data.file_name);
          }
          if ((data.errors)) {
            for(var error in data.errors)
            {
              $.notify(data.errors[error], "danger");
            }
          }
        }
      });

    }, 1000);



    });

})(jQuery);

  </script>

@include('partials.operator.catalogItem.catalogItem-scripts')
@endsection