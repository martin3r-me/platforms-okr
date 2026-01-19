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
 * Forecast Model
 * 
 * Represents a strategic forecast with versionable content.
 * 
 * @hint Forecasts come from strategic documents
 * @hint Forecasts belong to a team and have a target date
 * @hint The content is versionable
 * @hint Focus Areas can belong to a forecast
 */
class Forecast extends Model implements HasDisplayName
{
    protected $table = 'okr_forecasts';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'title',
        'target_date',
        'content',
        'team_id',
        'user_id',
        'current_version_id',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $forecast) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $forecast->uuid = $uuid;

            if (empty($forecast->user_id)) {
                $forecast->user_id = Auth::id();
            }

            if (empty($forecast->team_id)) {
                // For Parent Tools (scope_type = 'parent') automatically use Root-Team
                $user = Auth::user();
                $baseTeam = $user?->currentTeamRelation ?? $user?->currentTeam ?? null;
                
                if ($baseTeam) {
                    $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
                    $forecast->team_id = ($okrModule && method_exists($okrModule, 'isRootScoped') && $okrModule->isRootScoped()) 
                        ? ($baseTeam->getRootTeam()->id ?? $baseTeam->id)
                        : $baseTeam->id;
                }
            }
        });
    }

    /**
     * Scope: Team-Filter
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Returns the display name of the forecast.
     */
    public function getDisplayName(): ?string
    {
        return $this->title;
    }

    // 🔗 Relationships

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ForecastVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ForecastVersion::class)->orderBy('version', 'desc');
    }

    /**
     * Focus Areas that belong to this forecast
     */
    public function focusAreas(): HasMany
    {
        return $this->hasMany(FocusArea::class)->orderBy('order');
    }

    /**
     * Creates a new version of the content
     */
    public function createNewVersion(string $content, ?string $changeNote = null): ForecastVersion
    {
        $nextVersion = $this->versions()->max('version') ?? 0;
        $nextVersion++;

        $version = $this->versions()->create([
            'content' => $content,
            'version' => $nextVersion,
            'change_note' => $changeNote,
            'user_id' => Auth::id(),
        ]);

        // Update current content and version
        $this->update([
            'content' => $content,
            'current_version_id' => $version->id,
        ]);

        return $version;
    }
}
