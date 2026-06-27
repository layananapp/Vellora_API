<?php
// ============ OrderItem ============
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'product_name', 'product_image',
        'price', 'qty', 'subtotal', 'variant', 'weight',
        'store_id', 'store_name',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class)->withTrashed();
    }

    public function store()
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function review()
    {
        return $this->hasOne(\App\Models\Review::class, 'order_item_id');
    }
}