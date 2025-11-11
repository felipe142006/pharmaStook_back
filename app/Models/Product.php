<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku','name','description','stock','min_stock','cost','price','expires_at','is_active','created_by','updated_by'
    ];

    protected $casts = [
        'expires_at' => 'date',
        'is_active'  => 'boolean',
    ];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
