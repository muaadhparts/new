@extends('layouts.operator')

@section('content')

<div class="content-area">
            <div class="mr-breadcrumb">
              <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">{{ __("Add CatalogItem") }}</h4>
                    <ul class="links">
                      <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __("Dashboard") }}</a>
                      </li>
                      <li>
                        <a href="javascript:;">{{ __("Catalog Items") }} </a>
                      </li>
                      <li>
                        <a href="{{ route('operator-catalog-item-index') }}">{{ __("All Catalog Items") }}</a>
                      </li>
                      <li>
                        <a href="{{ route('operator-catalog-item-types') }}">{{ __("Add CatalogItem") }}</a>
                      </li>
                    </ul>
                </div>
              </div>
            </div>
            <div class="add-catalogItem-content">
              <div class="row">
                <div class="col-lg-12">
                  <div class="catalogItem-description">
                    <div class="heading-area">
                      <h2 class="title">
                          {{ __("Catalog Item Types") }}
                      </h2>
                    </div>
                  </div>
                </div>
              </div>
              <div class="ap-catalogItem-categories">
                <div class="row">
                  <div class="col-lg-4">
                    <a href="{{ route('operator-catalog-item-create','physical') }}">
                    <div class="cat-box box1">
                      <div class="icon">
                        <i class="fas fa-tshirt"></i>
                      </div>
                      <h5 class="title">{{ __("Physical") }} </h5>
                    </div>
                    </a>
                  </div>
                  <div class="col-lg-4">
                    <a href="{{ route('operator-catalog-item-create','digital') }}">
                    <div class="cat-box box2">
                      <div class="icon">
                        <i class="fas fa-camera-retro"></i>
                      </div>
                      <h5 class="title">{{ __("Digital") }} </h5>
                    </div>
                    </a>
                  </div>
                  <div class="col-lg-4">
                    <a href="{{ route('operator-catalog-item-create','license') }}">
                    <div class="cat-box box3">
                      <div class="icon">
                        <i class="fas fa-award"></i>
                      </div>
                      <h5 class="title">{{ __("license") }} </h5>
                    </div>
                    </a>
                  </div>
                </div>

                <div class="row my-4 d-flex justify-content-center">
                  <div class="col-lg-4">
                    <a href="{{ route('operator-catalog-item-create','listing') }}">
                    <div class="cat-box box3">
                      <div class="icon">
                        <i class="fas fa-th-list"></i>
                      </div>
                      <h5 class="title">{{ __("Classified Listing") }} </h5>
                    </div>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

@endsection