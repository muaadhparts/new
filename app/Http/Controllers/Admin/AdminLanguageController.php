<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminLanguage;
use Illuminate\Http\Request;
use Datatables;
use Validator;
use Illuminate\Support\Str;

class AdminLanguageController extends AdminBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $datas = AdminLanguage::latest('id')->get();

        return Datatables::of($datas)
            ->addColumn('action', function (AdminLanguage $data) {
                $delete = $data->id == 1 ? '' : '<a href="javascript:;" data-href="' . route('admin-tlang-delete', $data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
                $default = $data->is_default == 1
                    ? '<a><i class="fa fa-check"></i> ' . __("Default") . '</a>'
                    : '<a class="status" data-href="' . route('admin-tlang-st', ['id1' => $data->id, 'id2' => 1]) . '">' . __("Set Default") . '</a>';

                return '<div class="action-list">
                    <a href="' . route('admin-tlang-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a>' . $delete . $default . '
                </div>';
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function index()
    {
        return view('admin.adminlanguage.index');
    }

    public function create()
    {
        return view('admin.adminlanguage.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        $rules = ['language' => 'unique:admin_languages'];
        $customs = ['language.unique' => 'This language has already been taken.'];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $new = [];
        $input = $request->all();
        $data = new AdminLanguage();
        $data->language = $input['language'];
        $name = time() . Str::random(8);
        $data->name = $name;
        $data->file = $name . '.json';
        $data->rtl = $input['rtl'];
        $data->save();

        unset($input['_token'], $input['language']);
        $keys = $request->input('keys', []);
        $values = $request->input('values', []);

        // Filter out empty keys and values
        $filteredKeys = [];
        $filteredValues = [];

        foreach ($keys as $index => $key) {
            // Only include if both key and value exist and are not empty
            if (!empty($key) && isset($values[$index]) && $values[$index] !== '') {
                $filteredKeys[] = trim($key);
                $filteredValues[] = $values[$index];
            }
        }

        // Check if arrays have same length
        if (count($filteredKeys) !== count($filteredValues)) {
            return response()->json([
                'errors' => ['keys' => ['The number of keys and values must match.']]
            ], 422);
        }

        // Check if we have any data
        if (count($filteredKeys) === 0) {
            return response()->json([
                'errors' => ['keys' => ['At least one key-value pair is required.']]
            ], 422);
        }

        foreach (array_combine($filteredKeys, $filteredValues) as $key => $value) {
            $n = str_replace("_", " ", $key);
            $new[$n] = $value;
        }

        $mydata = json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(resource_path('lang/' . $data->file), $mydata);

        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
    }

    //*** GET Request
    public function edit($id)
    {
        $data = AdminLanguage::findOrFail($id);

        $filePath = resource_path('lang/' . $data->file);
        if (!file_exists($filePath)) {
            abort(404, "Language file not found: {$data->file}");
        }

        $data_results = file_get_contents($filePath);
        $lang = json_decode($data_results, true);

        return view('admin.adminlanguage.edit', compact('data', 'lang'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        // Check if PHP encountered a multipart body parts limit error
        $lastError = error_get_last();
        if ($lastError && strpos($lastError['message'], 'Multipart body parts limit exceeded') !== false) {
            return response()->json([
                'errors' => ['general' => [
                    'Too many language entries. Please contact your administrator to increase the PHP max_multipart_body_parts limit in php.ini, or reduce the number of language entries.'
                ]]
            ], 422);
        }

        $rules = ['language' => 'unique:admin_languages,language,' . $id];
        $customs = ['language.unique' => 'This language has already been taken.'];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $new = [];
        $input = $request->all();
        $data = AdminLanguage::findOrFail($id);

        $data->language = $input['language'];
        $data->rtl = $input['rtl'];
        $data->update();

        unset($input['_token'], $input['language']);
        $keys = $request->input('keys', []);
        $values = $request->input('values', []);

        // Filter out empty keys and values
        $filteredKeys = [];
        $filteredValues = [];

        foreach ($keys as $index => $key) {
            // Only include if both key and value exist and are not empty
            if (!empty($key) && isset($values[$index]) && $values[$index] !== '') {
                $filteredKeys[] = trim($key);
                $filteredValues[] = $values[$index];
            }
        }

        // Check if arrays have same length
        if (count($filteredKeys) !== count($filteredValues)) {
            return response()->json([
                'errors' => ['keys' => ['The number of keys and values must match.']]
            ], 422);
        }

        // Check if we have any data
        if (count($filteredKeys) === 0) {
            return response()->json([
                'errors' => ['keys' => ['At least one key-value pair is required.']]
            ], 422);
        }

        foreach (array_combine($filteredKeys, $filteredValues) as $key => $value) {
            $n = str_replace("_", " ", $key);
            $new[$n] = $value;
        }

        $mydata = json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(resource_path('lang/' . $data->file), $mydata);

        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $data = AdminLanguage::findOrFail($id1);
        $data->is_default = $id2;
        $data->update();

        AdminLanguage::where('id', '!=', $id1)->update(['is_default' => 0]);

        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        if ($id == 1) {
            return __("You don't have access to remove this language.");
        }

        $data = AdminLanguage::findOrFail($id);
        if ($data->is_default == 1) {
            return __("You can not remove default language.");
        }

        $filePath = resource_path('lang/' . $data->file);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $data->delete();

        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
    }
}

// namespace App\Http\Controllers\Admin;

// use App\Models\AdminLanguage;
// use Illuminate\Http\Request;
// use Datatables;
// use Validator;
// use Illuminate\Support\Str;

// class AdminLanguageController extends AdminBaseController
// {

//     //*** JSON Request
//     public function datatables()
//     {
//         $datas = AdminLanguage::latest('id')->get();
//         //--- Integrating This Collection Into Datatables
//         return Datatables::of($datas)
//             ->addColumn('action', function (AdminLanguage $data) {
//                 $delete = $data->id == 1 ? '' : '<a href="javascript:;" data-href="' . route('admin-tlang-delete', $data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
//                 $default = $data->is_default == 1 ? '<a><i class="fa fa-check"></i> ' . __("Default") . '</a>' : '<a class="status" data-href="' . route('admin-tlang-st', ['id1' => $data->id, 'id2' => 1]) . '">' . __("Set Default") . '</a>';
//                 return '<div class="action-list">
//                                             <a href="' . route('admin-tlang-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a>' . $delete . $default .
//                     '</div>';
//             })
//             ->rawColumns(['action'])
//             ->toJson(); //--- Returning Json Data To Client Side
//     }

//     public function index()
//     {
//         return view('admin.adminlanguage.index');
//     }

//     public function create()
//     {
//         return view('admin.adminlanguage.create');
//     }

//     //*** POST Request
//     public function store(Request $request)
//     {

//         //--- Validation Section
//         $rules = ['language' => 'unique:languages'];
//         $customs = ['language.unique' => 'This language has already been taken.'];
//         $validator = Validator::make($request->all(), $rules, $customs);
//         if ($validator->fails()) {
//             return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
//         }
//         //--- Validation Section Ends

//         //--- Logic Section
//         $new = null;
//         $input = $request->all();
//         $data = new AdminLanguage();
//         $data->language = $input['language'];
//         $name = time() . Str::random(8);
//         $data->name = $name;
//         $data->file = $name . '.json';
//         $data->rtl = $input['rtl'];
//         $data->save();
//         unset($input['_token']);
//         unset($input['language']);
//         $keys = $request->keys;
//         $values = $request->values;
//         foreach (array_combine($keys, $values) as $key => $value) {
//             $n = str_replace("_", " ", $key);
//             $new[$n] = $value;
//         }
//         $mydata = json_encode($new);
//         file_put_contents(public_path() . '/project/resources/lang/' . $data->file, $mydata);
//         //--- Logic Section Ends

//         //--- Redirect Section        
//         $msg = __('New Data Added Successfully.');
//         return response()->json($msg);
//         //--- Redirect Section Ends    
//     }

//     //*** GET Request
//     public function edit($id)
//     {
//         $data = AdminLanguage::findOrFail($id);
//         $data_results = file_get_contents(public_path() . '/project/resources/lang/' . $data->file);
//         $lang = json_decode($data_results, true);
//         return view('admin.adminlanguage.edit', compact('data', 'lang'));
//     }

//     //*** POST Request
//     public function update(Request $request, $id)
//     {

//         //--- Validation Section
//         $rules = ['language' => 'unique:languages,language,' . $id];
//         $customs = ['language.unique' => 'This language has already been taken.'];
//         $validator = Validator::make($request->all(), $rules, $customs);
//         if ($validator->fails()) {
//             return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
//         }
//         //--- Validation Section Ends

//         //--- Logic Section
//         $new = null;
//         $input = $request->all();
//         $data = AdminLanguage::findOrFail($id);
//         if (file_exists(public_path() . '/project/resources/lang/' . $data->file)) {
//             unlink(public_path() . '/project/resources/lang/' . $data->file);
//         }
//         $data->language = $input['language'];
//         $data->rtl = $input['rtl'];
//         $data->update();
//         unset($input['_token']);
//         unset($input['language']);
//         $keys = $request->keys;
//         $values = $request->values;
//         foreach (array_combine($keys, $values) as $key => $value) {
//             $n = str_replace("_", " ", $key);
//             $new[$n] = $value;
//         }
//         $mydata = json_encode($new);
//         file_put_contents(public_path() . '/project/resources/lang/' . $data->file, $mydata);
//         //--- Logic Section Ends

//         //--- Redirect Section     
//         $msg = __('Data Updated Successfully.');
//         return response()->json($msg);
//         //--- Redirect Section Ends            
//     }

//     //*** GET Request
//     public function status($id1, $id2)
//     {
//         $data = AdminLanguage::findOrFail($id1);
//         $data->is_default = $id2;
//         $data->update();
//         $data = AdminLanguage::where('id', '!=', $id1)->update(['is_default' => 0]);
//         //--- Redirect Section     
//         $msg = __('Data Updated Successfully.');
//         return response()->json($msg);
//         //--- Redirect Section Ends  
//     }

//     //*** GET Request Delete
//     public function destroy($id)
//     {
//         if ($id == 1) {
//             return __("You don't have access to remove this language.");
//         }
//         $data = AdminLanguage::findOrFail($id);
//         if ($data->is_default == 1) {
//             return __("You can not remove default language.");
//         }
//         if (file_exists(public_path() . '/project/resources/lang/' . $data->file)) {
//             unlink(public_path() . '/project/resources/lang/' . $data->file);
//         }
//         $data->delete();
//         //--- Redirect Section     
//         $msg = __('Data Deleted Successfully.');
//         return response()->json($msg);
//         //--- Redirect Section Ends     
//     }
// }
