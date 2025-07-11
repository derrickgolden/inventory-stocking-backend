<?php

namespace App\Models;

use App\Models\RestockCounter;
use App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestockCounterItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'restock_counter_id',
        'product_id',
        'quantity',
        'purchase_price',
        'sale_price',
        'sub_total',
    ];

    public function restockCounter()
    {
        return $this->belongsTo(RestockCounter::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
