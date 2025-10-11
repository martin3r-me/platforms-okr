<?php

namespace Platform\Okr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Models\Team;

/**
 * Team Performance Model
 * 
 * Speichert tägliche Performance-Snapshots für Teams.
 * Ermöglicht schnelle Dashboard-Ladung und Trend-Analysen.
 * 
 * @hint Tägliche Snapshots werden via UpdateOkrPerformance Command berechnet
 * @hint Trends werden automatisch gegen vorherige Snapshots berechnet
 * @hint Optimiert für schnelle Dashboard-Performance
 */
class TeamPerformance extends Model
{
    protected $table = 'okr_team_performances';
    
    protected $fillable = [
        'team_id',
        'performance_date',
        'average_score',
        'total_okrs',
        'active_okrs',
        'successful_okrs',
        'draft_okrs',
        'completed_okrs',
        'total_objectives',
        'achieved_objectives',
        'total_key_results',
        'achieved_key_results',
        'open_key_results',
        'active_cycles',
        'current_cycles',
        'score_trend',
        'okr_trend',
        'achievement_trend',
    ];

    protected $casts = [
        'performance_date' => 'date',
        'average_score' => 'decimal:2',
        'score_trend' => 'decimal:2',
    ];

    // Beziehungen
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // Scopes
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('performance_date', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('performance_date', today());
    }

    // Helper Methods
    public function getPerformanceGradeAttribute(): string
    {
        if ($this->average_score >= 90) return 'A+';
        if ($this->average_score >= 80) return 'A';
        if ($this->average_score >= 70) return 'B';
        if ($this->average_score >= 60) return 'C';
        if ($this->average_score >= 50) return 'D';
        return 'F';
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_okrs === 0) return 0;
        return round(($this->successful_okrs / $this->total_okrs) * 100, 1);
    }

    public function getAchievementRateAttribute(): float
    {
        if ($this->total_objectives === 0) return 0;
        return round(($this->achieved_objectives / $this->total_objectives) * 100, 1);
    }

    public function getKeyResultCompletionRateAttribute(): float
    {
        if ($this->total_key_results === 0) return 0;
        return round(($this->achieved_key_results / $this->total_key_results) * 100, 1);
    }

    // Trend Indicators
    public function isScoreImproving(): bool
    {
        return $this->score_trend > 0;
    }

    public function isScoreDeclining(): bool
    {
        return $this->score_trend < 0;
    }

    public function isStable(): bool
    {
        return abs($this->score_trend) < 1;
    }
}
