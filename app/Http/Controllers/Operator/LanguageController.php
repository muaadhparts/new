<?php

namespace App\Http\Controllers\Operator;

use App\Models\Language;
use Datatables;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Validator;

class LanguageController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $datas = Language::latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->addColumn('action', function (Language $data) {
                $delete = $data->id == 1 ? '' : '<a href="javascript:;" data-href="' . route('operator-lang-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
                $default = $data->is_default == 1 ? '<a><i class="fa fa-check"></i> ' . __('Default') . '</a>' : '<a class="status" data-href="' . route('operator-lang-st', ['id1' => $data->id, 'id2' => 1]) . '">' . __('Set Default') . '</a>';
                return '<div class="action-list"><a href="' . route('operator-lang-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="' . route('operator-lang-export', $data->id) . '"> <i class="fas fa-download"></i>' . __('Export') . '</a>' . $delete . $default . '</div>';
            })
            ->rawColumns(['action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.language.index');
    }

    public function create()
    {
        $lang =  File::json(resource_path('lang/default.json'));
        return view('operator.language.create', compact('lang'));
    }

    public function import()
    {
        return view('operator.language.import');
    }

    //*** POST Request
    public function store(Request $request)
    {

        //--- Validation Section
        $rules = ['language' => 'unique:languages'];
        $customs = ['language.unique' => 'This language has already been taken.'];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $new = [];
        $input = $request->all();
        $data = new Language();
        $data->language = $input['language'];
        $name = time() . Str::random(8);
        $data->name = $name;
        $data->file = $name . '.json';
        $data->rtl = $input['rtl'];
        $data->save();
        unset($input['_token']);
        unset($input['language']);

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
            ]);
        }

        // Check if we have any data
        if (count($filteredKeys) === 0) {
            return response()->json([
                'errors' => ['keys' => ['At least one key-value pair is required.']]
            ]);
        }

        foreach (array_combine($filteredKeys, $filteredValues) as $key => $value) {
            $n = str_replace("_", " ", $key);
            $new[$n] = $value;
        }
        $mydata = json_encode($new, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents(public_path() . '/project/resources/lang/' . $data->file, $mydata);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** POST Request
    public function importStore(Request $request)
    {

        //--- Validation Section
        $rules = [
            'csvfile' => 'required|mimes:csv,txt',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section

        $filename = '';
        if ($file = $request->file('csvfile')) {
            $filename = time() . '-' . str_replace(' ', '', $file->getClientOriginalName());
            $file->move('assets/temp_files', $filename);
        }

        $new = null;
        $input = $request->all();
        $data = new Language();
        $data->language = $input['language'];
        $name = time() . Str::random(8);
        $data->name = $name;
        $data->file = $name . '.json';
        $data->rtl = $input['rtl'];
        $data->save();
        unset($input['_token']);
        unset($input['language']);

        $file = fopen(public_path('assets/temp_files/' . $filename), "r");
        $i = 1;
        $keys = array();
        $values = array();
        while (($line = fgetcsv($file)) !== false) {
            if ($i != 1) {
                if (!empty($line[0])) {
                    $keys[] = $line[0];
                    $values[] = mb_convert_encoding($line[1], 'UTF-8', 'UTF-8');
                }
            }
            $i++;
        }
        fclose($file);

        foreach (array_combine($keys, $values) as $key => $value) {
            $new[$key] = $value;
        }
        $mydata = json_encode($new);
        file_put_contents(public_path() . '/project/resources/lang/' . $data->file, $mydata);
        $files = glob('assets/temp_files/*'); //get all file names
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
            //delete file
        }

        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Imported Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = Language::findOrFail($id);
        $lang =  File::json(resource_path('lang/'.$data->file));

        return view('operator.language.edit', compact('data', 'lang'));
    }

    //*** GET Request
    public function export($id)
    {
        $data = Language::findOrFail($id);
        $data_results = file_get_contents('project/resources/lang/' . $data->file);
        $lang = json_decode($data_results, true);
        $files = glob('assets/temp_files/*'); //get all file names
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
            //delete file
        }
        $f = fopen('assets/temp_files/language.csv', "w");
        $hline[0] = 'Main Languages';
        $hline[1] = 'Translations';
        fputcsv($f, $hline);
        foreach ($lang as $key => $value) {
            $line[0] = $key;
            $line[1] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            fputcsv($f, $line);
        }
        fclose($f);

        return response()->download(public_path('assets/temp_files/language.csv'));
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

        //--- Validation Section
        $rules = ['language' => 'unique:languages,language,' . $id];
        $customs = ['language.unique' => 'This language has already been taken.'];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $new = [];
        $input = $request->all();
        $data = Language::findOrFail($id);

        $data->language = $input['language'];
        $data->rtl = $input['rtl'];
        $data->update();
        unset($input['_token']);
        unset($input['language']);

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
            ]);
        }

        // Check if we have any data
        if (count($filteredKeys) === 0) {
            return response()->json([
                'errors' => ['keys' => ['At least one key-value pair is required.']]
            ]);
        }

        foreach (array_combine($filteredKeys, $filteredValues) as $key => $value) {
            $n = str_replace("_", " ", $key);
            $new[$n] = $value;
        }
        $mydata = json_encode($new, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents(resource_path('lang/' . $data->file), $mydata);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function xupdate(Request $request, $id)
    {
        //--- Validation Section
        $rules = ['language' => 'unique:languages,language,' . $id];
        $messages = ['language.unique' => 'This language has already been taken.'];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = Language::findOrFail($id);

        $data->language = $request->input('language');
        $data->rtl = $request->input('rtl');
        $data->update();

        $keys = $request->input('keys', []);
        $values = $request->input('values', []);

        $newData = [];
        foreach (array_combine($keys, $values) as $key => $value) {
            $formattedKey = str_replace("_", " ", $key);
            $newData[$formattedKey] = $value;
        }

        $jsonData = json_encode($newData, JSON_PRETTY_PRINT);

        file_put_contents(resource_path('lang/' . $data->file), $jsonData);
        //--- Logic Section Ends

        //--- Redirect Section
        return response()->json(__('Data Updated Successfully.'));
        //--- Redirect Section Ends
    }



    public function status($id1, $id2)
    {
        $data = Language::findOrFail($id1);
        $data->is_default = $id2;
        $data->update();
        $data = Language::where('id', '!=', $id1)->update(['is_default' => 0]);
        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        if ($id == 1) {
            return __("You don't have access to remove this language.");
        }
        $data = Language::findOrFail($id);
        if ($data->is_default == 1) {
            return __("You can not remove default language.");
        }
        if (file_exists(public_path() . '/project/resources/lang/' . $data->file)) {
            unlink(public_path() . '/project/resources/lang/' . $data->file);
        }

        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
