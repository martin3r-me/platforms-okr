<?php

namespace Platform\Okr\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Okr\Models\Okr;
use Platform\Okr\Models\Cycle;
use Platform\Okr\Models\Objective;
use Platform\Okr\Models\KeyResult;
use Platform\Okr\Models\CycleTemplate;
use Platform\Core\Models\Team;
use Platform\Core\Models\User;

class OkrDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nur in Development/Testing
        if (app()->environment('production')) {
            $this->command->warn('Demo data seeding is disabled in production.');
            return;
        }

        $team = Team::first();
        $user = User::first();

        if (!$team || !$user) {
            $this->command->warn('No team or user found. Skipping demo data seeding.');
            return;
        }

        // Aktuelles Cycle Template finden
        $currentTemplate = CycleTemplate::where('is_current', true)->first();
        
        if (!$currentTemplate) {
            $this->command->warn('No current cycle template found. Run OkrCycleTemplateSeeder first.');
            return;
        }

        // Demo OKR erstellen
        $okr = Okr::create([
            'title' => 'Marketing Team OKR 2025',
            'description' => 'Hauptziele des Marketing-Teams für das Jahr 2025',
            'team_id' => $team->id,
            'user_id' => $user->id,
            'manager_user_id' => $user->id,
        ]);

        // Demo Cycle erstellen
        $cycle = Cycle::create([
            'okr_id' => $okr->id,
            'cycle_template_id' => $currentTemplate->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'status' => 'current',
        ]);

        // Demo Objectives erstellen
        $objectives = [
            [
                'title' => 'Marktanteil um 15% steigern',
                'description' => 'Unser Produkt soll in unserem Hauptmarkt eine führende Position einnehmen',
                'is_mountain' => true,
                'order' => 1,
            ],
            [
                'title' => 'Kundenzufriedenheit auf 95% erhöhen',
                'description' => 'Wir wollen die beste Kundenerfahrung in unserer Branche bieten',
                'is_mountain' => false,
                'order' => 2,
            ],
            [
                'title' => 'Neue Produktlinie erfolgreich einführen',
                'description' => 'Launch und Markteinführung unseres neuen Produkts',
                'is_mountain' => false,
                'order' => 3,
            ],
        ];

        foreach ($objectives as $objectiveData) {
            $objective = Objective::create([
                'okr_id' => $okr->id,
                'cycle_id' => $cycle->id,
                'team_id' => $team->id,
                'user_id' => $user->id,
                'manager_user_id' => $user->id,
                ...$objectiveData,
            ]);

            // Key Results für jedes Objective
            $keyResults = $this->getKeyResultsForObjective($objectiveData['title']);
            
            foreach ($keyResults as $index => $keyResultData) {
                KeyResult::create([
                    'objective_id' => $objective->id,
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'manager_user_id' => $user->id,
                    'title' => $keyResultData['title'],
                    'description' => $keyResultData['description'],
                    'order' => $index + 1,
                ]);
            }
        }

        $this->command->info('✅ OKR Demo data created successfully!');
        $this->command->line("Created OKR: {$okr->title}");
        $this->command->line("Created Cycle: {$currentTemplate->label}");
        $this->command->line("Created " . count($objectives) . " Objectives with Key Results");
    }

    private function getKeyResultsForObjective(string $objectiveTitle): array
    {
        return match ($objectiveTitle) {
            'Marktanteil um 15% steigern' => [
                [
                    'title' => 'Umsatz um 25% steigern',
                    'description' => 'Von 2M€ auf 2.5M€ im Quartal',
                ],
                [
                    'title' => 'Neue Kunden gewinnen',
                    'description' => 'Mindestens 50 neue Enterprise-Kunden',
                ],
                [
                    'title' => 'Marktpenetration erhöhen',
                    'description' => 'In 3 neuen Regionen aktiv werden',
                ],
            ],
            'Kundenzufriedenheit auf 95% erhöhen' => [
                [
                    'title' => 'NPS Score auf 70+ steigern',
                    'description' => 'Aktuell bei 45, Ziel: 70+',
                ],
                [
                    'title' => 'Support-Response-Zeit reduzieren',
                    'description' => 'Von 24h auf unter 4h',
                ],
                [
                    'title' => 'Kunden-Onboarding verbessern',
                    'description' => 'Time-to-Value auf unter 7 Tage',
                ],
            ],
            'Neue Produktlinie erfolgreich einführen' => [
                [
                    'title' => 'Beta-Release erfolgreich',
                    'description' => '100 Beta-Tester mit 80%+ Zufriedenheit',
                ],
                [
                    'title' => 'Marketing-Kampagne starten',
                    'description' => '10.000 Leads generieren',
                ],
                [
                    'title' => 'Sales-Training abschließen',
                    'description' => 'Alle Sales-Reps geschult',
                ],
            ],
            default => [],
        };
    }
}
