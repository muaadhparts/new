<?php

namespace App\Http\Controllers\Admin;

use Datatables;
use App\Models\HelpArticle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HelpArticleController extends AdminBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $datas = HelpArticle::orderBy('id','desc')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->editColumn('details', function(HelpArticle $data) {
                                $details = mb_strlen(strip_tags($data->details),'utf-8') > 250 ? mb_substr(strip_tags($data->details),0,250,'utf-8').'...' : strip_tags($data->details);
                                return  $details;
                            })
                            ->addColumn('action', function(HelpArticle $data) {
                                return '<div class="action-list"><a href="' . route('admin-help-article-edit',$data->id) . '"> <i class="fas fa-edit"></i>'.__("Edit").'</a><a href="javascript:;" data-href="' . route('admin-help-article-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index(){
        return view('admin.help-article.index');
    }

    public function create(){
        return view('admin.help-article.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Logic Section
        $helpArticle = new HelpArticle();
        $input = $request->all();
        $helpArticle->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('New Data Added Successfully.').'<a href="'.route("admin-help-article-index").'">'.__("View Help Article Lists").'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = HelpArticle::findOrFail($id);
        return view('admin.help-article.edit',compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Logic Section
        $helpArticle = HelpArticle::findOrFail($id);
        $input = $request->all();
        $helpArticle->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.').'<a href="'.route("admin-help-article-index").'">'.__("View Help Article Lists").'</a>';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request Delete
    public function destroy($id)
    {
        $helpArticle = HelpArticle::findOrFail($id);
        $helpArticle->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
