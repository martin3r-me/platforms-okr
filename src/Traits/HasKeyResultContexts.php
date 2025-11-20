<?php

namespace Platform\Okr\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Platform\Okr\Models\KeyResultContext;

trait HasKeyResultContexts
{
    /**
     * Gibt alle KeyResults zurück, die mit diesem Kontext verknüpft sind.
     */
    public function keyResultContexts(): MorphMany
    {
        return $this->morphMany(KeyResultContext::class, 'context');
    }

    /**
     * Gibt die Anzahl der verknüpften KeyResults zurück.
     */
    public function keyResultContextsCount(): int
    {
        return (int) $this->keyResultContexts()->count();
    }
}

