{{-- STEP 1: NUR ROTE BOX --}}
<div style="background: red; color: white; padding: 20px; font-size: 24px;">
    <h1>LIVEWIRE FUNKTIONIERT!</h1>
    <p>OKR ID: {{ $okr->id ?? 'KEINE ID' }}</p>
    <p>OKR Title: {{ $okr->title ?? 'KEIN TITLE' }}</p>
    <p>OKR Description: {{ $okr->description ?? 'KEINE DESCRIPTION' }}</p>
</div>