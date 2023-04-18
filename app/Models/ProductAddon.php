<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAddon extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'title',
        'product_id',
        'description',
        'icon',
        'status',
        'order_by',
        'added_by'
    ];

    public function items()
    {
        return $this->hasMany(ProductAddonItem::class,'product_addon_id','id')->where('status','published');
    }

}
