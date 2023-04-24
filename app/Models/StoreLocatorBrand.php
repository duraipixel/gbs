<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLocatorBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_center_id',
        'brand_id',
        'status'
    ];
}
