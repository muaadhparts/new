<?php

namespace App\Http\Controllers\Operator;

use App\{
    Models\Publication,
    Models\ArticleType
};
use Illuminate\Http\Request;
use Validator;
use Datatables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PublicationController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $datas = Publication::orderBy('id', 'desc')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('photo', function (Publication $publication) {
                $photo = $publication->photo ? url('assets/images/publications/' . $publication->photo) : url('assets/images/noimage.png');
                return '<img src="' . $photo . '" alt="Image">';
            })
            ->addColumn('action', function (Publication $publication) {
                return '<div class="action-list"><a href="' . route('operator-publication-edit', $publication->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('operator-publication-delete', $publication->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['photo', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {


        return view('operator.publication.post.index');
    }

    //*** GET Request
    public function create()
    {
        $cats = ArticleType::all();
        return view('operator.publication.post.create', compact('cats'));
    }

    public function settings()
    {
        return view('operator.publication.settings');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'photo'      => 'required|mimes:jpeg,jpg,png,svg',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $publication = new Publication();
        $input = $request->all();

        $slug = Str::slug($request->title) . Str::random(4);

        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/publications', $name);
            $input['photo'] = $name;
        }
        if (!empty($request->meta_tag)) {
            $input['meta_tag'] = implode(',', $request->meta_tag);
        }
        if (!empty($request->tags)) {
            $input['tags'] = implode(',', $request->tags);
        }
        if ($request->secheck == "") {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        }
        $input['slug'] = $slug;

        Session::forget('footer_publications');
        $publication->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.') . '<a href="' . route("admin-publication-index") . '">' . __("View Post Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $cats = ArticleType::all();
        $publication = Publication::findOrFail($id);
        return view('operator.publication.post.edit', compact('publication', 'cats'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'photo'      => 'mimes:jpeg,jpg,png,svg',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $publication = Publication::findOrFail($id);
        $input = $request->all();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/publications', $name);
            if ($publication->photo != null) {
                if (file_exists(public_path() . '/assets/images/publications/' . $publication->photo)) {
                    unlink(public_path() . '/assets/images/publications/' . $publication->photo);
                }
            }
            $input['photo'] = $name;
        }
        if (!empty($request->meta_tag)) {
            $input['meta_tag'] = implode(',', $request->meta_tag);
        } else {
            $input['meta_tag'] = null;
        }
        if (!empty($request->tags)) {
            $input['tags'] = implode(',', $request->tags);
        } else {
            $input['tags'] = null;
        }
        if ($request->secheck == "") {
            $input['meta_tag'] = null;
            $input['meta_description'] = null;
        }
        $input['slug'] = Str::slug($request->title) . Str::random(4);
        $publication->update($input);
        //--- Logic Section Ends
        Session::forget('footer_publications');
        //--- Redirect Section
        $msg = __('Data Updated Successfully.') . '<a href="' . route("admin-publication-index") . '">' . __("View Post Lists") . '</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $publication = Publication::findOrFail($id);
        //If Photo Doesn't Exist
        if ($publication->photo == null) {
            $publication->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }
        //If Photo Exist
        if (file_exists(public_path() . '/assets/images/publications/' . $publication->photo)) {
            unlink(public_path() . '/assets/images/publications/' . $publication->photo);
        }
        $publication->delete();
        Session::forget('footer_publications');
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
