<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_date',
        'weight',
        'body_fat',
        'raw_input',
        'memo',
        'ai_feedback',
    ];

    protected $casts = [
        'record_date' => 'date:Y-m-d',
        'weight' => 'float',
        'body_fat' => 'float',
        'ai_feedback' => 'array',
    ];

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }
}

