<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogReview extends Model
{
    protected $table = 'catalog_reviews';

    protected $fillable = ['user_id', 'catalog_item_id', 'merchant_item_id', 'review', 'rating', 'review_date'];
    public $timestamps = false;

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem', 'catalog_item_id')->withDefault();
    }

    public function merchantItem()
    {
        return $this->belongsTo('App\Models\MerchantItem', 'merchant_item_id')->withDefault();
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public static function averageScore($catalogItemId){
        $stars = self::where('catalog_item_id', $catalogItemId)->avg('rating');
        return number_format($stars,1);
    }

    public static function scorePercentage($catalogItemId){
        $stars = self::where('catalog_item_id', $catalogItemId)->avg('rating');
        $percentage = number_format((float)$stars, 1, '.', '') * 20;
        return $percentage;
    }

    public static function reviewCount($catalogItemId){
        $count = self::where('catalog_item_id', $catalogItemId)->count();
        return number_format($count);
    }

    public static function customScorePercentage($catalogItemId, $score){
        $totalCount = self::where('catalog_item_id', $catalogItemId)->count();
        if($totalCount == 0){
            return 0;
        }
        $scoreCount = self::where('catalog_item_id', $catalogItemId)->where('rating', $score)->count();
        $avg = ($scoreCount / $totalCount) * 100;
        return $avg;
    }

    public static function customReviewPercentage($catalogItemId, $score){
        $totalCount = self::where('catalog_item_id', $catalogItemId)->count();
        if($totalCount == 0){
            return 0;
        }
        $scoreCount = self::where('catalog_item_id', $catalogItemId)->where('rating', $score)->count();
        $avg = ($scoreCount / $totalCount) * 100;
        return round($avg, 2).'%';
    }

    public static function merchantScorePercentage($user_id){
        $stars = self::whereHas('merchantItem', function($query) use ($user_id) {
            $query->where('user_id', '=', $user_id);
        })->avg('rating');
        $percentage = number_format((float)$stars, 1, '.', '') * 20;
        return $percentage;
    }

    public static function merchantReviewCount($user_id){
        $count = self::whereHas('merchantItem', function($query) use ($user_id) {
            $query->where('user_id', '=', $user_id);
        })->count();
        return $count;
    }

}
