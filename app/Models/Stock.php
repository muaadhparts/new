<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'part_number', 'branch_id', 'location', 'qty',
        'sell_price', 'comp_cost', 'cost_price',
    ];
}
