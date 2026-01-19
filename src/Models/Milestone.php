<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Core\Contracts\HasDisplayName;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

/**
 * Milestone Model
 * 
 * Represents a milestone (Meilenstein) within a focus area.
 */
class Milestone extends Model implements HasDisplayName
{
    protected $table = 'okr_milestones';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'focus_area_id',
        'title',
        'description',
        'target_date',
        'order',
        'team_id',
        'user_id',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $milestone) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $milestone->uuid = $uuid;

            if (empty($milestone->user_id)) {
                $milestone->user_id = Auth::id();
            }

            if (empty($milestone->team_id)) {
                $user = Auth::user();
                $baseTeam = $user?->currentTeamRelation ?? $user?->currentTeam ?? null;
                
                if ($baseTeam) {
                    $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
                    $milestone->team_id = ($okrModule && method_exists($okrModule, 'isRootScoped') && $okrModule->isRootScoped()) 
                        ? ($baseTeam->getRootTeam()->id ?? $baseTeam->id)
                        : $baseTeam->id;
                }
            }
        });
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }

    // 🔗 Relationships

    public function focusArea(): BelongsTo
    {
        return $this->belongsTo(FocusArea::class);
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
