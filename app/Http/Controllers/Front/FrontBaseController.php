<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Domain\Platform\Models\MonetaryUnit;
use App\Domain\Platform\Models\Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

class FrontBaseController extends Controller
{
    protected $gs;
    protected $ps;
    protected $curr;
    protected $language;

    public function __construct()
    {
        //$this->auth_guests();
        // Set Global Platform Settings (via PlatformSettingsService)

        $this->gs = platformSettings();
//
//        // Set Global PageSettings
//
        $this->ps = DB::table('frontend_settings')->first();
        

        $this->middleware(function ($request, $next) {


            if (Session::has('language')) {
                $this->language = Language::find(Session::get('language'));
            } else {
                $this->language = Language::where('is_default', '=', 1)->first();
            }

            // Use centralized MonetaryUnitService (SINGLE SOURCE OF TRUTH)
            $this->curr = monetaryUnit()->getCurrent();
         
            view()->share('langg', $this->language);
            view()->share('gs', $this->gs);
            view()->share('ps', $this->ps);
            view()->share('curr', $this->curr);

            if ($this->language) {
                App::setlocale($this->language->name);
            } else {
                App::setlocale('en');
            }


            // Set Popup

            if (!Session::has('popup')) {
                view()->share('visited', 1);
            }
            Session::put('popup', 1);


            return $next($request);
        });
    }

    protected function code_image()
    {
        $actual_path = str_replace('project', '', base_path());
        $image = imagecreatetruecolor(200, 50);
        $background_color = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, 200, 50, $background_color);

        $pixel = imagecolorallocate($image, 0, 0, 255);
        for ($i = 0; $i < 500; $i++) {
            imagesetpixel($image, rand() % 200, rand() % 50, $pixel);
        }

        $font = $actual_path . 'assets/front/fonts/NotoSans-Bold.ttf';

        $allowed_letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $length = strlen($allowed_letters);
        $letter = $allowed_letters[rand(0, $length - 1)];
        $word = '';
        //$text_color = imagecolorallocate($image, 8, 186, 239);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $cap_length = 6; // No. of character in image
        for ($i = 0; $i < $cap_length; $i++) {
            $letter = $allowed_letters[rand(0, $length - 1)];
            imagettftext($image, 25, 1, 35 + ($i * 25), 35, $text_color, $font, $letter);
            $word .= $letter;
        }
        $pixels = imagecolorallocate($image, 8, 186, 239);
        for ($i = 0; $i < 500; $i++) {
            imagesetpixel($image, rand() % 200, rand() % 50, $pixels);
        }
        session(['captcha_string' => $word]);
        imagepng($image, $actual_path . "assets/images/capcha_code.png");
    }

    // -------------------------------- INSTALL SECTION (REMOVED) ----------------------------------------

    // Legacy activation logic removed - MUAADH EPC is self-contained

    public function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    // -------------------------------- INSTALL SECTION  ENDS----------------------------------------

}
