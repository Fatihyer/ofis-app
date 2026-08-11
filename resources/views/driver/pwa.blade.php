<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Paris Via Chauffeur">
    <link rel="manifest" href="{{ asset('driver-app.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/driver-icon-192.png') }}">
    <title>Paris Via Chauffeur</title>
    <style>
        :root { color-scheme: light; --ink:#0f172a; --muted:#64748b; --line:#dbe4ef; --soft:#f8fafc; --brand:#0f172a; --ok:#16a34a; --warn:#f59e0b; --bad:#dc2626; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background:#eef3f8; color:var(--ink); padding-bottom:86px; }
        a { color:inherit; text-decoration:none; }
        .app-shell { max-width: 760px; margin: 0 auto; min-height:100vh; background:#f8fafc; }
        .hero { background:linear-gradient(135deg,#0f172a,#1e293b); color:white; padding: calc(18px + env(safe-area-inset-top)) 16px 18px; border-bottom-left-radius:22px; border-bottom-right-radius:22px; box-shadow:0 14px 34px rgba(15,23,42,.24); }
        .topline { display:flex; justify-content:space-between; gap:12px; align-items:center; position:relative; }
        .brand { font-size:18px; font-weight:800; letter-spacing:.2px; }
        .logout { font-size:12px; opacity:.9; border:1px solid rgba(255,255,255,.25); padding:8px 10px; border-radius:999px; }
        .menu-toggle { border:1px solid rgba(255,255,255,.28); background:rgba(255,255,255,.12); color:white; border-radius:999px; padding:9px 12px; font-weight:900; font-size:13px; display:inline-flex; align-items:center; gap:7px; }
        .menu-toggle span { width:16px; height:2px; background:white; display:block; position:relative; }
        .menu-toggle span::before, .menu-toggle span::after { content:""; position:absolute; left:0; width:16px; height:2px; background:white; }
        .menu-toggle span::before { top:-5px; }
        .menu-toggle span::after { top:5px; }
        .driver-menu { display:none; position:absolute; right:0; top:44px; min-width:210px; background:white; color:var(--ink); border-radius:16px; box-shadow:0 18px 38px rgba(15,23,42,.28); overflow:hidden; z-index:20; border:1px solid rgba(15,23,42,.08); }
        .driver-menu.open { display:block; }
        .driver-menu a, .driver-menu button { width:100%; border:0; background:white; color:var(--ink); display:flex; align-items:center; justify-content:space-between; gap:10px; padding:13px 14px; font:inherit; font-size:14px; font-weight:800; text-align:left; }
        .driver-menu a + a, .driver-menu form + a, .driver-menu a + form { border-top:1px solid #edf2f7; }
        .driver-menu button { color:#b91c1c; cursor:pointer; }
        .hero h1 { margin:18px 0 6px; font-size:25px; line-height:1.1; }
        .hero p { margin:0; color:#cbd5e1; font-size:13px; }
        .stats { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-top:16px; }
        .stat { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.16); border-radius:14px; padding:11px; }
        .stat span { display:block; color:#cbd5e1; font-size:11px; text-transform:uppercase; }
        .stat strong { font-size:24px; }
        .tabs { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; padding:14px 14px 6px; position:sticky; top:0; z-index:4; background:rgba(248,250,252,.94); backdrop-filter:blur(12px); }
        .tab { text-align:center; padding:10px 8px; border-radius:12px; background:white; border:1px solid var(--line); font-weight:700; font-size:13px; color:var(--muted); }
        .tab.active { background:var(--brand); color:white; border-color:var(--brand); }
        .list { padding:8px 14px 20px; }
        .transfer-card { background:white; border:1px solid var(--line); border-radius:18px; margin-bottom:12px; overflow:hidden; box-shadow:0 10px 26px rgba(15,23,42,.06); }
        .card-head { padding:14px; display:flex; justify-content:space-between; gap:10px; align-items:flex-start; border-left:5px solid #94a3b8; }
        .card-head.confirmed { border-left-color:var(--ok); }
        .card-head.waiting { border-left-color:var(--warn); }
        .card-head.refused { border-left-color:var(--bad); }
        .time { font-size:25px; font-weight:900; line-height:1; }
        .service { font-weight:800; margin-top:5px; font-size:15px; }
        .meta { color:var(--muted); font-size:12px; margin-top:3px; }
        .pill { display:inline-block; font-size:11px; padding:5px 8px; border-radius:999px; background:#e2e8f0; color:#334155; font-weight:800; white-space:nowrap; }
        .pill.ok { background:#dcfce7; color:#166534; }
        .pill.bad { background:#fee2e2; color:#991b1b; }
        .pill.warn { background:#fef3c7; color:#92400e; }
        .route { padding:0 14px 12px; display:grid; gap:8px; }
        .route-row { display:grid; grid-template-columns:22px 1fr; gap:8px; align-items:start; color:#1f2937; font-size:13px; }
        .dot { width:18px; height:18px; border-radius:50%; background:#e2e8f0; display:inline-flex; align-items:center; justify-content:center; font-size:10px; font-weight:900; margin-top:1px; }
        .actions { padding:12px 14px 14px; display:grid; grid-template-columns:repeat(2,1fr); gap:8px; border-top:1px solid #edf2f7; }
        .btn { border:0; border-radius:12px; padding:11px 10px; font-weight:800; font-size:13px; text-align:center; cursor:pointer; min-height:42px; display:flex; align-items:center; justify-content:center; gap:6px; }
        .btn.primary { background:#2563eb; color:white; }
        .btn.success { background:#16a34a; color:white; }
        .btn.danger { background:#fee2e2; color:#991b1b; }
        .btn.dark { background:#0f172a; color:white; }
        .btn.light { background:#eef2f7; color:#1f2937; }
        .empty { text-align:center; padding:44px 24px; color:var(--muted); }
        .tools { border-top:1px solid #edf2f7; padding:10px 14px 14px; }
        .tools summary { cursor:pointer; font-weight:900; color:#1f2937; }
        .tool-grid { display:grid; grid-template-columns:1fr; gap:10px; margin-top:10px; }
        .field, .select, .textarea { width:100%; border:1px solid var(--line); border-radius:12px; padding:10px; font:inherit; background:white; }
        .capture { display:block; width:100%; border:1px dashed #94a3b8; border-radius:12px; padding:10px; background:#f8fafc; }
        .signature-pad { width:100%; height:130px; border:1px solid var(--line); border-radius:12px; background:white; touch-action:none; }
        .qr-video { width:100%; border-radius:12px; background:#0f172a; display:none; margin-top:8px; }
        .hint { color:var(--muted); font-size:12px; margin-top:4px; }
        .notify-row { padding:10px 14px 0; }
        .bottom-nav { position:fixed; left:0; right:0; bottom:0; z-index:9; padding:8px 14px calc(8px + env(safe-area-inset-bottom)); background:rgba(255,255,255,.96); border-top:1px solid var(--line); backdrop-filter: blur(12px); }
        .bottom-inner { max-width:760px; margin:0 auto; display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .mini { display:block; text-align:center; padding:9px 5px; border-radius:12px; font-size:12px; font-weight:800; color:#475569; }
        .mini.active { background:#e2e8f0; color:#0f172a; }
        @media (min-width:680px) { .actions { grid-template-columns:repeat(4,1fr); } .hero h1{font-size:30px;} }
    </style>
</head>
<body>
<div class="app-shell">
    <header class="hero">
        <div class="topline">
            <div class="brand">Paris Via Chauffeur</div>
            <div>
                <button class="menu-toggle" id="driverMenuToggle" type="button" aria-expanded="false" aria-controls="driverMenu">
                    <span aria-hidden="true"></span> Menu
                </button>
                <div class="driver-menu" id="driverMenu">
                    <a href="{{ route('driver.app') }}">Services <small>Accueil</small></a>
                    <a href="{{ route('missionlist') }}">Mes missions <small>Suivi</small></a>
                    <a href="{{ route('kaptanshow') }}">Mes comptes <small>Soldes</small></a>
                    <form id="driver-logout-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit">Déconnexion <small>Quitter</small></button>
                    </form>
                </div>
            </div>
        </div>
        <h1>Mes services</h1>
        <p>{{ now()->format('d/m/Y H:i') }} · Application installable</p>
        <div class="stats">
            <div class="stat"><span>Services</span><strong>{{ $stats['total'] }}</strong></div>
            <div class="stat"><span>Confirmés</span><strong>{{ $stats['confirmed'] }}</strong></div>
        </div>
    </header>

    <nav class="tabs">
        <a class="tab {{ $dateOption === 'yesterday' ? 'active' : '' }}" href="{{ route('driver.app', ['dateOption' => 'yesterday']) }}">Hier</a>
        <a class="tab {{ $dateOption === 'today' ? 'active' : '' }}" href="{{ route('driver.app', ['dateOption' => 'today']) }}">Aujourd'hui</a>
        <a class="tab {{ $dateOption === 'tomorrow' ? 'active' : '' }}" href="{{ route('driver.app', ['dateOption' => 'tomorrow']) }}">Demain</a>
    </nav>

    <div class="notify-row"><button class="btn light" id="enableNotifications" type="button" style="width:100%;">Activer les notifications sur ce téléphone</button></div>

    @php
        $surplaceMinutes = (int) (optional(\App\Models\Option::where('name', 'surplaceMinBefore')->first())->value ?? 15);
    @endphp

    <main class="list">
        @forelse($transfers as $transfer)
            @php
                $start = \Carbon\Carbon::parse($transfer->start_date);
                $end = \Carbon\Carbon::parse($transfer->end_date);
                $surplace = $start->copy()->subMinutes($surplaceMinutes);
                $isSecond = \Illuminate\Support\Facades\Schema::hasColumn('transfers', 'second_driver_id')
                    && in_array((int) ($transfer->second_driver_id ?? 0), $acenteIds, true)
                    && !in_array((int) ($transfer->driver_id ?? 0), $acenteIds, true);
                $confirmedAt = $isSecond ? $transfer->second_driver_app_confirmed_at : $transfer->driver_app_confirmed_at;
                $refusedAt = $isSecond ? ($transfer->second_driver_app_refused_at ?? null) : ($transfer->driver_app_refused_at ?? null);
                $reconfirmAt = $isSecond
                    ? ($transfer->second_driver_app_reconfirm_required_at ?? null)
                    : ($transfer->driver_app_reconfirm_required_at ?? null);
                $stateClass = $refusedAt ? 'refused' : ($confirmedAt ? 'confirmed' : 'waiting');
                $firstClientPhone = optional($transfer->post)->client ? optional($transfer->post)->client->map(fn($client) => preg_replace('/\D+/', '', $client->tel ?? ''))->filter()->first() : null;
                $mapsQuery = trim(($transfer->from ?: '') . ' ' . ($transfer->target ?: ''));
            @endphp
            <article class="transfer-card">
                <div class="card-head {{ $stateClass }}">
                    <div>
                        <div class="time">{{ $surplace->format('H:i') }}</div>
                        <div class="service">{{ optional($transfer->servicetype)->name ?: 'Service' }}</div>
                        <div class="meta">#{{ $transfer->id }} · Sur place · Client {{ $start->format('H:i') }} → {{ $end->format('H:i') }}</div>
                    </div>
                    <div>
                        @if($refusedAt)
                            <span class="pill bad">Refusé</span>
                        @elseif($confirmedAt)
                            <span class="pill ok">Confirmé</span>
                        @elseif($reconfirmAt)
                            <span class="pill warn">Modifié, à reconfirmer</span>
                        @else
                            <span class="pill warn">À confirmer</span>
                        @endif
                        @if($isSecond)
                            <div class="meta" style="text-align:right;margin-top:6px;">2e chauffeur</div>
                        @endif
                    </div>
                </div>
                <div class="route">
                    <div class="route-row"><span class="dot">D</span><span><strong>Sur place {{ $surplace->format('H:i') }}</strong> · {{ $transfer->from ?: '-' }}</span></div>
                    <div class="route-row"><span class="dot">A</span><span>{{ $transfer->target ?: '-' }}</span></div>
                    <div class="route-row"><span class="dot">V</span><span>{{ trim((optional($transfer->vehicule)->name ?: 'Véhicule non défini').' '.(optional($transfer->vehicule)->plaka ?: '')) }}</span></div>
                </div>
                <div class="actions">
                    @if(!$confirmedAt && !$refusedAt)
                        @if($reconfirmAt)
                            <div class="hint" style="grid-column:1/-1;color:#92400e;font-weight:800;">Service modifié après votre réponse. Merci de confirmer à nouveau.</div>
                        @endif
                        <form method="POST" action="{{ route('transfers.driverAppConfirm', $transfer->id) }}" data-offline-form>@csrf<button class="btn success" type="submit">Confirmer</button></form>
                        <form method="POST" action="{{ route('transfers.driverAppRefuse', $transfer->id) }}" data-offline-form>
                            @csrf
                            <select class="select" name="driver_refusal_reason" aria-label="Motif du refus">
                                <option value="Indisponible">Indisponible</option>
                                <option value="Malade">Malade</option>
                                <option value="Véhicule en panne">Véhicule en panne</option>
                                <option value="Erreur planning">Erreur planning</option>
                                <option value="Autre motif">Autre motif</option>
                            </select>
                            <button class="btn danger" type="submit" onclick="return confirm('Refuser ce service ?')">Refuser</button>
                        </form>
                    @else
                        <a class="btn light" href="{{ route('showdriver', $transfer->id) }}">Détail</a>
                    @endif

                    @php $mission = $transfer->missionr; @endphp
                    @if($confirmedAt && $mission && $mission->hareket && !$mission->surplace)
                        <a class="btn success" href="{{ route('onplacemission', $mission->id) }}">Je suis arrivé</a>
                    @elseif($confirmedAt && $mission && $mission->surplace && !$mission->taked)
                        <a class="btn success" href="{{ route('onboardmission', $mission->id) }}">Client à bord</a>
                    @elseif($confirmedAt && $mission && $mission->taked && !$mission->finish)
                        <a class="btn success" href="{{ route('finishmission', $mission->id) }}">Terminé</a>
                    @else
                        <a class="btn primary" href="{{ route('showdriver', $transfer->id) }}">Mission</a>
                    @endif

                    @if($mapsQuery)
                        <a class="btn dark" target="_blank" href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($mapsQuery) }}">Maps</a>
                    @endif
                    @if($firstClientPhone)
                        <a class="btn light" href="tel:{{ $firstClientPhone }}">Appeler</a>
                    @endif
                </div>
                <details class="tools">
                    <summary>Documents, photo et signature</summary>
                    <div class="tool-grid">
                        <form action="{{ route('transfers.voucherupload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" value="{{ $transfer->id }}">
                            <input type="hidden" name="post_id" value="{{ $transfer->post_id }}">
                            <label class="hint">Photo: péage, parking, carburant, dégât</label>
                            <input class="capture" type="file" name="image" accept="image/*" capture="environment" required>
                            <button class="btn primary" type="submit" style="margin-top:8px;width:100%;">Envoyer la photo</button>
                        </form>
                        @if($mission)
                            <form action="{{ route('missions.updateKilometers', $mission->id) }}" method="POST" data-offline-form>
                                @csrf
                                <label class="hint">Kilométrage</label>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                    <input class="field" type="number" name="depart_km" min="0" max="5000000" inputmode="numeric" required value="{{ $mission->depart_km }}" placeholder="Départ km">
                                    <input class="field" type="number" name="finish_km" min="0" max="5000000" inputmode="numeric" value="{{ $mission->finish_km }}" placeholder="Retour km">
                                </div>
                                <button class="btn primary" type="submit" style="margin-top:8px;width:100%;">Enregistrer les km</button>
                            </form>
                        @endif
                        <form action="{{ route('transfers.driverNote', $transfer->id) }}" method="POST" data-offline-form>
                            @csrf
                            <label class="hint">Note chauffeur / frais / incident</label>
                            <textarea class="textarea driver-note" name="dcomments" rows="3" placeholder="Parking, péage, carburant, incident...">{{ $transfer->dcomments }}</textarea>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                                <button class="btn light voice-note" type="button">Dictée</button>
                                <button class="btn primary" type="submit">Enregistrer</button>
                            </div>
                        </form>
                        <div>
                            <button class="btn dark qr-start" type="button" style="width:100%;">Scanner QR / document</button>
                            <video class="qr-video" playsinline></video>
                            <div class="hint qr-result"></div>
                        </div>
                        <form action="{{ route('transfers.driverSignature', $transfer->id) }}" method="POST" class="signature-form">
                            @csrf
                            <label class="hint">Signature chauffeur / client</label>
                            <canvas class="signature-pad" data-transfer="{{ $transfer->id }}"></canvas>
                            <input type="hidden" name="signature" class="signature-input">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                                <button class="btn light signature-clear" type="button">Effacer</button>
                                <button class="btn dark" type="submit">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </details>
            </article>
        @empty
            <div class="empty">Aucun service à afficher pour cette période.</div>
        @endforelse
    </main>
</div>

<nav class="bottom-nav">
    <div class="bottom-inner">
        <a class="mini active" href="{{ route('driver.app') }}">Services</a>
        <a class="mini" href="{{ route('missionlist') }}">Missions</a>
        <a class="mini" href="{{ route('kaptanshow') }}">Comptes</a>
    </div>
</nav>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const driverMenuToggle = document.getElementById('driverMenuToggle');
const driverMenu = document.getElementById('driverMenu');

driverMenuToggle?.addEventListener('click', () => {
    const isOpen = driverMenu?.classList.toggle('open');
    driverMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});

document.addEventListener('click', event => {
    if (!driverMenu || !driverMenuToggle) return;
    if (driverMenu.contains(event.target) || driverMenuToggle.contains(event.target)) return;
    driverMenu.classList.remove('open');
    driverMenuToggle.setAttribute('aria-expanded', 'false');
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/driver-app-sw.js').catch(function () {});
    });
}

document.getElementById('enableNotifications')?.addEventListener('click', async () => {
    if (!('Notification' in window)) {
        alert('Notifications non supportées sur ce téléphone.');
        return;
    }
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
        new Notification('Paris Via Chauffeur', { body: 'Notifications activées sur ce téléphone.', data: { url: '/driver-app' } });
    }
});

function queueOfflineForm(form) {
    const data = Array.from(new FormData(form).entries());
    const queue = JSON.parse(localStorage.getItem('driverOfflineQueue') || '[]');
    queue.push({ action: form.action, method: form.method || 'POST', data, createdAt: new Date().toISOString() });
    localStorage.setItem('driverOfflineQueue', JSON.stringify(queue));
}

async function flushOfflineQueue() {
    const queue = JSON.parse(localStorage.getItem('driverOfflineQueue') || '[]');
    if (!queue.length || !navigator.onLine) return;
    const remaining = [];
    for (const item of queue) {
        try {
            const body = new URLSearchParams();
            item.data.forEach(([key, value]) => body.append(key, value));
            await fetch(item.action, { method: item.method.toUpperCase(), headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'text/html' }, body, credentials: 'same-origin' });
        } catch (e) {
            remaining.push(item);
        }
    }
    localStorage.setItem('driverOfflineQueue', JSON.stringify(remaining));
    if (remaining.length !== queue.length) window.location.reload();
}

document.querySelectorAll('form[data-offline-form]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!navigator.onLine) {
            event.preventDefault();
            queueOfflineForm(form);
            alert('Hors ligne: action enregistrée. Elle sera envoyée dès que la connexion revient.');
        }
    });
});
window.addEventListener('online', flushOfflineQueue);
flushOfflineQueue();

function sendDriverLocation(position) {
    fetch('{{ route('driver.app.location') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ latitude: position.coords.latitude, longitude: position.coords.longitude, accuracy: position.coords.accuracy })
    }).catch(() => {});
}
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(sendDriverLocation, function () {}, { enableHighAccuracy: true, maximumAge: 60000, timeout: 10000 });
}

document.querySelectorAll('.voice-note').forEach(button => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) return;
    button.addEventListener('click', () => {
        const textarea = button.closest('form').querySelector('.driver-note');
        const recognition = new SpeechRecognition();
        recognition.lang = 'fr-FR';
        recognition.interimResults = false;
        recognition.onresult = event => {
            textarea.value = (textarea.value ? textarea.value + "\n" : '') + event.results[0][0].transcript;
        };
        recognition.start();
    });
});

document.querySelectorAll('.qr-start').forEach(button => {
    button.addEventListener('click', async () => {
        const wrapper = button.closest('div');
        const video = wrapper.querySelector('.qr-video');
        const result = wrapper.querySelector('.qr-result');
        if (!('BarcodeDetector' in window)) {
            result.textContent = 'Scanner QR non supporté sur ce navigateur.';
            return;
        }
        try {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            video.style.display = 'block';
            await video.play();
            const timer = setInterval(async () => {
                const codes = await detector.detect(video).catch(() => []);
                if (codes.length) {
                    clearInterval(timer);
                    stream.getTracks().forEach(track => track.stop());
                    video.style.display = 'none';
                    const value = codes[0].rawValue || '';
                    result.textContent = value;
                    if (/^https?:\/\//.test(value)) window.open(value, '_blank');
                }
            }, 800);
        } catch (e) {
            result.textContent = 'Impossible d’ouvrir la caméra.';
        }
    });
});

document.querySelectorAll('.signature-form').forEach(form => {
    const canvas = form.querySelector('.signature-pad');
    const input = form.querySelector('.signature-input');
    const clearButton = form.querySelector('.signature-clear');
    const ctx = canvas.getContext('2d');
    let drawing = false;

    function resizeCanvas() {
        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';
    }
    resizeCanvas();

    function point(event) {
        const source = event.touches ? event.touches[0] : event;
        const rect = canvas.getBoundingClientRect();
        return { x: source.clientX - rect.left, y: source.clientY - rect.top };
    }
    function start(event) { drawing = true; const p = point(event); ctx.beginPath(); ctx.moveTo(p.x, p.y); event.preventDefault(); }
    function move(event) { if (!drawing) return; const p = point(event); ctx.lineTo(p.x, p.y); ctx.stroke(); event.preventDefault(); }
    function stop() { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', stop);
    canvas.addEventListener('mouseleave', stop);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', stop);

    clearButton.addEventListener('click', () => ctx.clearRect(0, 0, canvas.width, canvas.height));
    form.addEventListener('submit', event => {
        input.value = canvas.toDataURL('image/png');
    });
});
</script>
</body>
</html>
