<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Childcategory extends Model
{
    protected $fillable = ['subcategory_id', 'name', 'name_ar', 'slug'];
    public $timestamps = false;

    public function subcategory()
    {
    	return $this->belongsTo('App\Models\Subcategory')->withDefault();
    }

    /**
     * @deprecated Use catalogItems() instead
     */
    public function products()
    {
        return $this->hasMany('App\Models\CatalogItem');
    }

    /**
     * Catalog items for this childcategory
     */
    public function catalogItems()
    {
        return $this->hasMany('App\Models\CatalogItem');
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
