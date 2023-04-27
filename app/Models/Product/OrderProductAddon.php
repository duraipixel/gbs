<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProductAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'addon_item_id',
        'title',
        'amount',
        'description',
        'icon'
    ];
}
