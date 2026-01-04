<?php

namespace App\Http\Controllers\Merchant;

use App\{
    Models\MerchantPhoto,
    Models\CatalogItem
};
use Illuminate\Http\Request;
use Image;
use Illuminate\Support\Str;

class MerchantPhotoController extends MerchantBaseController
{
    /**
     * Show merchant photos for a catalogItem (filtered by current merchant)
     */
    public function show()
    {
        $data[0] = 0;
        $id = $_GET['id'];
        $catalogItem = CatalogItem::findOrFail($id);

        // Filter merchant photos by current merchant's user_id
        $merchantPhotos = MerchantPhoto::where('catalog_item_id', $id)
            ->where('user_id', $this->user->id)
            ->get();

        if ($merchantPhotos->count() > 0) {
            $data[0] = 1;
            $data[1] = $merchantPhotos;
        }
        return response()->json($data);
    }

    /**
     * Store new merchant photos (with merchant user_id)
     */
    public function store(Request $request)
    {
        $data = null;
        $lastid = $request->catalog_item_id;

        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                $val = $file->getClientOriginalExtension();
                if ($val == 'jpeg' || $val == 'jpg' || $val == 'png' || $val == 'svg' || $val == 'webp') {
                    $merchantPhoto = new MerchantPhoto;

                    $img = Image::make($file->getRealPath())->resize(800, 800);
                    $thumbnail = time() . Str::random(8) . '.jpg';
                    $img->save(public_path() . '/assets/images/merchant-photos/' . $thumbnail);

                    $merchantPhoto['photo'] = $thumbnail;
                    $merchantPhoto['catalog_item_id'] = $lastid;
                    $merchantPhoto['user_id'] = $this->user->id; // Add merchant's user_id
                    $merchantPhoto->save();
                    $data[] = $merchantPhoto;
                }
            }
        }
        return response()->json($data);
    }

    /**
     * Delete a merchant photo (only if owned by current merchant)
     */
    public function destroy()
    {
        $id = $_GET['id'];
        $merchantPhoto = MerchantPhoto::where('id', $id)
            ->where('user_id', $this->user->id) // Ensure merchant owns this photo
            ->firstOrFail();

        if (file_exists(public_path() . '/assets/images/merchant-photos/' . $merchantPhoto->photo)) {
            @unlink(public_path() . '/assets/images/merchant-photos/' . $merchantPhoto->photo);
        }
        $merchantPhoto->delete();

        return response()->json(['success' => true]);
    }
}
