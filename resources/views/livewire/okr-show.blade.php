{{-- STEP 2: ALLES IN X-UI-PAGE --}}
<x-ui-page>
    <x-slot name="content">
        {{-- DEBUG BOX --}}
        <div style="background: red; color: white; padding: 20px; font-size: 24px; margin-bottom: 20px;">
            <h1>LIVEWIRE FUNKTIONIERT!</h1>
            <p>OKR ID: {{ $okr->id ?? 'KEINE ID' }}</p>
            <p>OKR Title: {{ $okr->title ?? 'KEIN TITLE' }}</p>
            <p>OKR Description: {{ $okr->description ?? 'KEINE DESCRIPTION' }}</p>
        </div>
        
        <div class="p-4">
            <h2 class="text-2xl font-bold text-green-600">X-UI-PAGE FUNKTIONIERT!</h2>
            <p>Wenn du das siehst, funktioniert die x-ui-page Komponente!</p>
        </div>
    </x-slot>
</x-ui-page>