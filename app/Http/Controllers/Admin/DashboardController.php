<?php

namespace App\Http\Controllers\Admin;

use App\Models\Blog;
use App\Models\Counter;
use App\Models\License;
use App\Models\Purchase;
use App\Models\CatalogItem;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Validator;

class DashboardController extends AdminBaseController
{

    public function index()
    {

        $data['pending'] = Purchase::where('status', '=', 'pending')->get();
        $data['processing'] = Purchase::where('status', '=', 'processing')->get();
        $data['completed'] = Purchase::where('status', '=', 'completed')->get();
        $data['days'] = "";
        $data['sales'] = "";
        for ($i = 0; $i < 30; $i++) {
            $data['days'] .= "'" . date("d M", strtotime('-' . $i . ' days')) . "',";

            $data['sales'] .= "'" . Purchase::where('status', '=', 'completed')->whereDate('created_at', '=', date("Y-m-d", strtotime('-' . $i . ' days')))->count() . "',";
        }
        $data['users'] = User::count();
        $data['products'] = CatalogItem::count();
        $data['blogs'] = Blog::count();

        // جلب أحدث العناصر من merchant_items (العناصر النشطة فقط)
        $data['pproducts'] = \App\Models\MerchantItem::with(['catalogItem.brand', 'catalogItem.category', 'catalogItem.subcategory', 'catalogItem.childcategory', 'user', 'qualityBrand'])
            ->where('status', 1)
            ->whereHas('catalogItem', function($q) {
                $q->where('status', 1);
            })
            ->latest('id')
            ->take(5)
            ->get();

        $data['rpurchases'] = Purchase::latest('id')->take(5)->get();

        // جلب العناصر الشائعة من merchant_items (حسب views من catalog_items)
        $data['poproducts'] = \App\Models\MerchantItem::with(['catalogItem.brand', 'catalogItem.category', 'catalogItem.subcategory', 'catalogItem.childcategory', 'user', 'qualityBrand'])
            ->where('status', 1)
            ->whereHas('catalogItem', function($q) {
                $q->where('status', 1)->orderBy('views', 'desc');
            })
            ->take(5)
            ->get()
            ->sortByDesc(function($mp) {
                return $mp->catalogItem->views ?? 0;
            });

        $data['rusers'] = User::latest('id')->take(5)->get();
        $data['referrals'] = Counter::where('type', 'referral')->latest('total_count')->take(5)->get();
        $data['browsers'] = Counter::where('type', 'browser')->latest('total_count')->take(5)->get();

        // التحقق من حالة التفعيل
        $data['activation_notify'] = "";
        $license = License::getActiveLicense();
        if (!$license) {
            $data['activation_notify'] = "<i class='icofont-warning-alt icofont-4x'></i><br>النظام غير مفعل.<br><a href='" . route('admin-license-index') . "' class='btn btn-success'>تفعيل الآن</a>";
        } elseif ($license->expires_at && $license->expires_at->diffInDays(now()) <= 10) {
            $data['activation_notify'] = "<i class='icofont-warning-alt icofont-4x'></i><br>تنبيه: الترخيص سينتهي في " . $license->expires_at->format('Y-m-d') . "<br><a href='" . route('admin-license-index') . "' class='btn btn-warning'>تجديد الترخيص</a>";
        }

        return view('admin.dashboard', $data);
    }

    public function profile()
    {
        $data = Auth::guard('admin')->user();
        return view('admin.profile', compact('data'));
    }

    public function profileupdate(Request $request)
    {
        //--- Validation Section

        $rules =
            [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:admins,email,' . Auth::guard('admin')->user()->id,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends
        $input = $request->all();
        $data = Auth::guard('admin')->user();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/admins/', $name);
            if ($data->photo != null) {
                if (file_exists(public_path() . '/assets/images/admins/' . $data->photo)) {
                    unlink(public_path() . '/assets/images/admins/' . $data->photo);
                }
            }
            $input['photo'] = $name;
        }
        $data->update($input);
        $msg = __('Successfully updated your profile');
        return response()->json($msg);
    }

    public function passwordreset()
    {
        $data = Auth::guard('admin')->user();
        return view('admin.password', compact('data'));
    }

    public function changepass(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if ($request->cpass) {
            if (Hash::check($request->cpass, $admin->password)) {
                if ($request->newpass == $request->renewpass) {
                    $input['password'] = Hash::make($request->newpass);
                } else {
                    return response()->json(array('errors' => [0 => __('Confirm password does not match.')]));
                }
            } else {
                return response()->json(array('errors' => [0 => __('Current password Does not match.')]));
            }
        }
        $admin->update($input);
        $msg = __('Successfully changed your password');
        return response()->json($msg);
    }

    public function generate_bkup()
    {
        $bkuplink = "";
        $chk = file_get_contents('backup.txt');
        if ($chk != "") {
            $bkuplink = url($chk);
        }
        return view('admin.movetoserver', compact('bkuplink', 'chk'));
    }

    public function clear_bkup()
    {
        $destination = public_path() . '/install';
        $bkuplink = "";
        $chk = file_get_contents('backup.txt');
        if ($chk != "") {
            $path = str_replace('project', '', base_path($chk));
            @unlink($path);
        }

        if (is_dir($destination)) {
            $this->deleteDir($destination);
        }
        $handle = fopen('backup.txt', 'w+');
        fwrite($handle, "");
        fclose($handle);
        return redirect()->back()->with('success', 'Backup file Deleted Successfully!');
    }

    public function movescript()
    {
        ini_set('max_execution_time', 3000);

        $destination = public_path() . '/install';
        $chk = file_get_contents('backup.txt');

        if ($chk != "") {
            $base_path = str_replace('project', '', base_path());
            @unlink(public_path($base_path));
        }

        if (is_dir($destination)) {
            $this->deleteDir($destination);
        }

        $src = base_path() . '/vendor/update';

        $this->recurse_copy($src, $destination);
        $files = public_path();
        $bkupname = 'MUAADH-EPC-Backup-' . date('Y-m-d') . '.zip';

        $zip = new \ZipArchive();
        $zip->open($bkupname, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path()),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(base_path()) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $handle = fopen('backup.txt', 'w+');
        fwrite($handle, $bkupname);
        fclose($handle);

        if (is_dir($destination)) {
            $this->deleteDir($destination);
        }
        return response()->json(['status' => 'success', 'backupfile' => url($bkupname), 'filename' => $bkupname], 200);
    }

    public function recurse_copy($src, $dst)
    {

        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

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
}
