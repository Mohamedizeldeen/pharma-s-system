<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pharma extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'main_address',
        'phone',
    ];
    public function branches()
    {
        return $this->hasMany(branch::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
