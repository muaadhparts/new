<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleType extends Model
{
    protected $table = 'article_types';

    protected $fillable = ['name', 'slug'];

    public $timestamps = false;

    public function publications()
    {
        return $this->hasMany('App\Models\Publication', 'category_id');
    }

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = str_replace(' ', '-', $value);
    }
}
