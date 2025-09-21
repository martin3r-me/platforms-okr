<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

class KeyResultPerformance extends Model
{
    protected $table = 'okr_key_result_performances';
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'key_result_id',
        'team_id',
        'type',
        'is_completed',
        'current_value',
        'target_value',
        'calculated_value',
        'performance_score',
        'tendency',
        'okr_status_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $performance) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $performance->uuid = $uuid;

            if (empty($performance->team_id)) {
                $performance->team_id = Auth::user()?->current_team_id;
            }
        });
    }

    /** Beziehungen */

    public function keyResult(): BelongsTo
    {
        return $this->belongsTo(KeyResult::class, 'key_result_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** Hilfsmethoden */

    public function isBooleanType(): bool
    {
        return $this->type === 'boolean';
    }

    public function isPercentageType(): bool
    {
        return $this->type === 'percentage';
    }

    public function isAbsoluteType(): bool
    {
        return $this->type === 'absolute';
    }

    public function isCalculatedType(): bool
    {
        return $this->type === 'calculated';
    }
}
