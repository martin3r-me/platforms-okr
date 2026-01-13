<?php

namespace Platform\Okr\Tools;

use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\KeyResultContext;
use Platform\Okr\Services\KeyResultContextResolver;
use Platform\Okr\Tools\Concerns\ResolvesOkrScope;

class GetKeyResultContextsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesOkrScope;

    protected ?KeyResultContextResolver $resolver = null;

    public function __construct(?KeyResultContextResolver $resolver = null)
    {
        $this->resolver = $resolver;
    }

    protected function getResolver(): KeyResultContextResolver
    {
        if ($this->resolver === null) {
            $this->resolver = app(KeyResultContextResolver::class);
        }
        return $this->resolver;
    }

    public function getName(): string
    {
        return 'okr.key_result.contexts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /okr/key-results/{id}/contexts - Findet alle verknüpften Kontexte zu einem KeyResult (Tasks, Projects, Notes, Meetings, etc.). Gibt gruppierte Ergebnisse nach Entitätstyp zurück.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'key_result_id' => [
                    'type' => 'integer',
                    'description' => 'KeyResult-ID (required).',
                ],
                'context_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Filter auf einen spezifischen Kontext-Typ (z.B. "Platform\\Planner\\Models\\PlannerTask").',
                ],
                'only_primary' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Nur primäre Verknüpfungen anzeigen (default: true). Wenn false, werden auch Ancestor-Kontexte angezeigt.',
                    'default' => true,
                ],
            ],
            'required' => ['key_result_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $keyResultId = $this->normalizeId($arguments['key_result_id'] ?? null);
            if (!$keyResultId) {
                return ToolResult::error('VALIDATION_ERROR', 'key_result_id ist erforderlich.');
            }

            $teamId = $this->resolveOkrTeamId($context);
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext gefunden (OKR ist root-scoped).');
            }

            // Prüfe ob KeyResult existiert und zum Team gehört
            $keyResult = KeyResult::where('team_id', $teamId)
                ->with(['objective.cycle.okr'])
                ->find($keyResultId);
            
            if (!$keyResult) {
                return ToolResult::error('NOT_FOUND', "Key Result {$keyResultId} nicht gefunden (Team-ID: {$teamId}).");
            }

            $contextType = $arguments['context_type'] ?? null;
            $onlyPrimary = (bool) ($arguments['only_primary'] ?? true);

            // Lade alle Kontexte für dieses KeyResult
            $query = KeyResultContext::where('key_result_id', $keyResultId);
            
            if ($contextType) {
                $query->where('context_type', $contextType);
            }
            
            if ($onlyPrimary) {
                $query->where('is_primary', true);
            }

            $contexts = $query->get();

            // Gruppiere nach Kontext-Typ und lade die tatsächlichen Modelle
            $grouped = [];
            $summary = [];

            foreach ($contexts as $contextRecord) {
                $type = $contextRecord->context_type;
                
                if (!class_exists($type)) {
                    continue;
                }

                // Lade das tatsächliche Modell
                $model = $type::find($contextRecord->context_id);
                if (!$model) {
                    continue;
                }

                // Erstelle einen lesbaren Typ-Namen (z.B. "Platform\Planner\Models\PlannerTask" -> "task")
                $typeKey = $this->getTypeKey($type);
                
                if (!isset($grouped[$typeKey])) {
                    $grouped[$typeKey] = [];
                    $summary[$typeKey] = [
                        'type' => $type,
                        'type_key' => $typeKey,
                        'count' => 0,
                    ];
                }

                // Hole Display-Name
                $displayName = null;
                if ($model instanceof HasDisplayName) {
                    $displayName = $model->getDisplayName();
                } else {
                    $displayName = $this->getResolver()->resolveLabel($type, $contextRecord->context_id);
                }

                // Basis-Daten für alle Entitäten
                $item = [
                    'id' => $model->id,
                    'display_name' => $displayName,
                    'context_id' => $contextRecord->context_id,
                    'context_type' => $type,
                    'is_primary' => (bool) $contextRecord->is_primary,
                    'is_root' => (bool) $contextRecord->is_root,
                    'depth' => $contextRecord->depth,
                    'context_label' => $contextRecord->context_label,
                ];

                // Füge typspezifische Daten hinzu
                $item = array_merge($item, $this->getTypeSpecificData($model, $type));

                $grouped[$typeKey][] = $item;
                $summary[$typeKey]['count']++;
            }

            // Sortiere nach Typ-Key für konsistente Ausgabe
            ksort($grouped);
            ksort($summary);

            return ToolResult::success([
                'key_result' => [
                    'id' => $keyResult->id,
                    'title' => $keyResult->title,
                    'objective' => $keyResult->objective ? [
                        'id' => $keyResult->objective->id,
                        'title' => $keyResult->objective->title,
                    ] : null,
                ],
                'summary' => array_values($summary),
                'contexts' => $grouped,
                'total_count' => $contexts->count(),
                'message' => $this->buildMessage($summary),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der KeyResult-Kontexte: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt einen lesbaren Typ-Schlüssel aus dem vollständigen Klassennamen.
     */
    protected function getTypeKey(string $type): string
    {
        // Extrahiere den letzten Teil des Namespace (z.B. "PlannerTask" aus "Platform\Planner\Models\PlannerTask")
        $parts = explode('\\', $type);
        $className = end($parts);
        
        // Entferne Präfixe wie "Planner", "Notes", etc. und konvertiere zu snake_case
        $className = preg_replace('/^(Planner|Notes|Meeting|Brand|Appointment)/', '', $className);
        
        // Konvertiere zu snake_case und lowercase
        $key = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($className)));
        
        return trim($key, '_');
    }

    /**
     * Gibt typspezifische Daten für ein Modell zurück.
     */
    protected function getTypeSpecificData($model, string $type): array
    {
        $data = [];

        // Gemeinsame Felder, die viele Models haben
        if (isset($model->title)) {
            $data['title'] = $model->title;
        }
        if (isset($model->name)) {
            $data['name'] = $model->name;
        }
        if (isset($model->description)) {
            $data['description'] = $model->description;
        }
        if (isset($model->status)) {
            $data['status'] = $model->status;
        }

        // Typspezifische Felder
        if (str_contains($type, 'PlannerTask')) {
            if (isset($model->project_id)) {
                $data['project_id'] = $model->project_id;
            }
            if (isset($model->due_date)) {
                $data['due_date'] = $model->due_date?->toIso8601String();
            }
        } elseif (str_contains($type, 'PlannerProject')) {
            if (isset($model->start_date)) {
                $data['start_date'] = $model->start_date?->toIso8601String();
            }
            if (isset($model->end_date)) {
                $data['end_date'] = $model->end_date?->toIso8601String();
            }
        } elseif (str_contains($type, 'NotesNote')) {
            if (isset($model->folder_id)) {
                $data['folder_id'] = $model->folder_id;
            }
        } elseif (str_contains($type, 'Meeting')) {
            if (isset($model->start_at)) {
                $data['start_at'] = $model->start_at?->toIso8601String();
            }
        }

        return $data;
    }

    /**
     * Erstellt eine menschenlesbare Zusammenfassung.
     */
    protected function buildMessage(array $summary): string
    {
        if (empty($summary)) {
            return 'Keine verknüpften Kontexte gefunden.';
        }

        $parts = [];
        foreach ($summary as $typeKey => $info) {
            $count = $info['count'];
            $label = $this->getTypeLabel($typeKey);
            $parts[] = "{$count} {$label}";
        }

        return 'Gefunden: ' . implode(', ', $parts) . '.';
    }

    /**
     * Gibt ein lesbares Label für einen Typ-Schlüssel zurück.
     */
    protected function getTypeLabel(string $typeKey): string
    {
        $labels = [
            'task' => 'Aufgabe(n)',
            'project' => 'Projekt(e)',
            'note' => 'Notiz(en)',
            'folder' => 'Ordner',
            'meeting' => 'Meeting(s)',
            'appointment' => 'Termin(e)',
            'brand' => 'Marke(n)',
        ];

        return $labels[$typeKey] ?? $typeKey;
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['okr', 'key_result', 'contexts', 'relationships'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
