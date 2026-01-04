<div class="left">
    <h4 class="rate">{{ App\Models\CatalogTestimonial::averageScore($catalogItem->id) }}</h4>
    <ul class="stars">
        <div class="review-stars">
            <div class="empty-stars big"></div>
            <div class="full-stars big" style="width:{{ App\Models\CatalogTestimonial::scorePercentage($catalogItem->id) }}%"></div>
        </div>
    </ul>
    <p class="total-review">{{ App\Models\CatalogTestimonial::reviewCount($catalogItem->id) }} {{ __('Review') }}</p>
  </div>
  <div class="right">
    <ul class="reating-poll">
      <li>
        <div class="star-list">
          <ul class="star-list stars">
              <div class="review-stars">
                  <div class="empty-stars"></div>
                  <div class="full-stars" style="width:{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,5) }}%"></div>
              </div>
          </ul>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width: {{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,5) }}%" aria-valuenow="{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,5) }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <span class="count">{{ App\Models\CatalogTestimonial::customReviewPercentage($catalogItem->id,5) }}</span>
      </li>
      <li>
        <div class="star-list">
          <ul class="star-list stars">
              <div class="review-stars">
                  <div class="empty-stars"></div>
                  <div class="full-stars" style="width:{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,4) }}%"></div>
              </div>
          </ul>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width: {{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,4) }}%" aria-valuenow="{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,4) }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <span class="count">{{ App\Models\CatalogTestimonial::customReviewPercentage($catalogItem->id,4) }}</span>
      </li>
      <li>
        <div class="star-list">
          <ul class="star-list stars">
              <div class="review-stars">
                  <div class="empty-stars"></div>
                  <div class="full-stars" style="width:{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,3) }}%"></div>
              </div>
          </ul>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width: {{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,3) }}%" aria-valuenow="{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,3) }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <span class="count">{{ App\Models\CatalogTestimonial::customReviewPercentage($catalogItem->id,3) }}</span>
      </li>
      <li>
        <div class="star-list">
          <ul class="star-list stars">
              <div class="review-stars">
                  <div class="empty-stars"></div>
                  <div class="full-stars" style="width:{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,2) }}%"></div>
              </div>
          </ul>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width: {{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,2) }}%" aria-valuenow="{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,2) }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <span class="count">{{ App\Models\CatalogTestimonial::customReviewPercentage($catalogItem->id,2) }}</span>
      </li>
      <li>
        <div class="star-list">
          <ul class="star-list stars">
              <div class="review-stars">
                  <div class="empty-stars"></div>
                  <div class="full-stars" style="width:{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,1) }}%"></div>
              </div>
          </ul>
        </div>
        <div class="progress">
          <div class="progress-bar" role="progressbar" style="width: {{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,1) }}%" aria-valuenow="{{ App\Models\CatalogTestimonial::customScorePercentage($catalogItem->id,1) }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <span class="count">{{ App\Models\CatalogTestimonial::customReviewPercentage($catalogItem->id,1) }}</span>
      </li>
    </ul>
  </div>
