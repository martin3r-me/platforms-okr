<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;
class Okr extends Model
{
    protected $table = 'okr_okrs';
    use SoftDeletes;

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
}
