<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    public $timestamps = false;

    protected $fillable = ['area_id', 'name'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
