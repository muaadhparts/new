<?php

namespace App\Http\Controllers;

use App\Models\Brand;

class BrandController extends Controller
{
    public function index($brand)
    {

     $brand =  Brand::with('catalogs')
         ->where('name',$brand)
         ->first();

//            ->firstOrFail();



        dd($brand);
    }
}
