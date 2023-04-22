<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreLocator extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [

        'parent_id',
        'brand_id',
        'title',
        'slug',
        'banner',
        'banner_mb',
        'description',
        'pincode',
        'address',
        'latitude',
        'longitude',
        'email',
        'contact_no',
        'status',
        'added_by',
        'order_by',

    ];
    public function meta()
    {
        return $this->hasOne(StoreLocatorMetaTag::class, 'store_locator_id', 'id');
    }
    public function nearPincode()
    {
        return $this->hasMany(StoreLocatorPincode::class,'store_locator_id','id');
    }
    public function contact()
    {
        return $this->hasMany(StoreLocatorContact::class,'store_locator_id','id');
    }
    public function serviceEmail()
    {
        return $this->hasMany(StoreLocatorEmail::class,'store_locator_id','id');
    }
}
