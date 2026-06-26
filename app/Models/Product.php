<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Store;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'store_id',
        'category_id',
        'product_name',
        'description',
        'price',
        'stock',
        'is_active',
        'rating_avg',
        'rating_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['price_min', 'price_max'];

    public function getPriceMinAttribute()
    {
        $variants = $this->variants;
        if ($variants && $variants->count() > 0) {
            return (float) $variants->min('price');
        }
        return (float) ($this->attributes['price'] ?? 0);
    }

    public function getPriceMaxAttribute()
    {
        $variants = $this->variants;
        if ($variants && $variants->count() > 0) {
            return (float) $variants->max('price');
        }
        return (float) ($this->attributes['price'] ?? 0);
    }

    public function getPriceAttribute($value)
    {
        $variants = $this->variants;
        if ($variants && $variants->count() > 0) {
            return (float) $variants->min('price');
        }
        return (float) $value;
    }

    public function getStockAttribute($value)
    {
        $variants = $this->variants;
        if ($variants && $variants->count() > 0) {
            return (int) $variants->sum('stock');
        }
        return (int) $value;
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function recalculateRating(): void
    {
        $avg   = $this->reviews()->avg('rating') ?? 0;
        $count = $this->reviews()->count();
 
        $this->update([
            'rating_avg'   => round($avg, 2),
            'rating_count' => $count,
        ]);
    }
}
