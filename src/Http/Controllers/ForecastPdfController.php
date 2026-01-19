<?php

namespace Platform\Okr\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Platform\Okr\Models\Forecast;
use Barryvdh\DomPDF\Facade\Pdf;

class ForecastPdfController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Forecast $forecast)
    {
        $this->authorize('view', $forecast);

        // Lade alle benötigten Relationships
        $forecast->load([
            'team',
            'user',
            'currentVersion',
            'focusAreas' => function ($query) {
                $query->orderBy('order');
            },
            'focusAreas.visionImages' => function ($query) {
                $query->orderBy('order');
            },
            'focusAreas.obstacles' => function ($query) {
                $query->orderBy('order');
            },
            'focusAreas.milestones' => function ($query) {
                $query->orderBy('order');
            },
        ]);

        // Berechne verfügbare Jahre für Transformations-Map
        $startYear = $forecast->created_at->year;
        $endYear = $forecast->target_date->year;
        $availableYears = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $availableYears[$year] = $year;
        }

        $availableQuarters = [
            1 => 'Q1',
            2 => 'Q2',
            3 => 'Q3',
            4 => 'Q4',
        ];

        // Berechne Transformations-Map Daten
        $transformationMapData = [];
        foreach ($forecast->focusAreas->sortBy('order') as $focusArea) {
            $map = [
                'focusArea' => $focusArea,
                'milestonesByYearAndQuarter' => [],
            ];
            foreach ($availableYears as $year) {
                foreach ($availableQuarters as $quarter => $qLabel) {
                    $map['milestonesByYearAndQuarter'][$year][$quarter] = collect();
                }
            }

            foreach ($focusArea->milestones as $milestone) {
                if ($milestone->target_year && $milestone->target_quarter && isset($map['milestonesByYearAndQuarter'][$milestone->target_year][$milestone->target_quarter])) {
                    $map['milestonesByYearAndQuarter'][$milestone->target_year][$milestone->target_quarter]->push($milestone);
                } elseif ($milestone->target_year && !isset($map['milestonesByYearAndQuarter'][$milestone->target_year][0])) {
                    $map['milestonesByYearAndQuarter'][$milestone->target_year][0] = collect();
                    $map['milestonesByYearAndQuarter'][$milestone->target_year][0]->push($milestone);
                } elseif ($milestone->target_year && isset($map['milestonesByYearAndQuarter'][$milestone->target_year][0])) {
                    $map['milestonesByYearAndQuarter'][$milestone->target_year][0]->push($milestone);
                }
            }
            $transformationMapData[$focusArea->id] = $map;
        }

        $html = view('okr::pdf.forecast', [
            'forecast' => $forecast,
            'availableYears' => $availableYears,
            'availableQuarters' => $availableQuarters,
            'transformationMapData' => $transformationMapData,
        ])->render();

        $filename = str($forecast->title ?: 'regnose')
            ->slug('-')
            ->append('.pdf')
            ->toString();

        return Pdf::loadHTML($html)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setPaper('a4')
            ->download($filename);
    }
}
