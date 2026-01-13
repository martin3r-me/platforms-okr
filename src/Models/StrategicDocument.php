<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\Team;
use Platform\Core\Models\User;
use Platform\ActivityLog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\UuidV7;

/**
 * Strategic Document Model
 * 
 * Repräsentiert strategische Dokumente: Mission, Vision oder Regnose.
 * 
 * @hint Mission: Warum die Organisation existiert (zeitlich stabil)
 * @hint Vision: Gewollter Zukunftszustand (5-10 Jahre)
 * @hint Regnose: Erwartete Entwicklungen (Annahmenbasiert)
 * @hint Alle Dokumente sind versionierbar, genau eine Version ist aktiv
 */
class StrategicDocument extends Model
{
    protected $table = 'okr_strategic_documents';
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'type',
        'title',
        'content',
        'version',
        'is_active',
        'valid_from',
        'change_note',
        'team_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $document->uuid = $uuid;

            if (empty($document->created_by)) {
                $document->created_by = Auth::id();
            }

            if (empty($document->team_id)) {
                // Für Parent Tools (scope_type = 'parent') wird automatisch das Root-Team verwendet
                $user = Auth::user();
                $baseTeam = $user?->currentTeamRelation ?? $user?->currentTeam ?? null;
                
                if ($baseTeam) {
                    $okrModule = \Platform\Core\Models\Module::where('key', 'okr')->first();
                    $document->team_id = ($okrModule && method_exists($okrModule, 'isRootScoped') && $okrModule->isRootScoped()) 
                        ? ($baseTeam->getRootTeam()->id ?? $baseTeam->id)
                        : $baseTeam->id;
                }
            }

            // Wenn is_active = true, setze alle anderen Dokumente des gleichen Typs auf inaktiv
            if ($document->is_active) {
                self::where('type', $document->type)
                    ->where('team_id', $document->team_id)
                    ->where('id', '!=', $document->id ?? 0)
                    ->update(['is_active' => false]);
            }

            // Automatische Versionsnummer: Höchste Version + 1 für diesen Typ und Team
            if (empty($document->version)) {
                $maxVersion = self::where('type', $document->type)
                    ->where('team_id', $document->team_id)
                    ->max('version') ?? 0;
                $document->version = $maxVersion + 1;
            }
        });

        static::updating(function (self $document) {
            // Wenn is_active auf true gesetzt wird, setze alle anderen auf inaktiv
            if ($document->isDirty('is_active') && $document->is_active) {
                self::where('type', $document->type)
                    ->where('team_id', $document->team_id)
                    ->where('id', '!=', $document->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * Scope: Aktive Dokumente eines Typs
     */
    public function scopeActive($query, string $type = null)
    {
        $query->where('is_active', true);
        if ($type) {
            $query->where('type', $type);
        }
        return $query;
    }

    /**
     * Scope: Dokumente eines bestimmten Typs
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Für Team
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Erstellt eine neue Version dieses Dokuments
     * 
     * @param array $attributes Neue Attribute für die neue Version
     * @return self Die neue Version
     */
    public function createNewVersion(array $attributes = []): self
    {
        return DB::transaction(function () use ($attributes) {
            // Setze aktuelle Version auf inaktiv
            $this->update(['is_active' => false]);

            // Erstelle neue Version
            $newVersion = self::create(array_merge([
                'type' => $this->type,
                'team_id' => $this->team_id,
                'title' => $this->title,
                'content' => $this->content,
                'version' => $this->version + 1,
                'is_active' => true,
                'valid_from' => now()->toDateString(),
            ], $attributes));

            return $newVersion;
        });
    }

    /**
     * Gibt alle Versionen dieses Dokuments zurück (gleicher Typ, gleiches Team)
     */
    public function allVersions()
    {
        return self::where('type', $this->type)
            ->where('team_id', $this->team_id)
            ->orderBy('version', 'desc')
            ->get();
    }

    // 🔗 Beziehungen

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Objectives, die diese Vision referenzieren
     */
    public function visionObjectives(): HasMany
    {
        return $this->hasMany(Objective::class, 'vision_id');
    }

    /**
     * Objectives, die diese Regnose referenzieren
     */
    public function regnoseObjectives(): HasMany
    {
        return $this->hasMany(Objective::class, 'regnose_id');
    }
}

