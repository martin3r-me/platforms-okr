<?php

namespace Platform\Okr\Services;

use Platform\Core\Contracts\HasKeyResultAncestors;

class KeyResultContextResolver
{
    /**
     * Lädt das Modell und gibt dessen Vorfahren zurück.
     *
     * @param string $type Model-Klasse
     * @param int $id Model-ID
     * @return array Array von Vorfahren-Kontexten
     */
    public function resolveAncestors(string $type, int $id): array
    {
        if (! class_exists($type)) {
            return [];
        }

        $model = $type::find($id);

        if (! $model) {
            return [];
        }

        if (! $model instanceof HasKeyResultAncestors) {
            return [];
        }

        return $model->keyResultAncestors();
    }

    /**
     * Erstellt einen Kontext-Label aus dem Modell.
     *
     * @param string $type Model-Klasse
     * @param int $id Model-ID
     * @return string|null
     */
    public function resolveLabel(string $type, int $id): ?string
    {
        if (! class_exists($type)) {
            return null;
        }

        $model = $type::find($id);

        if (! $model) {
            return null;
        }

        // Prüfe ob Model HasDisplayName Interface implementiert (loose coupling)
        if ($model instanceof \Platform\Core\Contracts\HasDisplayName) {
            return $model->getDisplayName();
        }

        // Fallback: Versuche verschiedene Label-Felder
        if (isset($model->name)) {
            return $model->name;
        }

        if (isset($model->title)) {
            return $model->title;
        }

        if (method_exists($model, '__toString')) {
            return (string) $model;
        }

        return null;
    }

    /**
     * Gibt den Namen/Titel eines Root-Kontexts zurück.
     * 
     * @param string|null $type Model-Klasse
     * @param int|null $id Model-ID
     * @return string|null
     */
    public function resolveRootName(?string $type, ?int $id): ?string
    {
        if (!$type || !$id) {
            return null;
        }

        return $this->resolveLabel($type, $id);
    }
}

