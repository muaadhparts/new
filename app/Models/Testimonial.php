<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $table = 'testimonials';

    protected $fillable = ['photo', 'name', 'subtitle', 'details'];

    public $timestamps = false;
}
