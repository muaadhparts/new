<?php

namespace App\Http\Controllers\Merchant;

use App\Models\HeroCarousel;

use Illuminate\{Http\Request};

use Validator;
use Datatables;

class HeroCarouselController extends MerchantBaseController
{
    //*** JSON Request
    public function datatables()
    {
         $user = $this->user;
         $datas =  $user->heroCarousels()->latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->editColumn('photo', function(HeroCarousel $data) {
                                $photo = $data->photo ? url('assets/images/sliders/'.$data->photo):url('assets/images/noimage.png');
                                return '<img src="' . $photo . '" alt="Image">';
                            })
                            ->editColumn('name', function(HeroCarousel $data) {
                                $name = mb_strlen(strip_tags($data->name),'UTF-8') > 250 ? mb_substr(strip_tags($data->name),0,250,'UTF-8').'...' : strip_tags($data->name);
                                return  $name;
                            })
                            ->addColumn('action', function(HeroCarousel $data) {
                                return '<div class="action-list"><a href="' . route('merchant-hero-carousel-edit',$data->id) . '"> <i class="fas fa-edit"></i>'.__('Edit').'</a><a href="javascript:;" data-href="' . route('merchant-hero-carousel-delete',$data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['photo', 'action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        return view('merchant.hero-carousel.index');
    }

    //*** GET Request
    public function create()
    {
        return view('merchant.hero-carousel.create');
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
        $data = new HeroCarousel();
        $input = $request->all();
        if ($file = $request->file('photo'))
         {
            $extensions = ['jpeg','jpg','png','svg'];
            if(!in_array($file->getClientOriginalExtension(),$extensions)){
                return response()->json(array('errors' => ['Image format not supported']));
            }
            $name = \PriceHelper::ImageCreateName($file);
            $file->move('assets/images/sliders',$name);
            $input['photo'] = $name;
        }

        $input['user_id'] = $this->user->id;
        // Save Data
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
        $data = HeroCarousel::findOrFail($id);
        return view('merchant.hero-carousel.edit',compact('data'));
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
        $data = HeroCarousel::findOrFail($id);
        $input = $request->all();
            if ($file = $request->file('photo'))
            {
                $extensions = ['jpeg','jpg','png','svg'];
                if(!in_array($file->getClientOriginalExtension(),$extensions)){
                    return response()->json(array('errors' => ['Image format not supported']));
                }
                $name = \PriceHelper::ImageCreateName($file);
                $file->move('assets/images/sliders',$name);
                if($data->photo != null)
                {
                    if (file_exists(public_path().'/assets/images/sliders/'.$data->photo)) {
                        unlink(public_path().'/assets/images/sliders/'.$data->photo);
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

    //*** GET Request Delete
    public function destroy($id)
    {
        $data = HeroCarousel::findOrFail($id);
        //If Photo Doesn't Exist
        if($data->photo == null){
            $data->delete();
            //--- Redirect Section
            $msg = __('Data Deleted Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }
        //If Photo Exist
        if (file_exists(public_path().'/assets/images/sliders/'.$data->photo)) {
            unlink(public_path().'/assets/images/sliders/'.$data->photo);
        }
        $data->delete();
        //--- Redirect Section
        $msg = __('Data Deleted Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
