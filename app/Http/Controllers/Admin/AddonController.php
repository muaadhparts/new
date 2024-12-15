<?php

namespace App\Http\Controllers\Admin;

use App\Models\Addon;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

        $script_key = @file_get_contents(base_path() . '/license.txt');
        if (!$script_key) {
            return response()->json('Please activate your system first.');
        }

        $res = $this->addonLicenceCheck($script_key, $request->purchase_key);
        if ($res && $res['status'] == false) {
            return response()->json($res['message']);
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
                    return response()->json('Can\'t open the zip file.');
                }

           
  
                $str = file_get_contents(base_path('temp/' . $random_dir . '/addons/addon.json'));
                $config = json_decode($str, true);

                $addon = Addon::where('keyword', $config['keyword'])->exists();

                if ($addon) {
                    return response()->json('This addon is already installed.');
                }

                if (isset($request->purchase_key)) {
                    // Define the file path
                    $filePath = base_path('vendor/markury/src/Adapter/addon/otp.txt');

                    // Open the file for writing (this will overwrite the file if it already exists)
                    $file = fopen($filePath, 'w');

                    // Check if the file is successfully opened
                    if ($file) {
                        // Write the purchase key to the file
                        fwrite($file, $request->purchase_key);

                        // Close the file after writing
                        fclose($file);
                    } else {
                        return response()->json('Failed to open file for writing');
                    }
                } else {
                    return response()->json('Purchase key not provided.');

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
                    return response()->json('Addon installed successfully.');
                } catch (\Throwable $th) {
                    return response()->json('Something went wrong.');
                }

            }
        }
    }

    public function addonLicenceCheck($script_key, $addon_key)
    {
        return Http::get("https://geniusocean.com/verify/addoncheck.php?script_code=$script_key&addon_code=$addon_key")->json();
    }

    //*** GET Request Status
    public function uninstall($id)
    {
        $data = Addon::findOrFail($id);

        $files = json_decode($data->uninstall_files, true);

        foreach ($files['files'] as $file) {
            if (file_exists(base_path() . $file)) {
                unlink(base_path() . $file);
            }
        }

        foreach ($files['codes'] as $code) {
            DB::statement($code);
        }

        $data->delete();

        //--- Redirect Section
        $msg = __('Addon Uninstalled Successfully.');
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
