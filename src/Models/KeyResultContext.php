<?php

namespace Platform\Okr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeyResultContext extends Model
{
    protected $table = 'okr_key_result_contexts';

    protected $fillable = [
        'key_result_id',
        'context_type',
        'context_id',
        'depth',
        'is_primary',
        'is_root',
        'root_context_type',
        'root_context_id',
        'context_label',
    ];

    protected $casts = [
        'depth' => 'integer',
        'is_primary' => 'boolean',
        'is_root' => 'boolean',
    ];

    public function keyResult(): BelongsTo
    {
        return $this->belongsTo(KeyResult::class, 'key_result_id');
    }

    public function context(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function rootContext(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('rootContext', 'root_context_type', 'root_context_id');
    }
}

