<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Counter extends Model
{
    use HasFactory;
    protected $table = 'counter';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'staffId',
        'counterThumbnailImage',
        'description',
    ];

    public function users(): BelongsTo
    {
        return $this->belongsTo(Users::class, 'staffId');
    }

}
