<?php

namespace App\Http\Controllers\Admin;

use App\{
    Http\Controllers\Controller,
    Models\Gallery,
    Models\CatalogItem
};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;

class GalleryController extends Controller
{

    public function show()
    {
        $data[0] = 0;
        $id = $_GET['id'];
        $catalogItem = CatalogItem::findOrFail($id);
        if(count($catalogItem->galleries))
        {
            $data[0] = 1;
            $data[1] = $catalogItem->galleries;
        }
        return response()->json($data);              
    }  

    public function store(Request $request)
    { 
        $data = null;
        $lastid = $request->catalog_item_id ?? $request->product_id;
        if ($files = $request->file('gallery')){
            foreach ($files as  $key => $file){
                $val = $file->getClientOriginalExtension();
                if($val == 'jpeg'|| $val == 'jpg'|| $val == 'png'|| $val == 'svg')
                  {
                    $gallery = new Gallery;


        $img = Image::make($file->getRealPath())->resize(800, 800);
        $thumbnail = time().Str::random(8).'.jpg';
        $img->save('assets/images/galleries/'.$thumbnail);

                    $gallery['photo'] = $thumbnail;
                    $gallery['catalog_item_id'] = $lastid;
                    $gallery->save();
                    $data[] = $gallery;                        
                  }
            }
        }
        return response()->json($data);      
    } 

    public function destroy()
    {

        $id = $_GET['id'];
        $gal = Gallery::findOrFail($id);
            if (file_exists('assets/images/galleries/'.$gal->photo)) {
                unlink('assets/images/galleries/'.$gal->photo);
            }
        $gal->delete();
            
    } 

}
