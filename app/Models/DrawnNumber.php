<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrawnNumber extends Model
{
    use HasFactory;

    protected $fillable = ['numbers'];

    protected $casts = [
        'numbers' => 'array',
    ];
}
