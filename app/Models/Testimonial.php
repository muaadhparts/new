<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $table = 'testimonials';

    protected $fillable = ['photo', 'name', 'subname', 'details'];

    public $timestamps = false;
}
