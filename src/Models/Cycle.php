<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

/**
 * OKR Cycle Model
 * 
 * Repräsentiert einen OKR-Zyklus mit Objectives und Key Results.
 * 
 * @hint OKR-Zyklen sind Zeiträume für Zielsetzung und -verfolgung
 * @hint Jeder Cycle gehört zu einem OKR und hat mehrere Objectives
 * @hint Cycles haben Status: draft, active, completed, ending_soon, past
 */
class Cycle extends Model
{
    protected $table = 'okr_cycles';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'okr_id',
        'team_id',
        'user_id',
        'label',
        'type',
        'status',
        'notes',
        'description',
        'cycle_template_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $cycle) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $cycle->uuid = $uuid;

            if (empty($cycle->user_id)) {
                $cycle->user_id = Auth::id();
            }

            if (empty($cycle->team_id)) {
                $cycle->team_id = Auth::user()?->current_team_id;
            }
        });
    }

    public function getStartsAtAttribute()
    {
        return $this->template?->starts_at;
    }

    public function getEndsAtAttribute()
    {
        return $this->template?->ends_at;
    }

    public function getLabelAttribute()
    {
        return $this->template?->label;
    }

    // 🔗 Beziehungen

    public function template(): BelongsTo
    {
        return $this->belongsTo(CycleTemplate::class, 'cycle_template_id');
    }

    public function okr(): BelongsTo
    {
        return $this->belongsTo(Okr::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alle Objectives in diesem Cycle
     * 
     * @hint Objectives sind die Hauptziele des OKR-Zyklus
     * @hint Jeder Objective hat mehrere Key Results
     */
    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class)->orderBy('order');
    }

    public function performances(): HasMany
    {
        return $this->hasMany(CyclePerformance::class);
    }

    public function performance(): HasOne
    {
        return $this->hasOne(CyclePerformance::class)->latest();
    }

    public function keyResults(): HasManyThrough
    {
        return $this->hasManyThrough(
            KeyResult::class,
            Objective::class,
            'cycle_id',       // Foreign key on Objective
            'objective_id',   // Foreign key on KeyResult
            'id',             // Local key on Cycle
            'id'              // Local key on Objective
        )->join('okr_objectives', 'okr_objectives.id', '=', 'okr_key_results.objective_id')
         ->orderBy('okr_objectives.order')
         ->orderBy('okr_key_results.order')
         ->select('okr_key_results.*'); // wichtig: damit du nicht auch alle Objective-Spalten bekommst
    }
}
