<?php

namespace Platform\Okr\Models;

use Platform\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

/**
 * ForecastVersion Model
 * 
 * Represents a version of the forecast content.
 */
class ForecastVersion extends Model
{
    protected $table = 'okr_forecast_versions';

    protected $fillable = [
        'uuid',
        'forecast_id',
        'version',
        'content',
        'change_note',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $version) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $version->uuid = $uuid;
        });
    }

    // 🔗 Relationships

    public function forecast(): BelongsTo
    {
        return $this->belongsTo(Forecast::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
