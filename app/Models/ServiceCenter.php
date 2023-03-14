<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCenter extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [

        'parent_id',
        'title',
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
        return $this->hasOne(ServiceCenterMetaTag::class, 'service_center_id', 'id');
    }
    
    public function parent()
    {
        return $this->belongsTo(ServiceCenter::class, 'parent_id', 'id');
    }

}
