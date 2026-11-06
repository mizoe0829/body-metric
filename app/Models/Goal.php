<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'height',
        'age',
        'gender',
        'workout_environment',
        'target_weight',
        'target_body_fat',
        'start_weight',
        'start_body_fat',
        'target_date',
        'objective',
        'activity_level',
        'is_active',
    ];

    protected $casts = [
        'height' => 'float',
        'age' => 'integer',
        'target_weight' => 'float',
        'target_body_fat' => 'float',
        'start_weight' => 'float',
        'start_body_fat' => 'float',
        'target_date' => 'date',
        'is_active' => 'boolean',
    ];
}
