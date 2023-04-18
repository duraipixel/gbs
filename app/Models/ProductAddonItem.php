<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAddonItem extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'product_addon_id',
        'label',
        'amount',
        'status'
    ];
}
