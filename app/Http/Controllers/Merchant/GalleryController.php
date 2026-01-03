<?php

namespace App\Http\Controllers\Merchant;

use App\{
    Models\Gallery,
    Models\CatalogItem
};
use Illuminate\Http\Request;
use Image;
use Illuminate\Support\Str;

class GalleryController extends MerchantBaseController
{
    /**
     * Show galleries for a catalogItem (filtered by current merchant)
     */
    public function show()
    {
        $data[0] = 0;
        $id = $_GET['id'];
        $catalogItem = CatalogItem::findOrFail($id);

        // Filter galleries by current merchant's user_id
        $galleries = Gallery::where('catalog_item_id', $id)
            ->where('user_id', $this->user->id)
            ->get();

        if ($galleries->count() > 0) {
            $data[0] = 1;
            $data[1] = $galleries;
        }
        return response()->json($data);
    }

    /**
     * Store new gallery images (with merchant user_id)
     */
    public function store(Request $request)
    {
        $data = null;
        $lastid = $request->catalog_item_id;

        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                $val = $file->getClientOriginalExtension();
                if ($val == 'jpeg' || $val == 'jpg' || $val == 'png' || $val == 'svg' || $val == 'webp') {
                    $gallery = new Gallery;

                    $img = Image::make($file->getRealPath())->resize(800, 800);
                    $thumbnail = time() . Str::random(8) . '.jpg';
                    $img->save(public_path() . '/assets/images/galleries/' . $thumbnail);

                    $gallery['photo'] = $thumbnail;
                    $gallery['catalog_item_id'] = $lastid;
                    $gallery['user_id'] = $this->user->id; // Add merchant's user_id
                    $gallery->save();
                    $data[] = $gallery;
                }
            }
        }
        return response()->json($data);
    }

    /**
     * Delete a gallery image (only if owned by current merchant)
     */
    public function destroy()
    {
        $id = $_GET['id'];
        $gal = Gallery::where('id', $id)
            ->where('user_id', $this->user->id) // Ensure merchant owns this gallery
            ->firstOrFail();

        if (file_exists(public_path() . '/assets/images/galleries/' . $gal->photo)) {
            @unlink(public_path() . '/assets/images/galleries/' . $gal->photo);
        }
        $gal->delete();

        return response()->json(['success' => true]);
    }
}
