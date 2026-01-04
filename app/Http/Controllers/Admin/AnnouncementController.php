<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class AnnouncementController extends AdminBaseController
{

    //*** JSON Request
    public function datatables($type)
    {
        $datas = Announcement::where('type', '=', $type)->latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->editColumn('photo', function (Announcement $announcement) {
                $photo = $announcement->photo ? url('assets/images/announcements/' . $announcement->photo) : url('assets/images/noimage.png');
                return '<img src="' . $photo . '" alt="Image">';
            })
            ->addColumn('action', function (Announcement $announcement) {
                return '<div class="action-list"><a data-href="' . route('admin-announcement-edit', $announcement->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('admin-announcement-delete', $announcement->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->rawColumns(['photo', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        return view('admin.announcement.index');
    }

    //*** GET Request
    public function large()
    {
        return view('admin.announcement.large');
    }

    //*** GET Request
    public function bottom()
    {
        return view('admin.announcement.bottom');
    }

    //*** GET Request
    public function create()
    {
        return view('admin.announcement.create');
    }

    //*** GET Request
    public function largecreate()
    {
        return view('admin.announcement.largecreate');
    }

    //*** GET Request
    public function bottomcreate()
    {
        return view('admin.announcement.bottomcreate');
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
        $announcement = new Announcement();
        $input = $request->all();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/announcements', $name);
            $input['photo'] = $name;
        }
        $announcement->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $announcement = Announcement::findOrFail($id);
        return view('admin.announcement.edit', compact('announcement'));
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
        $announcement = Announcement::findOrFail($id);
        $input = $request->all();
        if ($file = $request->file('photo')) {
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/announcements', $name);
            if ($announcement->photo != null) {
                if (file_exists(public_path() . '/assets/images/announcements/' . $announcement->photo)) {
                    unlink(public_path() . '/assets/images/announcements/' . $announcement->photo);
                }
            }
            $input['photo'] = $name;
        }
        $announcement->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        //If Photo Doesn't Exist
        if ($announcement->photo == null) {
            $announcement->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }
        //If Photo Exist
        if (file_exists(public_path() . '/assets/images/announcements/' . $announcement->photo)) {
            unlink(public_path() . '/assets/images/announcements/' . $announcement->photo);
        }
        $announcement->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
