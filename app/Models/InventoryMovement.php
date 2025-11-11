<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    protected $fillable = ['product_id','type','quantity','reason','reference_type','reference_id','user_id','balance_after'];

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
