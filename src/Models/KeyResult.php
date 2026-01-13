<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;
use Platform\Okr\Models\KeyResultContext;

/**
 * OKR Key Result Model
 * 
 * Repräsentiert ein messbares Ergebnis (Key Result) für ein Objective.
 * 
 * @hint Key Results sind messbare Ergebnisse, die ein Objective erreichen
 * @hint Jeder Key Result hat Performance-Daten (Zielwert, aktueller Wert)
 * @hint Key Results können verschiedene Typen haben: absolute, percentage, boolean
 */
class KeyResult extends Model
{
    protected $table = 'okr_key_results';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'objective_id',
        'team_id',
        'user_id',
        'manager_user_id',
        'title',
        'description',
        'performance_score',
        'order',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $kr) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $kr->uuid = $uuid;

            if (empty($kr->user_id)) {
                $kr->user_id = Auth::id();
            }

            if (empty($kr->team_id)) {
                // Für Parent Tools (scope_type = 'parent') wird automatisch das Root-Team verwendet
                $user = Auth::user();
                $baseTeam = $user?->currentTeamRelation ?? $user?->currentTeam ?? null;
                
                if ($baseTeam) {
                    $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
                    $kr->team_id = ($okrModule && method_exists($okrModule, 'isRootScoped') && $okrModule->isRootScoped()) 
                        ? ($baseTeam->getRootTeam()->id ?? $baseTeam->id)
                        : $baseTeam->id;
                }
            }
        });
    }

    /** Beziehungen */

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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function performance(): HasOne
    {
        return $this->hasOne(KeyResultPerformance::class)->latestOfMany();
    }

    public function performances(): HasMany
    {
        return $this->hasMany(KeyResultPerformance::class);
    }

    public function contexts(): HasMany
    {
        return $this->hasMany(KeyResultContext::class, 'key_result_id');
    }

    public function primaryContexts(): HasMany
    {
        return $this->hasMany(KeyResultContext::class, 'key_result_id')
            ->where('is_primary', true);
    }
}
