<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class branch extends Model
{
    protected $fillable = [
        'pharma_id',
        'name',
        'address',
        'phone',
        'latitude',
        'longitude',
        'opening_hours',
        'closing_hours',
    ];

    public function pharma()
    {
        return $this->belongsTo(pharma::class);
    }
}
