<?php

namespace Platform\Okr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Uid\UuidV7;

class CycleTemplate extends Model
{
    protected $table = 'okr_cycle_templates';
    protected $fillable = [
        'uuid',
        'label',
        'starts_at',
        'ends_at',
        'type',
        'sort_index',
        'is_standard',
        'is_current',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_standard' => 'boolean',
        'is_current' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $template) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $template->uuid = $uuid;
        });
    }
}
