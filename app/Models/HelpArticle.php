<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpArticle extends Model
{
    protected $table = 'help_articles';

    protected $fillable = ['title', 'details'];

    public $timestamps = false;
}
