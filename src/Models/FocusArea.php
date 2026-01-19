<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\Core\Contracts\HasDisplayName;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

/**
 * FocusArea Model
 * 
 * Represents a focus area within a forecast.
 * 
 * @hint Focus Areas are separate entities, not related to OKRs
 * @hint Focus Areas belong to a Forecast
 */
class FocusArea extends Model implements HasDisplayName
{
    protected $table = 'okr_focus_areas';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'forecast_id',
        'title',
        'description',
        'content',
        'central_question_vision_images',
        'central_question_obstacles',
        'central_question_milestones',
        'order',
        'team_id',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $focusArea) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $focusArea->uuid = $uuid;

            if (empty($focusArea->user_id)) {
                $focusArea->user_id = Auth::id();
            }

            if (empty($focusArea->team_id)) {
                // For Parent Tools (scope_type = 'parent') automatically use Root-Team
                $user = Auth::user();
                $baseTeam = $user?->currentTeamRelation ?? $user?->currentTeam ?? null;
                
                if ($baseTeam) {
                    $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
                    $focusArea->team_id = ($okrModule && method_exists($okrModule, 'isRootScoped') && $okrModule->isRootScoped()) 
                        ? ($baseTeam->getRootTeam()->id ?? $baseTeam->id)
                        : $baseTeam->id;
                }
            }
        });
    }

    /**
     * Returns the display name of the focus area.
     */
    public function getDisplayName(): ?string
    {
        return $this->title;
    }

    // 🔗 Relationships

    public function forecast(): BelongsTo
    {
        return $this->belongsTo(Forecast::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visionImages(): HasMany
    {
        return $this->hasMany(VisionImage::class)->orderBy('order');
    }

    public function obstacles(): HasMany
    {
        return $this->hasMany(Obstacle::class)->orderBy('order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('order');
    }
}
