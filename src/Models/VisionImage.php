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
 * VisionImage Model
 * 
 * Represents a vision image (Zielbild) within a focus area.
 */
class VisionImage extends Model implements HasDisplayName
{
    protected $table = 'okr_vision_images';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'focus_area_id',
        'title',
        'description',
        'order',
        'team_id',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $visionImage) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $visionImage->uuid = $uuid;

            if (empty($visionImage->user_id)) {
                $visionImage->user_id = Auth::id();
            }

            if (empty($visionImage->team_id)) {
                $user = Auth::user();
                $baseTeam = $user?->currentTeamRelation ?? $user?->currentTeam ?? null;
                
                if ($baseTeam) {
                    $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
                    $visionImage->team_id = ($okrModule && method_exists($okrModule, 'isRootScoped') && $okrModule->isRootScoped()) 
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
