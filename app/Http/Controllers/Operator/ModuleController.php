<?php

namespace App\Http\Controllers\Operator;

use App\Models\Module;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ModuleController extends OperatorBaseController
{

    //*** JSON Request
    public function datatables()
    {
        $datas = Module::get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('created_at', function (Module $module) {
                return date('Y-m-d', strtotime($module->created_at));
            })
            ->rawColumns(['action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.module.index');
    }

    public function create()
    {
        return view('operator.module.create');
    }

    public function install(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:zip',
        ]);

        if (class_exists('ZipArchive')) {
            if ($request->hasFile('file')) {
                $path = Storage::disk('local')->put('modules', $request->file);

                $zip = new ZipArchive;
                $result = $zip->open(storage_path('app/' . $path));
                $random_dir = strtolower(Str::random(10));

                if ($result === true) {
                    $result = $zip->extractTo(base_path('temp/' . $random_dir . '/modules'));
                    $zip->close();
                } else {
                    return response()->json(__('لا يمكن فتح ملف الـ ZIP.'));
                }

                $configPath = base_path('temp/' . $random_dir . '/modules/module.json');
                if (!file_exists($configPath)) {
                    \File::deleteDirectory(base_path('temp'));
                    Storage::delete($path);
                    return response()->json(__('ملف module.json غير موجود في الإضافة.'));
                }

                $str = file_get_contents($configPath);
                $config = json_decode($str, true);

                if (!$config || !isset($config['keyword'])) {
                    \File::deleteDirectory(base_path('temp'));
                    Storage::delete($path);
                    return response()->json(__('ملف module.json غير صالح.'));
                }

                $moduleExists = Module::where('keyword', $config['keyword'])->exists();

                if ($moduleExists) {
                    \File::deleteDirectory(base_path('temp'));
                    Storage::delete($path);
                    return response()->json(__('هذه الإضافة مثبتة مسبقاً.'));
                }

                Storage::delete($path);
                \File::deleteDirectory(base_path('temp'));

                try {
                    $existingModule = Module::where('keyword', $config['keyword'])->first();
                    if ($existingModule) {
                        $existingModule->delete();
                    }

                    $module = new Module;
                    $module->keyword = $config['keyword'];
                    $module->name = $config['name'];
                    $module->save();

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
        $module = Module::findOrFail($id);

        $files = json_decode($module->uninstall_files, true);

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

        $module->delete();

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
