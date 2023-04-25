<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartProductAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'addon_item_id',
        'title',
        'amount'
    ];
}
