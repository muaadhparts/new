<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Brand;
use Modules\Product\Entities\Category;


class Catalog extends Model
{
     use HasFactory;

    protected $table = "vehicles";
    protected $guarded = ["id"];

    public $with = ['brand:id,name'];
//    public $with = ['brand:id,name' ,'parentCategory:id,vehicle_id,name,public_id,code,thumbnailImage,illustrated'];
    public static function boot()
    {
        parent::boot();
        static::created(function ($catlog) {
            $catlog->created_by = Auth::user()->id ?? null;
        });

        static::updating(function ($catlog) {
            $catlog->updated_by = Auth::user()->id ?? null;
        });


    }


    public function brand()
    {
        return $this->belongsTo(Partner::class, "brand_id") ;
    }



    public function levels()
    {
        return $this->belongsToMany(Level::class, 'catalog_level')
            ->withPivot('catalog_id', 'level_id')
            ->withTimestamps();
    }

    public function level()
    {
        return $this->belongsToMany(Level::class, 'catalog_id','level_id')
            ->withPivot('catalog_id', 'level_id');
    }

    public function subCategories(){
        return $this->hasMany(Category::class,'parent_id','id')->with('subCategories');
    }

    public function categories(){
        return $this->hasMany( Category::class,'vehicle_id','id')->with('subCategories');
    }



    public function attributes(){
        return $this->hasMany( MajorAttributes::class,'data','data');
    }


    public function parentCategory(){
        return $this->hasMany( Category::class,'vehicle_id','id')->whereNull('parent_id');
    }



    protected static function factory()
    {
        return \Modules\Product\Database\factories\CatlogFactory::new();
    }

}
