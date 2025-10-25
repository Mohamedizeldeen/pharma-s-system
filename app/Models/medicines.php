<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class medicines extends Model
{
    protected $fillable = [
        'branch_id',
        'pharma_id',
        'name',
        'description',
        'price',
        'image',
        'quantity',
        'scientific_name',
        'expiry_date',
    ];

    public function branch()
    {
        return $this->belongsTo(branch::class);
    }
    public function pharma()
    {
        return $this->belongsTo(pharma::class);
    }
}
