<?php

namespace Platform\Okr\Models;

use Illuminate\Database\Eloquent\Builder;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

/**
 * OKR Model
 * 
 * Repräsentiert ein OKR (Objectives and Key Results) System.
 * 
 * @hint OKR ist das Hauptsystem für Zielsetzung und -verfolgung
 * @hint Jedes OKR hat mehrere Cycles (Zeiträume)
 * @hint OKRs können Templates sein für wiederkehrende Zyklen
 */
class Okr extends Model
{
    protected $table = 'okr_okrs';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'performance_score',
        'auto_transfer',
        'is_template',
        'team_id',
        'user_id',
        'manager_user_id',
    ];

    protected $casts = [
        'performance_score' => 'decimal:3',
        'auto_transfer' => 'boolean',
        'is_template' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $okr) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $okr->uuid = $uuid;

            if (empty($okr->user_id)) {
                $okr->user_id = Auth::id();
            }

            if (empty($okr->team_id)) {
                $okr->team_id = Auth::user()?->current_team_id;
            }
        });
    }

    /**
     * Scope: Team-Filter
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope: Sichtbar für User (owner/admin/manager/self/members)
     */
    public function scopeVisibleFor(Builder $query, User $user): Builder
    {
        $teamId = $user->current_team_id;
        $query->where('team_id', $teamId);

        // Owner/Admin sieht alles
        $isOwner = method_exists($user, 'isTeamOwner') && $user->isTeamOwner($teamId);
        $isAdmin = method_exists($user, 'hasTeamRole') && $user->hasTeamRole('admin', $teamId);
        if ($isOwner || $isAdmin) {
            return $query;
        }

        // manager/self oder members
        return $query->where(function (Builder $q) use ($user) {
            $q->where('manager_user_id', $user->id)
              ->orWhere('user_id', $user->id)
              ->orWhereHas('members', fn (Builder $m) => $m->where('users.id', $user->id));
        });
    }

    // 🧩 Beziehungen

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function managerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(Objective::class);
    }

    public function keyResults(): HasManyThrough
    {
        return $this->hasManyThrough(
            KeyResult::class,
            Objective::class,
            'okr_id',
            'objective_id',
            'id',
            'id'
        );
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'okr_okr_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Scope für sichtbare OKRs für einen User
     */
    public function scopeVisibleFor($query, User $user)
    {
        return $query->where('team_id', $user->current_team_id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('manager_user_id', $user->id)
                  ->orWhereHas('members', function ($m) use ($user) {
                      $m->where('users.id', $user->id);
                  });
            });
    }
}
