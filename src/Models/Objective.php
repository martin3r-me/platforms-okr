<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

/**
 * OKR Objective Model
 * 
 * Repräsentiert ein Hauptziel (Objective) in einem OKR-Zyklus.
 * 
 * @hint Objectives sind die Hauptziele, die erreicht werden sollen
 * @hint Jeder Objective hat mehrere Key Results als messbare Ergebnisse
 * @hint Objectives können "Mountain" (ambitionierte Ziele) sein
 */
class Objective extends Model
{
    protected $table = 'okr_objectives';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'okr_id',
        'cycle_id',
        'team_id',
        'user_id',
        'manager_user_id',
        'title',
        'description',
        'is_mountain',
        'performance_score',
        'order',
        'vision_id',
        'regnose_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $objective) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $objective->uuid = $uuid;

            if (empty($objective->user_id)) {
                $objective->user_id = Auth::id();
            }

            if (empty($objective->team_id)) {
                // Für Parent Tools (scope_type = 'parent') wird automatisch das Root-Team verwendet
                $objective->team_id = Auth::user()?->currentTeam?->id;
            }
        });
    }

    /** Beziehungen */

    public function okr(): BelongsTo
    {
        return $this->belongsTo(Okr::class);
    }

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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(KeyResult::class)->orderBy('order');
    }

    public function performances(): HasMany
    {
        return $this->hasMany(ObjectivePerformance::class);
    }

    public function performance(): HasOne
    {
        return $this->hasOne(ObjectivePerformance::class)->latest();
    }

    /**
     * Vision, die dieses Objective referenziert (optional)
     */
    public function vision(): BelongsTo
    {
        return $this->belongsTo(StrategicDocument::class, 'vision_id');
    }

    /**
     * Regnose, die dieses Objective referenziert (optional)
     */
    public function regnose(): BelongsTo
    {
        return $this->belongsTo(StrategicDocument::class, 'regnose_id');
    }
}
