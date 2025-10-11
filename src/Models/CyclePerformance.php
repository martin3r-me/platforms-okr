<?php

namespace Platform\Okr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;

class CyclePerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'team_id',
        'user_id',
        'performance_score',
        'completion_percentage',
        'completed_objectives',
        'total_objectives',
        'completed_key_results',
        'total_key_results',
        'average_objective_progress',
        'average_key_result_progress',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'performance_score' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'average_objective_progress' => 'decimal:2',
        'average_key_result_progress' => 'decimal:2',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
