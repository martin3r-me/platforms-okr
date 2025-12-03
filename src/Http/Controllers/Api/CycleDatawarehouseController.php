<?php

namespace Platform\Okr\Http\Controllers\Api;

use Platform\Core\Http\Controllers\ApiController;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Core\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Datawarehouse API Controller für OKR Cycles
 * 
 * Stellt flexible Filter und Aggregationen für das Datawarehouse bereit.
 * Unterstützt Team-Hierarchien (inkl. Kind-Teams).
 */
class CycleDatawarehouseController extends ApiController
{
    /**
     * Flexibler Datawarehouse-Endpunkt für aktuelle Zyklen
     * 
     * Unterstützt komplexe Filter und Aggregationen
     */
    public function index(Request $request)
    {
        $query = Cycle::query();

        // ===== FILTER =====
        $this->applyFilters($query, $request);

        // ===== SORTING =====
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        
        // Validierung der Sort-Spalte (Security)
        $allowedSortColumns = ['id', 'created_at', 'updated_at', 'status'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // ===== PAGINATION =====
        $perPage = min($request->get('per_page', 100), 1000); // Max 1000 pro Seite
        // Relationen laden - mit Objectives, KeyResults und Performance-Daten
        $query->with([
            'team:id,name',
            'user:id,name,email',
            'okr:id,title',
            'template:id,label,starts_at,ends_at',
            'performance:id,cycle_id,performance_score,completion_percentage,completed_objectives,total_objectives,completed_key_results,total_key_results,is_completed,updated_at',
            'objectives' => function ($q) {
                $q->with([
                    'user:id,name,email',
                    'manager:id,name,email',
                    'performance:id,objective_id,performance_score,completion_percentage,completed_key_results,total_key_results,average_progress,is_completed,updated_at',
                    'keyResults' => function ($kr) {
                        $kr->with([
                            'user:id,name,email',
                            'manager:id,name,email',
                            'performance:id,key_result_id,type,current_value,target_value,calculated_value,performance_score,tendency,is_completed,updated_at'
                        ])->orderBy('order');
                    }
                ])->orderBy('order');
            }
        ]);
        $cycles = $query->paginate($perPage);

        // ===== FORMATTING =====
        // Datawarehouse-freundliches Format mit Objectives und KeyResults
        $formatted = $cycles->map(function ($cycle) {
            // Objectives mit KeyResults formatieren
            $objectives = $cycle->objectives->map(function ($objective) use ($cycle) {
                // KeyResults formatieren
                $keyResults = $objective->keyResults->map(function ($keyResult) use ($cycle, $objective) {
                    return [
                        'id' => $keyResult->id,
                        'uuid' => $keyResult->uuid,
                        'key_result_id' => $keyResult->id,
                        'objective_id' => $keyResult->objective_id,
                        'cycle_id' => $cycle->id,
                        'okr_id' => $cycle->okr_id,
                        'team_id' => $keyResult->team_id,
                        'user_id' => $keyResult->user_id,
                        'user_name' => $keyResult->user?->name,
                        'user_email' => $keyResult->user?->email,
                        'manager_user_id' => $keyResult->manager_user_id,
                        'manager_name' => $keyResult->manager?->name,
                        'title' => $keyResult->title,
                        'description' => $keyResult->description,
                        'order' => $keyResult->order,
                        // Performance-Daten (WICHTIG!)
                        'performance_type' => $keyResult->performance?->type,
                        'performance_current_value' => $keyResult->performance?->current_value,
                        'performance_target_value' => $keyResult->performance?->target_value,
                        'performance_calculated_value' => $keyResult->performance?->calculated_value,
                        'performance_score' => $keyResult->performance?->performance_score,
                        'performance_tendency' => $keyResult->performance?->tendency,
                        'performance_is_completed' => $keyResult->performance?->is_completed,
                        'performance_updated_at' => $keyResult->performance?->updated_at?->toIso8601String(),
                        'created_at' => $keyResult->created_at->toIso8601String(),
                        'updated_at' => $keyResult->updated_at->toIso8601String(),
                    ];
                });

                return [
                    'id' => $objective->id,
                    'uuid' => $objective->uuid,
                    'objective_id' => $objective->id,
                    'cycle_id' => $cycle->id,
                    'okr_id' => $cycle->okr_id,
                    'team_id' => $objective->team_id,
                    'user_id' => $objective->user_id,
                    'user_name' => $objective->user?->name,
                    'user_email' => $objective->user?->email,
                    'manager_user_id' => $objective->manager_user_id,
                    'manager_name' => $objective->manager?->name,
                    'title' => $objective->title,
                    'description' => $objective->description,
                    'is_mountain' => $objective->is_mountain,
                    'order' => $objective->order,
                    // Performance-Daten (WICHTIG!)
                    'performance_score' => $objective->performance?->performance_score,
                    'performance_completion_percentage' => $objective->performance?->completion_percentage,
                    'performance_completed_key_results' => $objective->performance?->completed_key_results,
                    'performance_total_key_results' => $objective->performance?->total_key_results,
                    'performance_average_progress' => $objective->performance?->average_progress,
                    'performance_is_completed' => $objective->performance?->is_completed,
                    'performance_updated_at' => $objective->performance?->updated_at?->toIso8601String(),
                    // KeyResults
                    'key_results' => $keyResults,
                    'key_results_count' => $keyResults->count(),
                    'created_at' => $objective->created_at->toIso8601String(),
                    'updated_at' => $objective->updated_at->toIso8601String(),
                ];
            });

            return [
                'id' => $cycle->id,
                'uuid' => $cycle->uuid,
                'okr_id' => $cycle->okr_id,
                'okr_title' => $cycle->okr?->title, // OKR-Titel mitliefern (denormalisiert)
                'team_id' => $cycle->team_id,
                'team_name' => $cycle->team?->name, // Team-Name mitliefern (denormalisiert)
                'user_id' => $cycle->user_id,
                'user_name' => $cycle->user?->name, // User-Name mitliefern (denormalisiert)
                'user_email' => $cycle->user?->email, // User-Email mitliefern
                'cycle_template_id' => $cycle->cycle_template_id,
                'label' => $cycle->label, // Kann aus Template kommen
                'type' => $cycle->type,
                'status' => $cycle->status,
                'notes' => $cycle->notes,
                'description' => $cycle->description,
                // Template-Daten
                'template_label' => $cycle->template?->label,
                'starts_at' => $cycle->starts_at?->format('Y-m-d'), // Aus Template
                'ends_at' => $cycle->ends_at?->format('Y-m-d'), // Aus Template
                // Cycle Performance-Daten (WICHTIG!)
                'performance_score' => $cycle->performance?->performance_score,
                'performance_completion_percentage' => $cycle->performance?->completion_percentage,
                'performance_completed_objectives' => $cycle->performance?->completed_objectives,
                'performance_total_objectives' => $cycle->performance?->total_objectives,
                'performance_completed_key_results' => $cycle->performance?->completed_key_results,
                'performance_total_key_results' => $cycle->performance?->total_key_results,
                'performance_average_objective_progress' => $cycle->performance?->average_objective_progress,
                'performance_average_key_result_progress' => $cycle->performance?->average_key_result_progress,
                'performance_is_completed' => $cycle->performance?->is_completed,
                'performance_updated_at' => $cycle->performance?->updated_at?->toIso8601String(),
                // Objectives mit KeyResults
                'objectives' => $objectives,
                'objectives_count' => $objectives->count(),
                'total_key_results_count' => $objectives->sum('key_results_count'),
                'created_at' => $cycle->created_at->toIso8601String(),
                'updated_at' => $cycle->updated_at->toIso8601String(),
                'deleted_at' => $cycle->deleted_at?->toIso8601String(),
            ];
        });

        return $this->paginated(
            $cycles->setCollection($formatted),
            'Zyklen erfolgreich geladen'
        );
    }

    /**
     * Wendet alle Filter auf die Query an
     */
    protected function applyFilters($query, Request $request): void
    {
        // Team-Filter mit Kind-Teams Option (standardmäßig aktiviert)
        if ($request->has('team_id')) {
            $teamId = $request->team_id;
            // Standardmäßig Kind-Teams inkludieren (wenn nicht explizit false)
            $includeChildrenValue = $request->input('include_child_teams');
            $includeChildren = $request->has('include_child_teams') 
                ? ($includeChildrenValue === '1' || $includeChildrenValue === 'true' || $includeChildrenValue === true || $includeChildrenValue === 1)
                : true; // Default: true (wenn nicht gesetzt)
            
            if ($includeChildren) {
                // Team mit Kind-Teams laden
                $team = Team::find($teamId);
                
                if ($team) {
                    // Alle Team-IDs inkl. Kind-Teams sammeln
                    $teamIds = $team->getAllTeamIdsIncludingChildren();
                    $query->whereIn('team_id', $teamIds);
                } else {
                    // Team nicht gefunden - leeres Ergebnis
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Nur das genannte Team (wenn explizit deaktiviert)
                $query->where('team_id', $teamId);
            }
        }

        // Status-Filter
        $status = $request->input('status');
        if ($status) {
            if ($status === 'current') {
                // "current" bedeutet: Zyklus ist aktuell aktiv (basierend auf Template-Zeitraum)
                $this->applyCurrentlyActiveFilter($query);
            } elseif ($status === 'current_template') {
                // "current_template" bedeutet: Alle Cycles, die zu einem CycleTemplate mit is_current=true gehören
                // Wird für Datawarehouse-Imports verwendet, um alle Cycles des aktuellen Templates zu importieren
                // Wichtig: Nur Cycles mit cycle_template_id (nicht NULL) und Template mit is_current=true
                $query->whereNotNull('cycle_template_id')
                      ->whereHas('template', function ($q) {
                          $q->where('is_current', true);
                      });
                
                // Debug: Logge wie viele Templates mit is_current=true existieren
                try {
                    Log::debug('OKR Cycles Filter: current_template', [
                        'templates_with_is_current' => CycleTemplate::where('is_current', true)->count(),
                        'cycles_with_current_template' => Cycle::whereNotNull('cycle_template_id')
                            ->whereHas('template', function ($q) {
                                $q->where('is_current', true);
                            })->count(),
                    ]);
                } catch (\Exception $e) {
                    // Debug-Logging sollte den Request nicht blockieren
                    Log::warning('Failed to log OKR Cycles debug info: ' . $e->getMessage());
                }
            } elseif ($status === 'all') {
                // "all" bedeutet: Alle Cycles zurückgeben (kein Status-Filter)
                // Wird für Datawarehouse-Imports verwendet, um alle Cycles zu importieren
                // Kein Filter wird angewendet
            } else {
                // Alle anderen Statuswerte kommen direkt aus der Cycle-Tabelle
                $query->where('status', $status);
            }
        } else {
            // Default: nur aktuell laufende Zyklen (Template-Zeitraum)
            $this->applyCurrentlyActiveFilter($query);
        }

        // OKR-Filter
        if ($request->has('okr_id')) {
            $query->where('okr_id', $request->okr_id);
        }

        // User-Filter
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Template-Filter
        if ($request->has('cycle_template_id')) {
            $query->where('cycle_template_id', $request->cycle_template_id);
        }

        // Type-Filter
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Erstellt heute
        if ($request->boolean('created_today')) {
            $query->whereDate('created_at', Carbon::today());
        }

        // Erstellt in Range
        if ($request->has('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->has('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Template-Datums-Filter (über Template-Relation)
        if ($request->has('starts_at_from')) {
            $query->whereHas('template', function ($q) use ($request) {
                $q->whereDate('starts_at', '>=', $request->starts_at_from);
            });
        }
        if ($request->has('starts_at_to')) {
            $query->whereHas('template', function ($q) use ($request) {
                $q->whereDate('starts_at', '<=', $request->starts_at_to);
            });
        }
        if ($request->has('ends_at_from')) {
            $query->whereHas('template', function ($q) use ($request) {
                $q->whereDate('ends_at', '>=', $request->ends_at_from);
            });
        }
        if ($request->has('ends_at_to')) {
            $query->whereHas('template', function ($q) use ($request) {
                $q->whereDate('ends_at', '<=', $request->ends_at_to);
            });
        }

        // Aktuell laufende Zyklen (basierend auf Template-Daten)
        if ($request->boolean('currently_active')) {
            $this->applyCurrentlyActiveFilter($query);
        }

        // Hat Notizen
        if ($request->has('has_notes')) {
            if ($request->has_notes === 'true' || $request->has_notes === '1') {
                $query->whereNotNull('notes')
                      ->where('notes', '!=', '');
            } else {
                $query->where(function($q) {
                    $q->whereNull('notes')
                      ->orWhere('notes', '');
                });
            }
        }

        // Hat Beschreibung
        if ($request->has('has_description')) {
            if ($request->has_description === 'true' || $request->has_description === '1') {
                $query->whereNotNull('description')
                      ->where('description', '!=', '');
            } else {
                $query->where(function($q) {
                    $q->whereNull('description')
                      ->orWhere('description', '');
                });
            }
        }

        // Nur gelöschte Einträge
        if ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        }

        // Mit gelöschten Einträgen
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }
    }

    /**
     * Filtert auf aktuell laufende Zyklen (basierend auf Template-Zeiträumen)
     */
    protected function applyCurrentlyActiveFilter($query): void
    {
        $today = Carbon::today();
        $query->whereHas('template', function ($q) use ($today) {
            $q->whereDate('starts_at', '<=', $today)
              ->whereDate('ends_at', '>=', $today);
        });
    }

    /**
     * Health Check Endpoint
     * Gibt einen Beispiel-Datensatz zurück für Tests
     */
    public function health(Request $request)
    {
        try {
            $example = Cycle::with([
                'team:id,name',
                'user:id,name,email',
                'okr:id,title',
                'template:id,label,starts_at,ends_at',
                'performance:id,cycle_id,performance_score,completion_percentage',
            ])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$example) {
                return $this->success([
                    'status' => 'ok',
                    'message' => 'API ist erreichbar, aber keine Cycles vorhanden',
                    'example' => null,
                    'timestamp' => now()->toIso8601String(),
                ], 'Health Check');
            }

            $exampleData = [
                'id' => $example->id,
                'uuid' => $example->uuid,
                'okr_id' => $example->okr_id,
                'okr_title' => $example->okr?->title,
                'team_id' => $example->team_id,
                'team_name' => $example->team?->name,
                'user_id' => $example->user_id,
                'user_name' => $example->user?->name,
                'user_email' => $example->user?->email,
                'cycle_template_id' => $example->cycle_template_id,
                'label' => $example->label,
                'type' => $example->type,
                'status' => $example->status,
                'template_label' => $example->template?->label,
                'starts_at' => $example->starts_at?->format('Y-m-d'),
                'ends_at' => $example->ends_at?->format('Y-m-d'),
                'performance_score' => $example->performance?->performance_score,
                'performance_completion_percentage' => $example->performance?->completion_percentage,
                'created_at' => $example->created_at->toIso8601String(),
                'updated_at' => $example->updated_at->toIso8601String(),
            ];

            return $this->success([
                'status' => 'ok',
                'message' => 'API ist erreichbar',
                'example' => $exampleData,
                'timestamp' => now()->toIso8601String(),
            ], 'Health Check');

        } catch (\Exception $e) {
            return $this->error('Health Check fehlgeschlagen: ' . $e->getMessage(), 500);
        }
    }
}

