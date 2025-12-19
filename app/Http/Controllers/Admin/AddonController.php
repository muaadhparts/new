<?php

namespace App\Http\Controllers\Admin;

use App\Models\Addon;
use App\Models\License;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class AddonController extends AdminBaseController
{

    //*** JSON Request
    public function datatables()
    {
        $datas = Addon::get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('created_at', function (Addon $data) {
                return date('Y-m-d', strtotime($data->created_at));
            })
            ->rawColumns(['action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('admin.addon.index');
    }

    public function create()
    {
        return view('admin.addon.create');
    }

    public function install(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:zip',
        ]);

        // التحقق من تفعيل النظام عبر جدول licenses
        $license = License::getActiveLicense();
        if (!$license) {
            return response()->json(__('يرجى تفعيل النظام أولاً.'));
        }

        if (class_exists('ZipArchive')) {
            if ($request->hasFile('file')) {
                $path = Storage::disk('local')->put('addons', $request->file);

                $zip = new ZipArchive;
                $result = $zip->open(storage_path('app/' . $path));
                $random_dir = strtolower(Str::random(10));

                if ($result === true) {
                    $result = $zip->extractTo(base_path('temp/' . $random_dir . '/addons'));
                    $zip->close();
                } else {
                    return response()->json(__('لا يمكن فتح ملف الـ ZIP.'));
                }

                $configPath = base_path('temp/' . $random_dir . '/addons/addon.json');
                if (!file_exists($configPath)) {
                    \File::deleteDirectory(base_path('temp'));
                    Storage::delete($path);
                    return response()->json(__('ملف addon.json غير موجود في الإضافة.'));
                }

                $str = file_get_contents($configPath);
                $config = json_decode($str, true);

                if (!$config || !isset($config['keyword'])) {
                    \File::deleteDirectory(base_path('temp'));
                    Storage::delete($path);
                    return response()->json(__('ملف addon.json غير صالح.'));
                }

                $addon = Addon::where('keyword', $config['keyword'])->exists();

                if ($addon) {
                    \File::deleteDirectory(base_path('temp'));
                    Storage::delete($path);
                    return response()->json(__('هذه الإضافة مثبتة مسبقاً.'));
                }

                Storage::delete($path);
                \File::deleteDirectory(base_path('temp'));

                try {
                    $addn = Addon::where('keyword', $config['keyword'])->first();
                    if ($addn) {
                        $addn->delete();
                    }

                    $addon = new Addon;
                    $addon->keyword = $config['keyword'];
                    $addon->name = $config['name'];
                    $addon->save();

                    return response()->json(__('تم تثبيت الإضافة بنجاح.'));
                } catch (\Throwable $th) {
                    return response()->json(__('حدث خطأ أثناء التثبيت: ') . $th->getMessage());
                }
            }
        } else {
            return response()->json(__('ZipArchive غير مثبت على السيرفر.'));
        }
    }

    //*** GET Request Status
    public function uninstall($id)
    {
        $data = Addon::findOrFail($id);

        $files = json_decode($data->uninstall_files, true);

        if ($files && isset($files['files'])) {
            foreach ($files['files'] as $file) {
                if (file_exists(base_path() . $file)) {
                    unlink(base_path() . $file);
                }
            }
        }

        if ($files && isset($files['codes'])) {
            foreach ($files['codes'] as $code) {
                DB::statement($code);
            }
        }

        $data->delete();

        //--- Redirect Section
        $msg = __('تم إلغاء تثبيت الإضافة بنجاح.');
        return redirect()->back()->withSuccess($msg);
        //--- Redirect Section Ends
    }

    public function deleteDir($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}
