<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{

    protected $fillable = ['product_id', 'merchant_product_id', 'user_id', 'title', 'note'];

    public function user()
    {
    	return $this->belongsTo('App\Models\User')->withDefault(function ($data) {
			foreach($data->getFillable() as $dt){
				$data[$dt] = __('Deleted');
			}
		});
    }

    public function product()
    {
    	return $this->belongsTo('App\Models\Product')->withDefault(function ($data) {
			foreach($data->getFillable() as $dt){
				$data[$dt] = __('Deleted');
			}
		});
    }

    /**
     * السجل التجاري المرتبط بالبلاغ
     */
    public function merchantProduct()
    {
        return $this->belongsTo('App\Models\MerchantProduct')->withDefault();
    }

}
