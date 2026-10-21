<?php

namespace Platform\Okr\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Platform\Core\Models\Module;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\CycleTemplate;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\Okr;

/**
 * Agent-API des OKR-Moduls (/api/okr/agent/…): ein Agent pflegt SEIN persönliches OKR selbst —
 * Entwurf anlegen, nach Freigabe aktivieren, einchecken. Gleiche Anlege-Logik wie die Tools/UI,
 * aber über einen festen Vertrag statt freier MCP-Aufrufe (die Firmware steuert, nicht der Kortex).
 *
 * „Persönliches OKR" = Okr mit user_id = der authentifizierte (Bot-)User. Ein Entwurf ist ein
 * Zyklus im Status `draft`; die Freigabe (TRAAN, durch die Bezugsperson) setzt ihn auf `active`.
 * Grenzen wie beim Menschen: max. 5 Objectives × 7 Key Results je Zyklus.
 */
class OkrAgentController extends Controller
{
    private const MAX_OBJECTIVES = 5;
    private const MAX_KEY_RESULTS = 7;
    private const VALUE_TYPES = ['absolute' => 'absolute', 'relative' => 'percentage', 'boolean' => 'boolean'];

    /**
     * GET /api/okr/agent/context — laufendes + nächstes Quartal, mein persönliches OKR und mein
     * Zyklus im laufenden Quartal (Entwurf oder aktiv), plus die Grenzen.
     */
    public function context(Request $request): JsonResponse
    {
        [$userId, $teamId, $err] = $this->actor($request);
        if ($err) {
            return $err;
        }

        $current = $this->quarterTemplate(0);
        $next = $this->quarterTemplate(1);
        $okr = $this->personalOkr($userId, $teamId);

        return response()->json(['data' => [
            'current_quarter' => $this->templateArray($current),
            'next_quarter' => $this->templateArray($next),
            'personal_okr' => $okr ? ['id' => $okr->id, 'title' => $okr->title] : null,
            'current_cycle' => $okr && $current ? $this->cycleArray($this->cycleFor($okr, $current)) : null,
            'next_cycle' => $okr && $next ? $this->cycleArray($this->cycleFor($okr, $next)) : null,
            'limits' => ['max_objectives' => self::MAX_OBJECTIVES, 'max_key_results' => self::MAX_KEY_RESULTS],
        ]]);
    }

    /**
     * POST /api/okr/agent/drafts — legt einen Entwurf an (oder ersetzt einen bestehenden Entwurf
     * desselben Quartals, z. B. nach Ablehnung). Ein bereits AKTIVER Zyklus wird nicht überschrieben.
     *
     * Body: { quarter: "current"|"next" (default current), title?: string,
     *         objectives: [ { title, description?, key_results: [ { title, description?,
     *           value_type: absolute|relative|boolean, target_value?: number } ] } ] }
     */
    public function storeDraft(Request $request): JsonResponse
    {
        [$userId, $teamId, $err] = $this->actor($request);
        if ($err) {
            return $err;
        }

        $data = $request->validate([
            'quarter' => 'nullable|in:current,next',
            'title' => 'nullable|string|max:255',
            'objectives' => 'required|array|min:1|max:' . self::MAX_OBJECTIVES,
            'objectives.*.title' => 'required|string|max:500',
            'objectives.*.description' => 'nullable|string|max:5000',
            'objectives.*.key_results' => 'required|array|min:1|max:' . self::MAX_KEY_RESULTS,
            'objectives.*.key_results.*.title' => 'required|string|max:500',
            'objectives.*.key_results.*.description' => 'nullable|string|max:5000',
            'objectives.*.key_results.*.value_type' => 'required|in:absolute,relative,boolean',
            'objectives.*.key_results.*.target_value' => 'nullable|numeric|min:0',
        ]);

        $tpl = $this->quarterTemplate(($data['quarter'] ?? 'current') === 'next' ? 1 : 0);
        if (!$tpl) {
            return response()->json(['message' => 'Kein Quartals-Template für diesen Zeitraum.'], 422);
        }

        foreach ($data['objectives'] as $i => $o) {
            foreach ($o['key_results'] as $k => $kr) {
                if ($kr['value_type'] !== 'boolean' && !isset($kr['target_value'])) {
                    return response()->json(['message' => "objectives.{$i}.key_results.{$k}.target_value fehlt (nötig außer bei boolean)."], 422);
                }
            }
        }

        $result = DB::transaction(function () use ($data, $userId, $teamId, $tpl, $request) {
            $okr = $this->personalOkr($userId, $teamId) ?? Okr::create([
                'title' => trim((string) ($data['title'] ?? $request->user()->name ?? 'Persönliches OKR')),
                'team_id' => $teamId,
                'user_id' => $userId,
            ]);

            $cycle = $this->cycleFor($okr, $tpl);
            if ($cycle && $cycle->status !== 'draft') {
                return ['conflict' => "Für {$tpl->label} gibt es bereits einen Zyklus im Status '{$cycle->status}' — Entwurf nicht überschrieben."];
            }
            if ($cycle) {
                // Erneuter Entwurf (z. B. nach Ablehnung): alte Entwurfs-Ziele ersetzen.
                foreach ($cycle->objectives()->get() as $old) {
                    $old->keyResults()->delete();
                    $old->delete();
                }
            } else {
                $cycle = Cycle::create([
                    'okr_id' => $okr->id,
                    'cycle_template_id' => $tpl->id,
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'status' => 'draft',
                ]);
            }

            foreach (array_values($data['objectives']) as $oi => $o) {
                $objective = Objective::create([
                    'okr_id' => $okr->id,
                    'cycle_id' => $cycle->id,
                    'team_id' => $teamId,
                    'user_id' => $userId,
                    'title' => trim($o['title']),
                    'description' => $o['description'] ?? null,
                    'order' => $oi + 1,
                ]);
                foreach (array_values($o['key_results']) as $ki => $k) {
                    $kr = KeyResult::create([
                        'objective_id' => $objective->id,
                        'team_id' => $teamId,
                        'user_id' => $userId,
                        'title' => trim($k['title']),
                        'description' => $k['description'] ?? null,
                        'order' => $ki + 1,
                    ]);
                    $type = self::VALUE_TYPES[$k['value_type']];
                    $kr->performances()->create([
                        'type' => $type,
                        'target_value' => $type === 'boolean' ? 1.0 : (float) $k['target_value'],
                        'current_value' => 0.0,
                        'is_completed' => false,
                        'performance_score' => 0.0,
                        'team_id' => $teamId,
                        'user_id' => $userId,
                    ]);
                }
            }

            return ['okr' => $okr, 'cycle' => $cycle->fresh()];
        });

        if (isset($result['conflict'])) {
            return response()->json(['message' => $result['conflict']], 409);
        }

        return response()->json(['data' => [
            'okr_id' => $result['okr']->id,
            'cycle' => $this->cycleArray($result['cycle']),
        ]], 201);
    }

    /**
     * POST /api/okr/agent/drafts/{cycle}/activate — Freigabe umsetzen: nur MEIN Entwurf; die
     * Freigabe-Referenz (TRAAN-Vorgang) wird am Zyklus vermerkt (nachvollziehbar, wer freigab).
     */
    public function activateDraft(Request $request, int $cycle): JsonResponse
    {
        [$userId, $teamId, $err] = $this->actor($request);
        if ($err) {
            return $err;
        }
        $data = $request->validate(['approval_ref' => 'required|string|max:255']);

        $c = Cycle::query()->where('team_id', $teamId)->find($cycle);
        if (!$c || (int) Okr::query()->whereKey($c->okr_id)->value('user_id') !== $userId) {
            return response()->json(['message' => 'Zyklus nicht gefunden oder nicht dein persönliches OKR.'], 404);
        }
        if ($c->status !== 'draft') {
            return response()->json(['message' => "Zyklus ist nicht im Entwurf (Status '{$c->status}')."], 409);
        }
        $c->status = 'active';
        $c->notes = trim(($c->notes ? $c->notes . "\n" : '') . 'Freigegeben über ' . $data['approval_ref'] . ' am ' . now()->toDateTimeString());
        $c->save();

        return response()->json(['data' => $this->cycleArray($c->fresh())]);
    }

    /**
     * POST /api/okr/agent/checkins — Check-in auf ein EIGENES Key Result (neue Performance-Version).
     * Body: { key_result_id, current_value?: number, is_completed?: bool }
     */
    public function checkin(Request $request): JsonResponse
    {
        [$userId, $teamId, $err] = $this->actor($request);
        if ($err) {
            return $err;
        }
        $data = $request->validate([
            'key_result_id' => 'required|integer',
            'current_value' => 'nullable|numeric|min:0',
            'is_completed' => 'nullable|boolean',
        ]);

        $kr = KeyResult::query()->where('team_id', $teamId)->with('performance')->find($data['key_result_id']);
        if (!$kr || (int) $kr->user_id !== $userId) {
            return response()->json(['message' => 'Key Result nicht gefunden oder nicht deins.'], 404);
        }
        if ($kr->isMetricDriven()) {
            return response()->json(['message' => 'Dieses Key Result wird über Kennzahlen gemessen — kein manueller Check-in.'], 409);
        }

        $last = $kr->performance;
        $type = $last->type ?? 'absolute';
        $target = (float) ($last->target_value ?? 0);
        if ($type === 'boolean') {
            $done = (bool) ($data['is_completed'] ?? ($data['current_value'] ?? 0) > 0);
            $current = $done ? 1.0 : 0.0;
            $score = $done ? 1.0 : 0.0;
            $target = 1.0;
        } else {
            $current = (float) ($data['current_value'] ?? 0);
            $done = false;
            $score = $target > 0 ? min(1.0, max(0.0, $current / $target)) : 0.0;
        }

        $perf = $kr->performances()->create([
            'type' => $type,
            'target_value' => $target,
            'current_value' => $current,
            'is_completed' => $done,
            'performance_score' => $score,
            'team_id' => $kr->team_id,
            'user_id' => $userId,
        ]);

        return response()->json(['data' => [
            'key_result_id' => $kr->id,
            'current_value' => $current,
            'target_value' => $target,
            'performance_score' => $score,
            'checked_in_at' => $perf->created_at?->toIso8601String(),
        ]], 201);
    }

    // ── Hilfen ───────────────────────────────────────────────────────────────────────────────

    /** @return array{0:int,1:int,2:?JsonResponse} */
    private function actor(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [0, 0, response()->json(['message' => 'Unauthenticated'], 401)];
        }
        $teamId = $this->rootTeamId($user);
        if (!$teamId) {
            return [0, 0, response()->json(['message' => 'Kein Team für diesen User.'], 422)];
        }
        return [(int) $user->id, $teamId, null];
    }

    /** OKR ist root-scoped (wie ResolvesOkrScope): Root-Team des Basis-Teams. */
    private function rootTeamId($user): ?int
    {
        $base = $user->currentTeamRelation ?? null;
        if (!$base) {
            return null;
        }
        $module = Module::where('key', 'okr')->first();
        if ($module && method_exists($module, 'isRootScoped') && $module->isRootScoped() && method_exists($base, 'getRootTeam')) {
            return (int) ($base->getRootTeam()->id ?? $base->id);
        }
        return (int) $base->id;
    }

    /** Quartals-Template nach DATUM: 0 = laufendes, 1 = nächstes. */
    private function quarterTemplate(int $offset): ?CycleTemplate
    {
        $today = Carbon::today();
        $current = CycleTemplate::query()->where('type', 'quarter')
            ->whereDate('starts_at', '<=', $today)->whereDate('ends_at', '>=', $today)
            ->orderByDesc('starts_at')->first();
        if ($offset === 0 || !$current) {
            return $offset === 0 ? $current : null;
        }
        return CycleTemplate::query()->where('type', 'quarter')
            ->whereDate('starts_at', '>', $current->ends_at)
            ->orderBy('starts_at')->first();
    }

    private function personalOkr(int $userId, int $teamId): ?Okr
    {
        return Okr::query()->where('team_id', $teamId)->where('user_id', $userId)
            ->where('is_template', false)->orderByDesc('id')->first();
    }

    private function cycleFor(Okr $okr, CycleTemplate $tpl): ?Cycle
    {
        return Cycle::query()->where('okr_id', $okr->id)->where('cycle_template_id', $tpl->id)
            ->orderByDesc('id')->first();
    }

    private function templateArray(?CycleTemplate $t): ?array
    {
        return $t ? [
            'id' => $t->id,
            'label' => $t->label,
            'starts_at' => $t->starts_at?->toDateString(),
            'ends_at' => $t->ends_at?->toDateString(),
        ] : null;
    }

    private function cycleArray(?Cycle $c): ?array
    {
        if (!$c) {
            return null;
        }
        $c->loadMissing('objectives.keyResults');

        return [
            'id' => $c->id,
            'status' => $c->status,
            'template_id' => $c->cycle_template_id,
            'objectives' => $c->objectives->sortBy('order')->values()->map(fn ($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'key_results' => $o->keyResults->sortBy('order')->values()->map(fn ($k) => [
                    'id' => $k->id,
                    'title' => $k->title,
                ])->all(),
            ])->all(),
        ];
    }
}
