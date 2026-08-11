@extends('layouts.app')

@section('content')
<style>
.ai-bot-page { max-width: 1200px; margin: 0 auto; }
.ai-bot-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:14px; flex-wrap:wrap; }
.ai-bot-head h1 { margin:0; font-size:24px; font-weight:850; }
.ai-bot-shell { border:1px solid #e5e7eb; border-radius:8px; background:#fff; overflow:hidden; }
.ai-bot-messages { min-height:440px; max-height:58vh; overflow:auto; padding:16px; background:#f9fafb; }
.ai-bot-message { max-width:82%; margin-bottom:12px; padding:11px 13px; border-radius:8px; white-space:pre-wrap; line-height:1.45; }
.ai-bot-message.user { margin-left:auto; background:#111827; color:#fff; }
.ai-bot-message.bot { background:#fff; border:1px solid #e5e7eb; color:#111827; }
.ai-bot-form { display:flex; gap:10px; padding:12px; border-top:1px solid #e5e7eb; background:#fff; }
.ai-bot-form textarea { resize:none; min-height:46px; max-height:130px; }
.ai-bot-examples { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
.ai-bot-example { border:1px solid #d1d5db; background:#fff; border-radius:999px; padding:7px 11px; cursor:pointer; font-size:13px; }
.ai-bot-example:hover { background:#f3f4f6; }
.ai-bot-meta { font-size:12px; color:#6b7280; margin-top:6px; }
@media (max-width: 768px) { .ai-bot-message { max-width:100%; } .ai-bot-form { flex-direction:column; } }
</style>

<div class="ai-bot-page">
    <div class="ai-bot-head">
        <div>
            <h1><i class="fas fa-robot"></i> AI Bot</h1>
            <div class="text-muted">Assistant operationnel interne. Disponible uniquement pour les super administrateurs.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('ai-bot.quick-transfer') }}" class="btn btn-primary btn-sm"><i class="fas fa-bolt"></i> Création rapide</a>
            <a href="{{ route('whatsapp.inbox') }}" class="btn btn-outline-secondary btn-sm">Messages WhatsApp</a>
        </div>
    </div>

    <div class="ai-bot-examples">
        <button type="button" class="ai-bot-example">Yarınki transferleri yaz</button>
        <button type="button" class="ai-bot-example">Bugünkü arrival saat kaç?</button>
        <button type="button" class="ai-bot-example">Demain quels chauffeurs manquent ?</button>
        <button type="button" class="ai-bot-example">#28082 arrival saat kaç?</button>
    </div>

    <div class="ai-bot-shell">
        <div id="aiBotMessages" class="ai-bot-messages">
            <div class="ai-bot-message bot">Bonjour. Pose une question operationnelle: transferts de demain, horaires arrival/departure, chauffeur, vehicule, statut, dossier ou numero de transfert.</div>
        </div>
        <form id="aiBotForm" class="ai-bot-form">
            @csrf
            <textarea id="aiBotQuestion" class="form-control" placeholder="Ex: yarınki transferleri yaz" required></textarea>
            <button id="aiBotSend" class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    function addMessage(type, text, meta) {
        var box = $('#aiBotMessages');
        var node = $('<div>').addClass('ai-bot-message').addClass(type).text(text || '');
        if (meta) {
            node.append($('<div>').addClass('ai-bot-meta').text(meta));
        }
        box.append(node);
        box.scrollTop(box[0].scrollHeight);
    }

    $('.ai-bot-example').on('click', function () {
        $('#aiBotQuestion').val($(this).text()).focus();
    });

    $('#aiBotForm').on('submit', function (event) {
        event.preventDefault();
        var question = $('#aiBotQuestion').val().trim();
        if (!question) return;

        addMessage('user', question);
        $('#aiBotQuestion').val('');
        $('#aiBotSend').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ route('ai-bot.ask') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                question: question
            },
            success: function (response) {
                var meta = response.context_summary ? (response.context_summary.period + ' - ' + response.context_summary.count + ' transfert(s) charge(s)') : '';
                addMessage('bot', response.answer || 'Aucune reponse.', meta);
            },
            error: function (xhr) {
                var answer = (xhr.responseJSON && xhr.responseJSON.answer) ? xhr.responseJSON.answer : 'Erreur AI Bot.';
                addMessage('bot', answer);
            },
            complete: function () {
                $('#aiBotSend').prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
            }
        });
    });
})();
</script>
@endsection
