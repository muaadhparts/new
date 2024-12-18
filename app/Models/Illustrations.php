<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Illustrations extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $guarded = ["id"];


    protected $casts = [
         'illustrationwithcallouts' => 'array',
//        'basicNumberHyperlinkCallouts' => 'array',
//        'sectionHyplerlinkCallouts' => 'array',
//        'hardwareCallouts' => 'array',
       
        
    ];


}
