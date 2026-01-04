<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturedPromo extends Model
{
    use HasFactory;

    protected $table = 'featured_promos';

    protected $fillable = ['title', 'header', 'photo', 'up_sale', 'url'];
}
