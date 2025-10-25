<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class order_item extends Model
{
    protected $fillable = [
        'medicine_id',
        'quantity',
        'price',
    ];

    public function order()
    {
        return $this->belongsTo(order::class);
    }

    public function medicine()
    {
        return $this->belongsTo(medicines::class);
    }
}
