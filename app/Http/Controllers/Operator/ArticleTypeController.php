<?php

namespace App\Http\Controllers\Operator;

use App\Models\ArticleType;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class ArticleTypeController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $datas = ArticleType::latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->addColumn('action', function (ArticleType $data) {
                return '<div class="action-list"><a data-href="' . route('operator-article-type-edit', $data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i>' . __('Edit') . '</a><a href="javascript:;" data-href="' . route('operator-article-type-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
            })
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.article-type.index');
    }

    public function create()
    {
        return view('operator.article-type.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'name' => 'unique:article_types',
            'slug' => 'unique:article_types'
        ];
        $customs = [
            'name.unique' => __('This name has already been taken.'),
            'slug.unique' => __('This slug has already been taken.')
        ];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new ArticleType;
        $input = $request->all();
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section  
        $msg = __('New Data Added Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends  
    }

    //*** GET Request
    public function edit($id)
    {
        $data = ArticleType::findOrFail($id);
        return view('operator.article-type.edit', compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'name' => 'unique:article_types,name,' . $id,
            'slug' => 'unique:article_types,slug,' . $id
        ];
        $customs = [
            'name.unique' => __('This name has already been taken.'),
            'slug.unique' => __('This slug has already been taken.')
        ];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = ArticleType::findOrFail($id);
        $input = $request->all();
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section          
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends  

    }

    //*** GET Request
    public function destroy($id)
    {
        $data = ArticleType::findOrFail($id);

        //--- Check If there any publications available, If Available Then Delete it
        if ($data->publications->count() > 0) {
            foreach ($data->publications as $element) {
                $element->delete();
            }
        }
        $data->delete();
        //--- Redirect Section     
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends   
    }
}
