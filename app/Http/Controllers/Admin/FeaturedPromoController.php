<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedPromo;
use Illuminate\Http\Request;
use Datatables;
use Illuminate\Support\Facades\Validator;

class FeaturedPromoController extends Controller
{
    public function datatables()
    {
        $datas = FeaturedPromo::latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('photo', function (FeaturedPromo $data) {
                $photo = $data->photo ? url('assets/images/arrival/' . $data->photo) : url('assets/images/noimage.png');
                return '<img src="' . $photo . '" alt="Image">';
            })
            ->editColumn('title', function (FeaturedPromo $data) {
                $title = mb_strlen(strip_tags($data->title), 'UTF-8') > 250 ? mb_substr(strip_tags($data->title), 0, 250, 'UTF-8') . '...' : strip_tags($data->title);
                return  $title;
            })
            ->editColumn('up_sale', function (FeaturedPromo $data) {
                $up_sale = mb_strlen(strip_tags($data->up_sale), 'UTF-8') > 250 ? mb_substr(strip_tags($data->up_sale), 0, 250, 'UTF-8') . '...' : strip_tags($data->up_sale);
                return  $up_sale;
            })
            ->editColumn('header', function (FeaturedPromo $data) {
                $header = mb_strlen(strip_tags($data->header), 'UTF-8') > 250 ? mb_substr(strip_tags($data->header), 0, 250, 'UTF-8') . '...' : strip_tags($data->header);
                return  $header;
            })
          
            ->addColumn('action', function (FeaturedPromo $data) {
                return '<div class="action-list"><a href="' . route('admin-featured-promo-edit', $data->id) . '"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('admin-featured-promo-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['photo', 'action', 'up_sale'])
            ->toJson();
    }
    public function index()
    {
        // Redirect to Best Sellers management page
        return redirect()->route('admin-fs-best-sellers');
    }
    public function create()
    {
        return view('admin.featured-promo.create');
    }

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
        //--- Logic Section
        $data = new FeaturedPromo();
        $input = $request->all();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/arrival', $name);
            $input['photo'] = $name;
        }
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
    public function edit($id)
    {
        $data = FeaturedPromo::findOrFail($id);
        return view('admin.featured-promo.edit', compact('data'));
    }

    public function status($id1, $id2)
    {
        FeaturedPromo::findOrFail($id1)->update([
            'status' => $id2
        ]);
    }

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
        $data = FeaturedPromo::findOrFail($id);
        $input = $request->all();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/arrival', $name);
            if ($data->photo != null) {
                if (file_exists(public_path() . '/assets/images/arrival/' . $data->photo)) {
                    unlink(public_path() . '/assets/images/arrival/' . $data->photo);
                }
            }
            $input['photo'] = $name;
        }
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function destroy($id)
    {
        $data = FeaturedPromo::findOrFail($id);
        //If Photo Doesn't Exist
        if ($data->photo == null) {
            $data->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }
        //If Photo Exist
        if (file_exists(public_path() . '/assets/images/arrival/' . $data->photo)) {
            unlink(public_path() . '/assets/images/arrival/' . $data->photo);
        }
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
