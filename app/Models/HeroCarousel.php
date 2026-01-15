<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroCarousel extends Model
{
    protected $table = 'hero_carousels';

    protected $fillable = ['user_id','subname_text','subname_size','subname_color','subname_anime','name_text','name_size','name_color','name_anime','details_text','details_size','details_color','details_anime','photo','position','link'];
    public $timestamps = false;


}
