<?php

namespace App\Models\Product;

use App\Models\Master\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'product_id', 'star', 'comments', 'status'];

    protected $casts = [
        'created_at' => 'datetime:d M Y'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

}
