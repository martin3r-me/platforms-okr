<?php

namespace Platform\Okr\Livewire;

use Livewire\Component;
use Platform\Okr\Models\StrategicDocument;
use Platform\Core\Models\Team;
use Livewire\Attributes\Computed;

class StrategicDocumentsManagement extends Component
{
    // Modal States
    public $createModalShow = false;
    public $editModalShow = false;
    public $versionModalShow = false;
    public $viewVersionsModalShow = false;

    // Form Data
    public $form = [
        'type' => 'mission',
        'title' => '',
        'content' => '',
        'valid_from' => '',
        'change_note' => '',
        'is_active' => true,
    ];

    public $editingDocumentId = null;
    public $viewingVersionsType = null;
    public $versions = [];

    protected $rules = [
        'form.type' => 'required|in:mission,vision,regnose',
        'form.title' => 'required|string|max:255',
        'form.content' => 'nullable|string',
        'form.valid_from' => 'required|date',
        'form.change_note' => 'nullable|string',
        'form.is_active' => 'boolean',
    ];

    public function mount()
    {
        // Setze Standard-Datum auf heute
        $this->form['valid_from'] = now()->toDateString();
    }

    #[Computed]
    public function teamId()
    {
        return auth()->user()->currentTeam?->id ?? auth()->user()->current_team_id;
    }

    #[Computed]
    public function mission()
    {
        return StrategicDocument::active('mission')
            ->forTeam($this->teamId)
            ->first();
    }

    #[Computed]
    public function vision()
    {
        return StrategicDocument::active('vision')
            ->forTeam($this->teamId)
            ->first();
    }

    #[Computed]
    public function regnose()
    {
        return StrategicDocument::active('regnose')
            ->forTeam($this->teamId)
            ->first();
    }

    #[Computed]
    public function allMissions()
    {
        return StrategicDocument::ofType('mission')
            ->forTeam($this->teamId)
            ->orderBy('version', 'desc')
            ->get();
    }

    #[Computed]
    public function allVisions()
    {
        return StrategicDocument::ofType('vision')
            ->forTeam($this->teamId)
            ->orderBy('version', 'desc')
            ->get();
    }

    #[Computed]
    public function allRegnoses()
    {
        return StrategicDocument::ofType('regnose')
            ->forTeam($this->teamId)
            ->orderBy('version', 'desc')
            ->get();
    }

    public function openCreateModal($type = 'mission')
    {
        $this->resetForm();
        $this->form['type'] = $type;
        $this->form['valid_from'] = now()->toDateString();
        $this->createModalShow = true;
    }

    public function openEditModal($documentId)
    {
        $document = StrategicDocument::findOrFail($documentId);
        $this->editingDocumentId = $document->id;
        $this->form = [
            'type' => $document->type,
            'title' => $document->title,
            'content' => $document->content,
            'valid_from' => $document->valid_from->toDateString(),
            'change_note' => '',
            'is_active' => $document->is_active,
        ];
        $this->editModalShow = true;
    }

    public function openVersionModal($documentId)
    {
        $document = StrategicDocument::findOrFail($documentId);
        $this->editingDocumentId = $document->id;
        $this->form = [
            'type' => $document->type,
            'title' => $document->title,
            'content' => $document->content,
            'valid_from' => now()->toDateString(),
            'change_note' => '',
            'is_active' => true,
        ];
        $this->versionModalShow = true;
    }

    public function openViewVersionsModal($type)
    {
        $this->viewingVersionsType = $type;
        $this->versions = StrategicDocument::ofType($type)
            ->forTeam($this->teamId)
            ->orderBy('version', 'desc')
            ->get();
        $this->viewVersionsModalShow = true;
    }

    public function closeModals()
    {
        $this->createModalShow = false;
        $this->editModalShow = false;
        $this->versionModalShow = false;
        $this->viewVersionsModalShow = false;
        $this->resetForm();
    }

    public function createDocument()
    {
        $this->validate();

        StrategicDocument::create([
            'type' => $this->form['type'],
            'title' => $this->form['title'],
            'content' => $this->form['content'],
            'valid_from' => $this->form['valid_from'],
            'change_note' => $this->form['change_note'],
            'is_active' => $this->form['is_active'],
            'team_id' => $this->teamId,
        ]);

        $this->closeModals();
        session()->flash('message', 'Strategisches Dokument erfolgreich erstellt!');
    }

    public function updateDocument()
    {
        $this->validate();

        $document = StrategicDocument::findOrFail($this->editingDocumentId);
        
        // Wenn sich Inhalt oder Titel geändert hat, erstelle neue Version
        if ($document->content !== $this->form['content'] || $document->title !== $this->form['title']) {
            $document->createNewVersion([
                'title' => $this->form['title'],
                'content' => $this->form['content'],
                'valid_from' => $this->form['valid_from'],
                'change_note' => $this->form['change_note'],
            ]);
            session()->flash('message', 'Neue Version erfolgreich erstellt!');
        } else {
            // Nur Metadaten aktualisieren
            $document->update([
                'change_note' => $this->form['change_note'],
            ]);
            session()->flash('message', 'Dokument erfolgreich aktualisiert!');
        }

        $this->closeModals();
    }

    public function createNewVersion()
    {
        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.content' => 'nullable|string',
            'form.valid_from' => 'required|date',
            'form.change_note' => 'nullable|string',
        ]);

        $document = StrategicDocument::findOrFail($this->editingDocumentId);
        $document->createNewVersion([
            'title' => $this->form['title'],
            'content' => $this->form['content'],
            'valid_from' => $this->form['valid_from'],
            'change_note' => $this->form['change_note'],
        ]);

        $this->closeModals();
        session()->flash('message', 'Neue Version erfolgreich erstellt!');
    }

    public function activateVersion($documentId)
    {
        $document = StrategicDocument::findOrFail($documentId);
        
        // Setze alle anderen Versionen des gleichen Typs auf inaktiv
        StrategicDocument::where('type', $document->type)
            ->where('team_id', $document->team_id)
            ->where('id', '!=', $document->id)
            ->update(['is_active' => false]);

        // Aktiviere diese Version
        $document->update(['is_active' => true]);

        session()->flash('message', 'Version erfolgreich aktiviert!');
    }

    public function resetForm()
    {
        $this->form = [
            'type' => 'mission',
            'title' => '',
            'content' => '',
            'valid_from' => now()->toDateString(),
            'change_note' => '',
            'is_active' => true,
        ];
        $this->editingDocumentId = null;
    }

    public function getTypeLabel($type)
    {
        return match($type) {
            'mission' => 'Mission',
            'vision' => 'Vision',
            'regnose' => 'Regnose',
            default => $type,
        };
    }

    public function getTypeIcon($type)
    {
        return match($type) {
            'mission' => 'heroicon-o-document-text',
            'vision' => 'heroicon-o-sun',
            'regnose' => 'heroicon-o-sparkles',
            default => 'heroicon-o-document',
        };
    }

    public function getTypeDescription($type)
    {
        return match($type) {
            'mission' => 'Die Mission beschreibt, warum die Organisation heute existiert und welchen übergeordneten Zweck sie erfüllt. Zeitlich stabil, selten geändert, keine KPIs/OKRs, Referenz für Entscheidungen.',
            'vision' => 'Die Vision beschreibt einen bewusst angestrebten zukünftigen Zustand der Organisation. Normativ (gewollt, nicht prognostiziert), langfristig (5–10 Jahre), keine Key Results, dient als "North Star".',
            'regnose' => 'Die Regnose beschreibt erwartete Entwicklungen im Markt, in der Technologie oder Organisation – unabhängig vom eigenen Handeln. Deskriptiv, nicht wertend, annahmenbasiert, änderbar/überprüfbar, Begründung für strategische Entscheidungen.',
            default => '',
        };
    }

    public function render()
    {
        return view('okr::livewire.strategic-documents-management')
            ->layout('platform::layouts.app');
    }
}

