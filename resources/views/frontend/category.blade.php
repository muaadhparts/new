<div class="catalogItems-header d-flex justify-content-between align-items-center py-10 px-20 bg-light md-mt-30">
    <div class="catalogItems-header-left d-flex align-items-center">
       <h6 class="woocommerce-catalogItems-header__name page-name"> <strong> {{ __('Items')  }}</strong>  </h6>
       <div class="woocommerce-result-count"></div> 
    </div>
    <div class="catalogItems-header-right">
       <form class="woocommerce-ordering" method="get">
          <select name="sort" class="orderby short-item" aria-label="Shop purchase" id="sortby">
             <option value="price_asc">{{ __('Lowest Price') }}</option>
             <option value="price_desc">{{ __('Highest Price') }}</option>
             <option value="part_number">{{ __('Part Number') }}</option>
             <option value="name_asc">{{ __('Name A-Z') }}</option>
          </select>
          @if($gs->item_page != null)
          <select id="pageby" name="pageby" class="short-itemby-no">
             @foreach (explode(',',$gs->item_page) as $element)
             <option value="{{ $element }}">{{ $element }}</option>
             @endforeach
          </select>
          @else
          <input type="hidden" id="pageby" name="paged" value="{{ $gs->page_count }}">
          <input type="hidden" name="shop-page-layout" value="left-sidebar">
          @endif
       </form>
       <div class="catalogItems-view">
          <a  class="grid-view check_view" data-shopview="grid-view" href="javascript:;"><i class="flaticon-menu-1 flat-mini"></i></a>
          <a class="list-view check_view" data-shopview="list-view" href="javascript:;"><i class="flaticon-list flat-mini"></i></a>
       </div>
    </div>
 </div>
