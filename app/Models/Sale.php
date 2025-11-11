<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number','customer_id','user_id','status',
        'subtotal','discount','tax','total','issued_at','printed_at'
    ];

    protected $casts = [
        'issued_at'  => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
