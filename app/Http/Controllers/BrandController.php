<?php

namespace App\Http\Controllers;

use App\Models\Partner;

class BrandController extends Controller
{
    public function index($partner)
    {

     $partner =  Partner::with('catalogs')
         ->where('name',$partner)
         ->first();

//            ->firstOrFail();



        dd($partner);
    }
}
