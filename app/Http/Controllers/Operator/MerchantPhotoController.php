<?php

namespace App\Http\Controllers\Operator;

use App\{
    Http\Controllers\Controller,
    Domain\Merchant\Models\MerchantPhoto,
    Domain\Catalog\Models\CatalogItem
};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;

class MerchantPhotoController extends Controller
{

    public function show()
    {
        $data[0] = 0;
        $id = $_GET['id'];
        $catalogItem = CatalogItem::findOrFail($id);
        if(count($catalogItem->merchantPhotos))
        {
            $data[0] = 1;
            $data[1] = $catalogItem->merchantPhotos;
        }
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = null;
        $lastid = $request->catalog_item_id;
        if ($files = $request->file('gallery')){
            foreach ($files as  $key => $file){
                $val = $file->getClientOriginalExtension();
                if($val == 'jpeg'|| $val == 'jpg'|| $val == 'png'|| $val == 'svg')
                  {
                    $merchantPhoto = new MerchantPhoto;


        $img = Image::make($file->getRealPath())->resize(800, 800);
        $thumbnail = time().Str::random(8).'.jpg';
        $img->save('assets/images/merchant-photos/'.$thumbnail);

                    $merchantPhoto['photo'] = $thumbnail;
                    $merchantPhoto['catalog_item_id'] = $lastid;
                    $merchantPhoto->save();
                    $data[] = $merchantPhoto;
                  }
            }
        }
        return response()->json($data);
    }

    public function destroy()
    {

        $id = $_GET['id'];
        $merchantPhoto = MerchantPhoto::findOrFail($id);
            if (file_exists('assets/images/merchant-photos/'.$merchantPhoto->photo)) {
                unlink('assets/images/merchant-photos/'.$merchantPhoto->photo);
            }
        $merchantPhoto->delete();

    }

}
