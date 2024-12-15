<?php

function wishlistCheck($product_id)
{
    $wishlist = \App\Models\Wishlist::where('product_id', $product_id)->where('user_id', auth()->id())->first();
    if ($wishlist) {
        return true;
    } else {
        return false;
    }

}

function addon($name)
{

    if ($name == "otp") {
        $otp = file_exists(base_path("/vendor/markury/src/Adapter/addon/otp.txt"));
        if ($otp) {
            $data = file_get_contents(base_path("/vendor/markury/src/Adapter/addon/otp.txt"));

            if ($data) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    return false;
}
