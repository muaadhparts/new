<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name','slug','photo','image','is_featured'];
    public $timestamps = false;

    public function subs()
    {
    	return $this->hasMany('App\Models\Subcategory')->where('status','=',1);
//            ->take(6);
    }
    

    public function products()
    {
        return $this->hasMany('App\Models\Product');
    }

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = str_replace(' ', '-', $value);
    }

    public function attributes() {
        return $this->morphMany('App\Models\Attribute', 'attributable');
    }

    public function getLocalizedNameAttribute(): string
    {
        $isAr = app()->getLocale() === 'ar';
        $ar   = trim((string)($this->name_ar ?? ''));
        $en   = trim((string)($this->name ?? ''));
        return $isAr && $ar !== '' ? $ar : $en;
    }
}
