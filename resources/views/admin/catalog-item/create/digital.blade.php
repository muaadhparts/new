@extends('layouts.admin')

@section('content')

						<div class="content-area">
							<div class="mr-breadcrumb">
								<div class="row">
									<div class="col-lg-12">
											<h4 class="heading">{{ __("Digital Product") }} <a class="add-btn" href="{{ route('admin-catalog-item-types') }}"><i class="fas fa-arrow-left"></i> {{ __("Back") }}</a></h4>
											<ul class="links">
												<li>
													<a href="{{ route('admin.dashboard') }}">{{ __("Dashboard") }} </a>
												</li>
											<li>
												<a href="javascript:;">{{ __("Products") }} </a>
											</li>
											<li>
												<a href="{{ route('admin-catalog-item-index') }}">{{ __("All Products") }}</a>
											</li>
												<li>
													<a href="{{ route('admin-catalog-item-types') }}">{{ __("Add Product") }}</a>
												</li>
												<li>
													<a href="{{ route('admin-catalog-item-create','digital') }}">{{ __("Digital Product") }}</a>
												</li>
											</ul>
									</div>
								</div>
							</div>

							<form id="muaadhform" action="{{route('admin-catalog-item-store')}}" method="POST" enctype="multipart/form-data">
								{{csrf_field()}}
								@include('alerts.admin.form-both')
								<div class="row">
									<div class="col-lg-8">
										<div class="add-product-content">
											<div class="row">
												<div class="col-lg-12">
													<div class="product-description">
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
																	<select id="merchant_id" name="user_id" required="" class="select2">
																		<option value="">{{ __('Select Merchant') }}</option>
																		@foreach (\App\Models\User::where('is_merchant', 2)->where('ban', 0)->orderBy('shop_name')->get() as $merchant)
																			<option value="{{ $merchant->id }}">
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
																			<option value="{{ $brand->id }}">
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
																		@foreach (\App\Models\QualityBrand::all() as $qb)
																			<option value="{{ $qb->id }}">
																				{{ $qb->name_en }} {{ $qb->name_ar ? '- ' . $qb->name_ar : '' }} {{ $qb->country ? '(' . $qb->country . ')' : '' }}
																			</option>
																		@endforeach
																	</select>
																</div>
															</div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																			<h4 class="heading">{{ __('Product Name') }}* </h4>
																			<p class="sub-heading">{{ __('(In Any Language)') }}</p>
																	</div>
																</div>
																<div class="col-lg-12">
																	<input type="text" class="form-control" placeholder="{{ __('Enter Product Name') }}" name="name" required="">
																</div>
															</div>

															{{-- Label English --}}
															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">{{ __('Product Name (English)') }}</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<input type="text" class="form-control"
																		placeholder="{{ __('Enter Product Name in English') }}"
																		name="label_en">
																</div>
															</div>

															{{-- Label Arabic --}}
															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">{{ __('Product Name (Arabic)') }}</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<input type="text" class="form-control" dir="rtl"
																		placeholder="{{ __('Enter Product Name in Arabic') }}"
																		name="label_ar">
																</div>
															</div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">{{ __('Category') }}*</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<select id="cat" name="category_id" required="">
																		<option value="">{{ __('Select Category') }}</option>
																		@foreach($cats as $cat)
																			<option data-href="{{ route('admin-subcat-load',$cat->id) }}" value="{{ $cat->id }}">{{$cat->name}}</option>
																		@endforeach
																	</select>
																</div>
															</div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">{{ __('Sub Category') }}*</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<select id="subcat" name="subcategory_id" disabled="">
																			<option value="">{{ __('Select Sub Category') }}</option>
																	</select>
																</div>
															</div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">{{ __('Child Category') }}*</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<select id="childcat" name="childcategory_id" disabled="">
																			<option value="">{{ __('Select Child Category') }}</option>
																	</select>
																</div>
															</div>


															<div id="catAttributes"></div>
															<div id="subcatAttributes"></div>
															<div id="childcatAttributes"></div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																			<h4 class="heading">{{ __("Select Upload Type") }}*</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																		<select id="type_check" name="type_check">
																		  <option value="1">{{ __("Upload By File") }}</option>
																		  <option value="2">{{ __("Upload By Link") }}</option>
																		</select>
																</div>
															</div>
			
															<div class="row file">
																<div class="col-lg-12">
																	<div class="left-area">
																			<h4 class="heading">{{ __("Select File") }}*</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																		<input type="file" name="file" required="">
																</div>
															</div>
			
															<div class="row link hidden">
																<div class="col-lg-4">
																	<div class="left-area">
																			<h4 class="heading">{{ __("Link") }}*</h4>
																	</div>
																</div>
																<div class="col-lg-7">
																		<textarea class="form-control" rows="4" name="link" placeholder="{{ __("Link") }}"></textarea> 
																</div>
															</div>

															
														
															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">
																			{{ __('Product Description') }}*
																		</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<div class="text-editor">
																		<textarea class="nic-edit" name="details"></textarea>
																	</div>
																</div>
															</div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">
																			{{ __('Product Buy/Return Policy') }}*
																		</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<div class="text-editor">
																		<textarea class="nic-edit" name="policy"></textarea>
																	</div>
																</div>
															</div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="checkbox-wrapper">
																		<input type="checkbox" name="seo_check" value="1" class="checkclick" id="allowProductSEO" value="1">
																		<label for="allowProductSEO">{{ __('Allow Product SEO') }}</label>
																	</div>
																</div>
															</div>
		
		
		
														<div class="showbox">
															<div class="row">
															  <div class="col-lg-12">
																<div class="left-area">
																	<h4 class="heading">{{ __('Meta Tags') }} *</h4>
																</div>
															  </div>
															  <div class="col-lg-12">
																<ul id="metatags" class="myTags">
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
																  <textarea name="meta_description" class="form-control" placeholder="{{ __('Meta Description') }}"></textarea>
																</div>
															  </div>
															</div>
														  </div>

														  <input type="hidden" name="type" value="Digital">
													
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
									<div class="col-lg-4">
										<div class="add-product-content">
											<div class="row">
												<div class="col-lg-12">
													<div class="product-description">
														<div class="body-area">
															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">{{ __('Feature Image') }} *</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																		<div class="panel panel-body">
																			<div class="span4 cropme text-center" id="landscape"
																				class="m-upload-zone">
																				<a href="javascript:;" id="crop-image" class="btn btn-primary" style="">
																					<i class="icofont-upload-alt"></i> {{ __('Upload Image Here') }}
																				</a>
																			</div>
																		</div>
																</div>
															</div>
															<input type="hidden" id="feature_photo" name="photo" value="">
															<input type="file" name="gallery[]" class="hidden" id="uploadgallery" accept="image/*"
																multiple>
															<div class="row mb-4">
																<div class="col-lg-12 mb-2">
																	<div class="left-area">
																		<h4 class="heading">
																			{{ __('Product Gallery Images') }} *
																		</h4>
																	</div>
																</div>
																<div class="col-lg-12">
																	<a href="#" class="set-gallery" data-bs-toggle="modal" data-bs-target="#setgallery">
																		<i class="icofont-plus"></i> {{ __('Set Gallery') }}
																	</a>
																</div>
															</div>

															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																		<h4 class="heading">
																			{{ __('Product Current Price') }}*
																		</h4>
																		<p class="sub-heading">
																			({{ __('In') }} {{$sign->name}})
																		</p>
																	</div>
																</div>
																<div class="col-lg-12">
																	<input name="price" type="number" class="form-control" placeholder="{{ __('e.g 20') }}" step="0.1" required="" min="0">
																</div>
															</div>
			
															<div class="row">
																<div class="col-lg-12">
																	<div class="left-area">
																			<h4 class="heading">{{ __('Product Discount Price') }}*</h4>
																			<p class="sub-heading">{{ __('(Optional)') }}</p>
																	</div>
																</div>
																<div class="col-lg-12">
																	<input name="previous_price" step="0.1" type="number" class="form-control" placeholder="{{ __('e.g 20') }}" min="0">
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
																	<input  name="youtube" type="text" class="form-control" placeholder="{{ __('Enter Youtube Video URL') }}">
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
																			<div class="feature-area">
																				<span class="remove feature-remove"><i class="fas fa-times"></i></span>
																				<div class="row">
																					<div class="col-lg-6">
																					<input type="text" name="features[]" class="form-control" placeholder="{{ __('Enter Your Keyword') }}">
																					</div>
			
																					<div class="col-lg-6">
																						<div class="input-group colorpicker-component cp">
																						  <input type="text" name="colors[]" value="#000000" class="form-control cp"/>
																						  <span class="input-group-addon"><i></i></span>
																						</div>
																					</div>
																				</div>
																			</div>
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
																  </ul>
																</div>
															  </div>

															  <div class="row text-center">
																<div class="col-6 offset-3">
																	<button class="btn btn-primary" type="submit">{{ __('Create Product') }}</button>
																</div>
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
								<h5 class="modal-title" id="exampleModalCenterTitle">{{__('Image Gallery')}}</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
								
								</button>
							</div>
							<div class="modal-body">
								<div class="top-area">
									<div class="row">
										<div class="col-sm-6 text-right">
											<div class="upload-img-btn">
														<label for="image-upload" id="prod_gallery"><i class="icofont-upload-alt"></i>{{__('Upload File')}}</label>
											</div>
										</div>
										<div class="col-sm-6">
											<a href="javascript:;" class="upload-done" data-bs-dismiss="modal"> <i class="fas fa-check"></i> {{__('Done')}}</a>
										</div>
										<div class="col-sm-12 text-center">( <small>{{__('You can upload multiple Images.')}}</small> )</div>
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

		<script src="{{asset('assets/admin/js/jquery.Jcrop.js')}}"></script>
		<script src="{{asset('assets/admin/js/jquery.SimpleCropper.js')}}"></script>
		<script src="{{asset('assets/admin/js/select2.js')}}"></script>

<script type="text/javascript">
	
    (function($) {
		"use strict";

		$(document).ready(function() {
    $('.select2').select2({
		placeholder: "Select Products",
		maximumSelectionLength: 4,
	});
});

  $(document).on('click', '.remove-img' ,function() {
    var id = $(this).find('input[type=hidden]').val();
    $('#galval'+id).remove();
    $(this).parent().parent().remove();
  });

  $(document).on('click', '#prod_gallery' ,function() {
    $('#uploadgallery').click();
     $('.selected-image .row').html('');
    $('#muaadhform').find('.removegal').val(0);
  });
                                        
                                
  $("#uploadgallery").change(function(){
     var total_file=document.getElementById("uploadgallery").files.length;
     for(var i=0;i<total_file;i++)
     {
      $('.selected-image .row').append('<div class="col-sm-6">'+
                                        '<div class="img gallery-img">'+
                                            '<span class="remove-img"><i class="fas fa-times"></i>'+
                                            '<input type="hidden" value="'+i+'">'+
                                            '</span>'+
                                            '<a href="'+URL.createObjectURL(event.target.files[i])+'" target="_blank">'+
                                            '<img src="'+URL.createObjectURL(event.target.files[i])+'" alt="gallery image">'+
                                            '</a>'+
                                        '</div>'+
                                  '</div> '
                                      );
      $('#muaadhform').append('<input type="hidden" name="galval[]" id="galval'+i+'" class="removegal" value="'+i+'">')
     }

  });

})(jQuery);
</script>

<script type="text/javascript">
    (function($) {
		"use strict";

		$(document).ready(function() {
			$('.cropme').simpleCropper();
		});

	})(jQuery);
</script>


@include('partials.admin.product.product-scripts')
@endsection