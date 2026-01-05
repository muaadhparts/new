<?php

namespace App\Http\Controllers\Operator;

use App\Models\StaticContent;
use Datatables;
use Illuminate\Http\Request;
use Validator;

class StaticContentController extends OperatorBaseController
{

    //*** JSON Request
    public function datatables()
    {
        $datas = StaticContent::orderBy('id', 'desc')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->addColumn('header', function (StaticContent $data) {
                $class = $data->header == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->header == 1 ? 'selected' : '';
                $ns = $data->header == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('operator-static-content-header', [$data->id, 1]) . '" ' . $s . '>' . __('Showed') . '</option><option data-val="0" value="' . route('operator-static-content-header', [$data->id, 0]) . '" ' . $ns . '>' . __('Not Showed') . '</option></select></div>';
            })
            ->addColumn('footer', function (StaticContent $data) {
                $class = $data->footer == 1 ? 'drop-success' : 'drop-danger';
                $s = $data->footer == 1 ? 'selected' : '';
                $ns = $data->footer == 0 ? 'selected' : '';
                return '<div class="action-list"><select class="process select droplinks ' . $class . '"><option data-val="1" value="' . route('operator-static-content-footer', [$data->id, 1]) . '" ' . $s . '>' . __('Showed') . '</option><option data-val="0" value="' . route('operator-static-content-footer', [$data->id, 0]) . '" ' . $ns . '>' . __('Not Showed') . '</option></select></div>';
            })
            ->addColumn('action', function (StaticContent $data) {
                return '<div class="action-list"><a href="' . route('operator-static-content-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('operator-static-content-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['header', 'footer', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.static-content.index');
    }

    public function create()
    {
        return view('operator.static-content.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $slug = $request->slug;
        $main = array('home', 'faq', 'contact', 'blog', 'cart', 'checkout');
        if (in_array($slug, $main)) {
            return response()->json(array('errors' => [0 => 'This slug has already been taken.']));
        }
        $rules = ['slug' => 'unique:static_content'];
        $customs = ['slug.unique' => 'This slug has already been taken.'];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new StaticContent();
        $input = $request->all();

        if (!empty($request->meta_tag)) {
            $input['meta_tag'] = implode(',', $request->meta_tag);
        }
        if ($request->secheck == "") {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        }
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = 'New Data Added Successfully.' . '<a href="' . route("admin-static-content-index") . '">View Static Content Lists</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = StaticContent::findOrFail($id);
        return view('operator.static-content.edit', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $slug = $request->slug;
        $main = array('home', 'faq', 'contact', 'blog', 'cart', 'checkout');
        if (in_array($slug, $main)) {
            return response()->json(array('errors' => [0 => 'This slug has already been taken.']));
        }
        $rules = ['slug' => 'unique:static_content,slug,' . $id,
            'photo' => 'mimes:jpeg,jpg,png,svg'];
        $customs = ['slug.unique' => 'This slug has already been taken.'];
        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = StaticContent::findOrFail($id);
        $input = $request->all();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/pages', $name);
            if ($data->photo != null) {
                if (file_exists(public_path() . '/assets/images/pages/' . $data->photo)) {
                    unlink(public_path() . '/assets/images/pages/' . $data->photo);
                }
            }
            $input['photo'] = $name;
        }
        if (!empty($request->meta_tag)) {
            $input['meta_tag'] = implode(',', $request->meta_tag);
        } else {
            $input['meta_tag'] = null;
        }
        if ($request->secheck == "") {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        }
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = 'Data Updated Successfully.' . '<a href="' . route("admin-static-content-index") . '">View Static Content Lists</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }
    //*** GET Request Header
    public function header($id1, $id2)
    {
        $data = StaticContent::findOrFail($id1);
        $data->header = $id2;
        $data->update();
    }
    //*** GET Request Footer
    public function footer($id1, $id2)
    {
        $data = StaticContent::findOrFail($id1);
        $data->footer = $id2;
        $data->update();
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = StaticContent::findOrFail($id);
        $data->delete();
        //--- Redirect Section
        $msg = 'Data Deleted Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
