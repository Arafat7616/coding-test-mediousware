<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('d-M-Y');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariantPrice::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
