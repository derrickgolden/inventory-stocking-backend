<?php

namespace App\Models;

use App\Models\Counter;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CounterProductStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'counter_id',
        'product_id',
        'current_quantity',
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
