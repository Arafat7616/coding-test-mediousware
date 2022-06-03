<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $fillable = [
        'product_variant_one', 'product_variant_two', 'product_variant_three', 'price', 'stock'
    ];

    public function variant_one()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_one', 'id');
    }
    
    public function variant_two()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_two', 'id');
    }

    public function variant_three()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_three', 'id');
    }
}
