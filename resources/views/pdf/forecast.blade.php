<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $forecast->title }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #111827;
        }
        h1 {
            font-size: 24pt;
            margin: 0 0 8mm 0;
            font-weight: bold;
        }
        h2 { 
            font-size: 16pt; 
            margin: 10mm 0 4mm 0; 
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 2mm;
        }
        h3 { 
            font-size: 13pt; 
            margin: 6mm 0 3mm 0; 
            font-weight: bold;
        }
        h4 {
            font-size: 11pt;
            margin: 4mm 0 2mm 0;
            font-weight: bold;
        }
        p { margin: 0 0 3mm 0; }
        ul, ol { margin: 0 0 4mm 0; padding-left: 6mm; }
        li { margin: 0 0 1.5mm 0; }
        .meta {
            margin-top: 8mm;
            font-size: 9pt;
            color: #6b7280;
            padding-top: 4mm;
            border-top: 1px solid #e5e7eb;
        }
        .header-info {
            display: flex;
            gap: 8mm;
            margin-bottom: 6mm;
            font-size: 10pt;
            color: #6b7280;
        }
        .header-info span {
            display: flex;
            align-items: center;
            gap: 2mm;
        }
        .focus-area {
            margin-bottom: 8mm;
            padding: 4mm;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            background: #f9fafb;
        }
        .focus-area-title {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 3mm;
            color: #1f2937;
        }
        .focus-area-description {
            font-size: 10pt;
            color: #4b5563;
            margin-bottom: 4mm;
        }
        .section {
            margin-bottom: 5mm;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2mm;
            color: #374151;
        }
        .item {
            margin-bottom: 2mm;
            padding: 2mm;
            background: white;
            border-left: 3px solid #3b82f6;
            padding-left: 3mm;
        }
        .item-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 1mm;
        }
        .item-description {
            font-size: 9pt;
            color: #6b7280;
        }
        .milestone-badge {
            display: inline-block;
            padding: 1mm 2mm;
            background: #10b981;
            color: white;
            font-size: 8pt;
            border-radius: 3px;
            margin-right: 2mm;
        }
        .transformation-map {
            margin: 6mm 0;
            font-size: 8pt;
        }
        .transformation-map table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
        }
        .transformation-map th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 2mm;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }
        .transformation-map td {
            border: 1px solid #d1d5db;
            padding: 2mm;
            text-align: left;
            vertical-align: top;
            font-size: 7pt;
        }
        .transformation-map .focus-area-cell {
            font-weight: bold;
            background: #f9fafb;
            min-width: 30mm;
        }
        .milestone-item {
            margin-bottom: 1mm;
            padding: 1mm;
            background: #ecfdf5;
            border-left: 2px solid #10b981;
            padding-left: 2mm;
        }
        .content-section {
            margin: 6mm 0;
            padding: 4mm;
            background: #f9fafb;
            border-radius: 4px;
        }
        .stats {
            display: flex;
            gap: 4mm;
            margin: 4mm 0;
        }
        .stat-item {
            padding: 3mm;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            text-align: center;
            flex: 1;
        }
        .stat-value {
            font-size: 18pt;
            font-weight: bold;
            color: #3b82f6;
        }
        .stat-label {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 1mm;
        }
        .central-question {
            margin: 3mm 0;
            padding: 3mm;
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            font-size: 10pt;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>{{ $forecast->title }}</h1>

    <div class="header-info">
        <span>Zieldatum: {{ $forecast->target_date->format('d.m.Y') }}</span>
        @if($forecast->currentVersion)
            <span>Version: {{ $forecast->currentVersion->version }}</span>
        @endif
        <span>Erstellt: {{ $forecast->created_at->format('d.m.Y') }}</span>
    </div>

    {{-- Statistiken --}}
    <div class="stats">
        <div class="stat-item">
            <div class="stat-value">{{ $forecast->focusAreas->count() }}</div>
            <div class="stat-label">Fokusräume</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $forecast->focusAreas->sum(fn($fa) => $fa->visionImages->count()) }}</div>
            <div class="stat-label">Zielbilder</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $forecast->focusAreas->sum(fn($fa) => $fa->obstacles->count()) }}</div>
            <div class="stat-label">Hindernisse</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $forecast->focusAreas->sum(fn($fa) => $fa->milestones->count()) }}</div>
            <div class="stat-label">Meilensteine</div>
        </div>
    </div>

    {{-- Regnose Content --}}
    @if($forecast->currentVersion && $forecast->currentVersion->content)
        <div class="content-section">
            <h2>Regnose Inhalt</h2>
            <div>
                {!! \Illuminate\Support\Str::markdown($forecast->currentVersion->content) !!}
            </div>
        </div>
    @endif

    {{-- Transformations-Map --}}
    @if(count($availableYears) > 0 && $forecast->focusAreas->count() > 0)
        <h2>Transformations-Map</h2>
        <div class="transformation-map">
            <table>
                <thead>
                    <tr>
                        <th class="focus-area-cell">Fokusraum</th>
                        @foreach($availableYears as $year)
                            <th colspan="4" style="text-align: center;">{{ $year }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="focus-area-cell"></th>
                        @foreach($availableYears as $year)
                            @foreach($availableQuarters as $quarter => $qLabel)
                                <th style="text-align: center;">{{ $qLabel }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($transformationMapData as $data)
                        <tr>
                            <td class="focus-area-cell">{{ $data['focusArea']->title }}</td>
                            @foreach($availableYears as $year)
                                @foreach($availableQuarters as $quarter => $qLabel)
                                    <td>
                                        @if(isset($data['milestonesByYearAndQuarter'][$year][$quarter]))
                                            @foreach($data['milestonesByYearAndQuarter'][$year][$quarter] as $milestone)
                                                <div class="milestone-item">
                                                    {{ $milestone->title }}
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Fokusräume --}}
    <h2>Fokusräume</h2>
    @foreach($forecast->focusAreas->sortBy('order') as $focusArea)
        <div class="focus-area">
            <div class="focus-area-title">{{ $focusArea->title }}</div>
            @if($focusArea->description)
                <div class="focus-area-description">{{ $focusArea->description }}</div>
            @endif

            {{-- Zentrale Fragen --}}
            @if($focusArea->central_question_vision_images || $focusArea->central_question_obstacles || $focusArea->central_question_milestones)
                <div class="section">
                    <h4>Zentrale Fragen</h4>
                    @if($focusArea->central_question_vision_images)
                        <div class="central-question">
                            <strong>Zielbilder:</strong> {{ $focusArea->central_question_vision_images }}
                        </div>
                    @endif
                    @if($focusArea->central_question_obstacles)
                        <div class="central-question">
                            <strong>Hindernisse:</strong> {{ $focusArea->central_question_obstacles }}
                        </div>
                    @endif
                    @if($focusArea->central_question_milestones)
                        <div class="central-question">
                            <strong>Meilensteine:</strong> {{ $focusArea->central_question_milestones }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Focus Area Content --}}
            @if($focusArea->content)
                <div class="section">
                    <h4>Beschreibung</h4>
                    <div>
                        {!! \Illuminate\Support\Str::markdown($focusArea->content) !!}
                    </div>
                </div>
            @endif

            {{-- Zielbilder --}}
            @if($focusArea->visionImages->count() > 0)
                <div class="section">
                    <h4>Zielbilder ({{ $focusArea->visionImages->count() }})</h4>
                    @foreach($focusArea->visionImages->sortBy('order') as $visionImage)
                        <div class="item">
                            <div class="item-title">{{ $visionImage->title }}</div>
                            @if($visionImage->description)
                                <div class="item-description">{{ $visionImage->description }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Hindernisse --}}
            @if($focusArea->obstacles->count() > 0)
                <div class="section">
                    <h4>Hindernisse ({{ $focusArea->obstacles->count() }})</h4>
                    @foreach($focusArea->obstacles->sortBy('order') as $obstacle)
                        <div class="item">
                            <div class="item-title">{{ $obstacle->title }}</div>
                            @if($obstacle->description)
                                <div class="item-description">{{ $obstacle->description }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Meilensteine --}}
            @if($focusArea->milestones->count() > 0)
                <div class="section">
                    <h4>Meilensteine ({{ $focusArea->milestones->count() }})</h4>
                    @foreach($focusArea->milestones->sortBy('order') as $milestone)
                        <div class="item">
                            <div class="item-title">
                                {{ $milestone->title }}
                                @if($milestone->target_year)
                                    <span class="milestone-badge">
                                        {{ $milestone->target_year }}
                                        @if($milestone->target_quarter)
                                            Q{{ $milestone->target_quarter }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                            @if($milestone->description)
                                <div class="item-description">{{ $milestone->description }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    <div class="meta">
        Erstellt: {{ optional($forecast->created_at)->format('d.m.Y H:i') }} ·
        Aktualisiert: {{ optional($forecast->updated_at)->format('d.m.Y H:i') }}
        @if($forecast->user)
            · Von: {{ $forecast->user->name }}
        @endif
    </div>
</body>
</html>
