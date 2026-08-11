@extends('layouts.app')

@section('style')
<style>
/* ── Layout ── */
.dash { display:grid; gap:20px; }

/* ── Section header ── */
.dash-section-title {
    font-size:.75rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.08em; color:#6c757d; margin-bottom:10px;
}

/* ── Stat tiles ── */
.stat-row { display:flex; flex-wrap:wrap; gap:12px; }
.stat-tile {
    flex:1; min-width:110px; max-width:160px;
    border-radius:10px; padding:14px 16px;
    border:1px solid #e5e7eb; background:#fff;
    text-decoration:none; color:inherit;
    transition:box-shadow .15s;
}
.stat-tile:hover { box-shadow:0 4px 14px rgba(0,0,0,.1); color:inherit; }
.stat-tile .st-val { font-size:1.9rem; font-weight:800; line-height:1; }
.stat-tile .st-lbl { font-size:.75rem; color:#6c757d; margin-top:4px; }

/* ── Talep tiles ── */
.talep-row { display:flex; flex-wrap:wrap; gap:10px; }
.talep-tile {
    flex:1; min-width:100px;
    border-radius:10px; padding:12px 14px;
    border-width:2px; border-style:solid;
    text-decoration:none; color:inherit;
    transition:box-shadow .15s;
}
.talep-tile:hover { box-shadow:0 4px 14px rgba(0,0,0,.12); color:inherit; }
.talep-tile .tt-val { font-size:1.8rem; font-weight:800; line-height:1; }
.talep-tile .tt-lbl { font-size:.76rem; font-weight:600; margin-top:3px; }

/* ── Véhicule tiles ── */
.veh-row { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:14px; }
.veh-tile {
    flex:1; min-width:110px;
    border-radius:10px; padding:14px 16px;
    border:1px solid #e5e7eb; background:#fff;
    text-decoration:none; color:inherit;
    transition:box-shadow .15s;
}
.veh-tile:hover { box-shadow:0 4px 14px rgba(0,0,0,.1); color:inherit; }
.veh-tile .vt-val { font-size:1.9rem; font-weight:800; line-height:1; }
.veh-tile .vt-lbl { font-size:.75rem; color:#6c757d; margin-top:4px; }

/* ── Alert list ── */
.doc-alert { display:flex; align-items:center; gap:8px; padding:7px 10px;
    border-radius:7px; margin-bottom:5px; font-size:.85rem; }
.doc-alert.exp  { background:#fff0f0; border:1px solid #f5c6cb; }
.doc-alert.warn { background:#fffbec; border:1px solid #ffeeba; }
.doc-alert .da-name { font-weight:600; min-width:140px; }
.doc-alert .da-tags { display:flex; flex-wrap:wrap; gap:4px; }
.doc-alert .da-tag  { font-size:.7rem; font-weight:700; padding:1px 6px;
    border-radius:4px; }
.doc-alert.exp  .da-tag { background:#dc3545; color:#fff; }
.doc-alert.warn .da-tag { background:#fd7e14; color:#fff; }

/* ── Sticky notes ── */
.sticky-row { display:flex; flex-wrap:wrap; gap:12px; }
.sticky-card {
    width:200px; min-height:120px;
    border-radius:10px; padding:14px;
    font-size:.85rem; position:relative;
    box-shadow:0 3px 10px rgba(0,0,0,.12);
}
.sticky-card .sc-del {
    position:absolute; top:6px; right:8px;
    background:none; border:none; font-size:1rem;
    cursor:pointer; color:rgba(0,0,0,.35);
    line-height:1; padding:0;
}
.sticky-card .sc-del:hover { color:rgba(0,0,0,.7); }
.sticky-add { display:flex; gap:8px; align-items:flex-start; }
.sticky-add textarea { flex:1; border-radius:8px; border:1px solid #dee2e6;
    padding:8px; font-size:.85rem; resize:vertical; }
.color-picker { display:flex; gap:6px; flex-wrap:wrap; }
.color-dot {
    width:22px; height:22px; border-radius:50%; cursor:pointer;
    border:2px solid transparent; transition:transform .1s;
}
.color-dot.selected, .color-dot:hover { transform:scale(1.25); border-color:#333; }
</style>
@endsection

@section('content')
@php
    $talepTotal = collect($talepOrder)->sum(fn($s) => $talepStatuts[$s] ?? 0);
@endphp

<div class="container-fluid dash">

    {{-- ══ Opérations ══ --}}
    <div>
        <div class="dash-section-title">Opérations</div>
        <div class="stat-row">
            <a href="{{ url('/ev') }}?dateOption=today" class="stat-tile" style="border-color:#0d6efd;">
                <div class="st-val" style="color:#0d6efd;">{{ $ops['today'] }}</div>
                <div class="st-lbl">Aujourd'hui</div>
            </a>
            <a href="{{ url('/ev') }}?dateOption=tomorrow" class="stat-tile" style="border-color:#6610f2;">
                <div class="st-val" style="color:#6610f2;">{{ $ops['tomorrow'] }}</div>
                <div class="st-lbl">Demain</div>
            </a>
            <a href="{{ url('/ev') }}" class="stat-tile" style="border-color:#198754;">
                <div class="st-val" style="color:#198754;">{{ $ops['week'] }}</div>
                <div class="st-lbl">Cette semaine</div>
            </a>
            <a href="{{ url('/ev') }}" class="stat-tile" style="border-color:#fd7e14;">
                <div class="st-val" style="color:#fd7e14;">{{ $ops['month'] }}</div>
                <div class="st-lbl">Ce mois</div>
            </a>
            @if($ops['no_driver'] > 0)
            <a href="{{ url('/ev') }}" class="stat-tile" style="border-color:#dc3545; background:#fff5f5;">
                <div class="st-val" style="color:#dc3545;">{{ $ops['no_driver'] }}</div>
                <div class="st-lbl">Sans chauffeur (7j)</div>
            </a>
            @endif
        </div>
    </div>

    {{-- ══ Demandes (Talepler) ══ --}}
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="dash-section-title mb-0">Demandes</div>
            <a href="{{ route('talepler.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">Voir toutes →</a>
        </div>
        <div class="talep-row">
            @foreach($talepOrder as $statut)
            @php $cnt = $talepStatuts[$statut] ?? 0; $c = $talepColors[$statut]; @endphp
            <a href="{{ route('talepler.index') }}"
               class="talep-tile"
               style="background:{{ $c['bg'] }}; border-color:{{ $c['border'] }}; color:{{ $c['color'] }};">
                <div class="tt-val">{{ $cnt }}</div>
                <div class="tt-lbl">{{ $statut }}</div>
            </a>
            @endforeach
        </div>
    </div>

    {{-- ══ Véhicules ══ --}}
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="dash-section-title mb-0">Véhicules</div>
            <a href="{{ route('vehicules.controle-docs') }}" class="btn btn-sm btn-outline-secondary ms-auto">Contrôle docs →</a>
        </div>
        <div class="veh-row">
            <a href="{{ route('vehicules.index') }}" class="veh-tile" style="border-color:#0d6efd;">
                <div class="vt-val" style="color:#0d6efd;">{{ $vTotal }}</div>
                <div class="vt-lbl">Véhicules réels</div>
            </a>
            @if($vPanne > 0)
            <a href="{{ route('vehicules.index') }}" class="veh-tile" style="border-color:#dc3545; background:#fff5f5;">
                <div class="vt-val" style="color:#dc3545;">{{ $vPanne }}</div>
                <div class="vt-lbl">En panne</div>
            </a>
            @endif
            @if($vExpired > 0)
            <a href="{{ route('vehicules.controle-docs') }}" class="veh-tile" style="border-color:#dc3545; background:#fff5f5;">
                <div class="vt-val" style="color:#dc3545;">{{ $vExpired }}</div>
                <div class="vt-lbl">Docs expirés</div>
            </a>
            @endif
            @if($vWarn > 0)
            <a href="{{ route('vehicules.controle-docs') }}" class="veh-tile" style="border-color:#fd7e14; background:#fffbec;">
                <div class="vt-val" style="color:#fd7e14;">{{ $vWarn }}</div>
                <div class="vt-lbl">Docs &lt; 30 j</div>
            </a>
            @endif
        </div>

        @if(count($docAlerts) > 0)
        <div style="max-height:260px; overflow-y:auto;">
            @foreach($docAlerts as $alert)
            <a href="{{ route('vehicules.show', $alert['id']) }}" class="doc-alert {{ $alert['type'] }}" style="text-decoration:none;">
                <div class="da-name">{{ $alert['name'] }} <span style="font-weight:400;color:#666;">{{ $alert['plaka'] }}</span></div>
                <div class="da-tags">
                    @foreach($alert['labels'] as $lbl)
                    <span class="da-tag">{{ $lbl }}</span>
                    @endforeach
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ══ Sticky Notes ══ --}}
    <div>
        <div class="dash-section-title">Notes</div>
        <div class="sticky-row" id="stickyContainer">
            @foreach($stickyNotes as $note)
            <div class="sticky-card" style="background:{{ $note->color ?? '#fef9c3' }};" data-id="{{ $note->id }}">
                <button class="sc-del" onclick="deleteNote({{ $note->id }}, this)">✕</button>
                <div>{{ $note->content }}</div>
            </div>
            @endforeach
        </div>

        <div class="sticky-add mt-3">
            <div style="flex:1;">
                <textarea id="newNoteContent" rows="2" class="form-control" placeholder="Nouvelle note…"></textarea>
                <div class="color-picker mt-2">
                    @foreach(['#fef9c3','#d1fae5','#dbeafe','#fce7f3','#ffe4e6','#e0e7ff','#f3f4f6'] as $col)
                    <div class="color-dot {{ $loop->first ? 'selected' : '' }}"
                         style="background:{{ $col }};"
                         data-color="{{ $col }}"
                         onclick="selectColor(this)"></div>
                    @endforeach
                </div>
            </div>
            <button class="btn btn-sm btn-warning mt-1" onclick="addNote()">+ Note</button>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
let selectedColor = '#fef9c3';

function selectColor(el) {
    document.querySelectorAll('.color-dot').forEach(d => d.classList.remove('selected'));
    el.classList.add('selected');
    selectedColor = el.dataset.color;
}

function addNote() {
    const content = document.getElementById('newNoteContent').value.trim();
    if (!content) return;

    fetch('/sticky-notes', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ content, color: selectedColor })
    })
    .then(r => r.json())
    .then(note => {
        const div = document.createElement('div');
        div.className = 'sticky-card';
        div.style.background = note.color;
        div.dataset.id = note.id;
        div.innerHTML = `<button class="sc-del" onclick="deleteNote(${note.id}, this)">✕</button><div>${note.content}</div>`;
        document.getElementById('stickyContainer').appendChild(div);
        document.getElementById('newNoteContent').value = '';
    });
}

function deleteNote(id, btn) {
    if (!confirm('Supprimer cette note ?')) return;
    fetch('/sticky-notes/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    }).then(() => btn.closest('.sticky-card').remove());
}
</script>
@endsection
