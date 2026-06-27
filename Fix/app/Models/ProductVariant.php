<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'product_id',
        'variant_name',
        'price',
        'stock'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
