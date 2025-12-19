<?php

namespace App\Http\Controllers\Vendor;

use App\{
    Models\Gallery,
    Models\Product
};
use Illuminate\Http\Request;
use Image;
use Illuminate\Support\Str;

class GalleryController extends VendorBaseController
{
    /**
     * Show galleries for a product (filtered by current vendor)
     */
    public function show()
    {
        $data[0] = 0;
        $id = $_GET['id'];
        $prod = Product::findOrFail($id);

        // Filter galleries by current vendor's user_id
        $galleries = Gallery::where('product_id', $id)
            ->where('user_id', $this->user->id)
            ->get();

        if ($galleries->count() > 0) {
            $data[0] = 1;
            $data[1] = $galleries;
        }
        return response()->json($data);
    }

    /**
     * Store new gallery images (with vendor user_id)
     */
    public function store(Request $request)
    {
        $data = null;
        $lastid = $request->product_id;

        if ($files = $request->file('gallery')) {
            foreach ($files as $key => $file) {
                $val = $file->getClientOriginalExtension();
                if ($val == 'jpeg' || $val == 'jpg' || $val == 'png' || $val == 'svg' || $val == 'webp') {
                    $gallery = new Gallery;

                    $img = Image::make($file->getRealPath())->resize(800, 800);
                    $thumbnail = time() . Str::random(8) . '.jpg';
                    $img->save(public_path() . '/assets/images/galleries/' . $thumbnail);

                    $gallery['photo'] = $thumbnail;
                    $gallery['product_id'] = $lastid;
                    $gallery['user_id'] = $this->user->id; // Add vendor's user_id
                    $gallery->save();
                    $data[] = $gallery;
                }
            }
        }
        return response()->json($data);
    }

    /**
     * Delete a gallery image (only if owned by current vendor)
     */
    public function destroy()
    {
        $id = $_GET['id'];
        $gal = Gallery::where('id', $id)
            ->where('user_id', $this->user->id) // Ensure vendor owns this gallery
            ->firstOrFail();

        if (file_exists(public_path() . '/assets/images/galleries/' . $gal->photo)) {
            @unlink(public_path() . '/assets/images/galleries/' . $gal->photo);
        }
        $gal->delete();

        return response()->json(['success' => true]);
    }
}
