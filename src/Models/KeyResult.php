<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

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
                $kr->team_id = Auth::user()?->current_team_id;
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
        return $this->hasOne(KeyResultPerformance::class);
    }
}
