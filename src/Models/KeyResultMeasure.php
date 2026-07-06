<?php

namespace Platform\Okr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

/**
 * Eine Messgröße eines Key Results.
 *
 * Bindet eine Provider-Metrik (metric_key + selector) mit Rolle, Ziel, Baseline
 * und Gewicht. Rollen (offenes Enum):
 *   - score : gewichteter Beitrag zur Erreichungsquote
 *   - gate  : Pass/Fail, blockt "erreicht", verwässert die Quote nicht
 *   - cap   : deckelt die Quote (min)
 *   - info  : nur Anzeige, nicht bewertet
 */
class KeyResultMeasure extends Model
{
    use SoftDeletes;

    protected $table = 'okr_key_result_measures';

    public const ROLE_SCORE = 'score';
    public const ROLE_GATE  = 'gate';
    public const ROLE_CAP   = 'cap';
    public const ROLE_INFO  = 'info';

    protected $fillable = [
        'uuid',
        'key_result_id',
        'key_result_context_id',
        'metric_key',
        'selector',
        'binding',
        'role',
        'value_type',
        'polarity',
        'target_value',
        'baseline_value',
        'weight',
        'window_mode',
        'current_value',
        'achievement',
        'is_available',
        'last_synced_at',
        'label',
        'order',
        'team_id',
        'user_id',
    ];

    protected $casts = [
        'selector' => 'array',
        'target_value' => 'decimal:4',
        'baseline_value' => 'decimal:4',
        'weight' => 'decimal:2',
        'current_value' => 'decimal:4',
        'achievement' => 'decimal:3',
        'is_available' => 'boolean',
        'last_synced_at' => 'datetime',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $measure) {
            if (empty($measure->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $measure->uuid = $uuid;
            }

            if (empty($measure->user_id)) {
                $measure->user_id = Auth::id();
            }
        });
    }

    public function keyResult(): BelongsTo
    {
        return $this->belongsTo(KeyResult::class, 'key_result_id');
    }

    public function context(): BelongsTo
    {
        return $this->belongsTo(KeyResultContext::class, 'key_result_context_id');
    }

    public function isManual(): bool
    {
        return $this->metric_key === 'manual';
    }
}
