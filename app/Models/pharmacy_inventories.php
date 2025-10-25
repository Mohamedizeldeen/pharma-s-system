<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pharmacy_inventories extends Model
{
    protected $fillable = [
        'pharmacy_id',
        'medicine_id',
        'branch_id',
        'price',
        'quantity',
        'status',
    ];

    public function branch()
    {
        return $this->belongsTo(branch::class);
    }

    public function medicine()
    {
        return $this->belongsTo(medicines::class);
    }
    public function pharmacy()
    {
        return $this->belongsTo(pharma::class);
    }
}
