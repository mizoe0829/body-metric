<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_metric_id',
        'exercise_name',
        'target_muscle',
        'weight',
        'reps',
        'sets',
        'rpe',
        'notes',
    ];

    protected $casts = [
        'weight' => 'float',
        'reps' => 'integer',
        'sets' => 'integer',
        'rpe' => 'float',
    ];

    public function dailyMetric(): BelongsTo
    {
        return $this->belongsTo(DailyMetric::class);
    }
}

