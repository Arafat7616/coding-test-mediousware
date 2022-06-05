<?php

namespace App\Models;

use App\Models\Variant;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'variant', 'variant_id'
    ];

    public function category()
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'id');
    }

    

}
