<?php

namespace Platform\Okr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;

class ObjectivePerformance extends Model
{
    use HasFactory;

    protected $table = 'okr_objective_performances';

    protected $fillable = [
        'objective_id',
        'team_id',
        'user_id',
        'performance_date',
        'performance_score',
        'completion_percentage',
        'completed_key_results',
        'total_key_results',
        'average_progress',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'performance_score' => 'decimal:2',
        'completion_percentage' => 'decimal:2',
        'average_progress' => 'decimal:2',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function objective(): BelongsTo
    {
        return $this->belongsTo(Objective::class);
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
