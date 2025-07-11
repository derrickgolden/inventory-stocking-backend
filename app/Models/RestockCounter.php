<?php

namespace App\Models;

use App\Models\Users;
use App\Models\Counter;
use App\Models\RestockCounterItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestockCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'counter_id',
        'total_amount',
        'total_quantity',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function items()
    {
        return $this->hasMany(RestockCounterItem::class);
    }
}
