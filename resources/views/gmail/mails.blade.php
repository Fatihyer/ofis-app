@extends('layouts.app')
@php
use Illuminate\Support\Str;
@endphp

@section('content')
<style>
.mail-workbench { max-width:none; width:100%; }
.mail-toolbar { background:#fff; border:1px solid #e7eaf0; border-radius:8px; padding:14px; }
.mail-table-wrap { background:#fff; border:1px solid #e7eaf0; border-radius:8px; overflow-x:auto; overflow-y:hidden; }
.mail-table { min-width:1680px; }
.mail-table th { white-space:nowrap; font-size:12px; text-transform:uppercase; color:#657083; letter-spacing:.02em; background:#f7f8fb; }
.mail-table td { vertical-align:top; font-size:13px; }
.mail-subject { font-weight:700; color:#1f2937; }
.mail-preview { color:#4b5563; max-width:520px; white-space:normal; }
.mail-actions { min-width:210px; }
.mail-badge { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:3px 8px; font-size:12px; font-weight:700; }
.mail-badge-ok { background:#e8f7ef; color:#166534; }
.mail-badge-warn { background:#fff7e6; color:#92400e; }
.mail-badge-done { background:#e8f0ff; color:#1d4ed8; }
.mail-badge-in { background:#ecfdf5; color:#047857; }
.mail-badge-out { background:#eef2ff; color:#4338ca; }
.mail-date { white-space:nowrap; color:#4b5563; }
.mail-xml-box { min-height:260px; font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size:12px; }
.mail-body-box { max-height:380px; overflow:auto; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px; line-height:1.45; word-break:break-word; }
.mail-body-box p { margin:0 0 8px; }
.mail-body-box blockquote { border-left:3px solid #cbd5e1; margin:8px 0; padding-left:10px; color:#64748b; }
.mail-body-box a { color:#2563eb; font-weight:800; }
.mail-body-box table { width:100%; border-collapse:collapse; margin:8px 0; }
.mail-body-box td, .mail-body-box th { border:1px solid #e5e7eb; padding:5px 7px; vertical-align:top; }
@media (max-width: 992px) {
  .mail-table { min-width:1320px; }
  .mail-actions .btn { display:block; width:100%; margin-bottom:6px; }
}
</style>

<div class="container-fluid mail-workbench">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Demandes par e-mail</h4>
            <div class="text-muted">
                Affichage rapide depuis la base.
                @if(!empty($lastSync))
                    Dernière synchro: {{ \Carbon\Carbon::parse($lastSync)->format('d/m/Y H:i') }}.
                @endif
            </div>
        </div>
        <a href="{{ route('talepler.index') }}" class="btn btn-outline-secondary btn-sm">Demandes</a>
    </div>

    @if(!empty($refreshed))
        <div class="alert alert-success">
            Synchronisation Gmail terminée: {{ $syncedCount ?? 0 }} mail(s) vérifié(s).
        </div>
    @endif

    @if(!empty($mailError))
        <div class="alert alert-warning">
            {{ $mailError }}
        </div>
    @endif

    <form method="GET" action="{{ route('gmail.mails') }}" class="mail-toolbar mb-3">
        <div class="form-row align-items-end">
            <div class="col-md-3">
                <label class="small text-muted mb-1">Boîte mail</label>
                <select name="account" class="form-control">
                    <option value="resparis" {{ request('account', 'resparis') == 'resparis' ? 'selected' : '' }}>Réservation Paris Via</option>
                    <option value="contact" {{ request('account') == 'contact' ? 'selected' : '' }}>Contact Paris Via</option>
                    <option value="paris" {{ request('account') == 'paris' ? 'selected' : '' }}>paris@parisvia.com</option>
                    <option value="sales" {{ request('account') == 'sales' ? 'selected' : '' }}>sales@parisvia.com</option>
                    <option value="sales2" {{ request('account') == 'sales2' ? 'selected' : '' }}>sales2@parisvia.com</option>
                    <option value="contactfrance" {{ request('account') == 'contactfrance' ? 'selected' : '' }}>Contact France Via</option>
                    <option value="resfrance" {{ request('account') == 'resfrance' ? 'selected' : '' }}>Réservation France Via</option>
                </select>
            </div>
            <div class="col-md-2 mt-2 mt-md-0">
                <label class="small text-muted mb-1">Période Gmail</label>
                <select name="days" class="form-control">
                    @foreach([7, 15, 30, 60, 90] as $dayOption)
                        <option value="{{ $dayOption }}" {{ (int) request('days', $days ?? 7) === $dayOption ? 'selected' : '' }}>
                            {{ $dayOption }} jours
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mt-2 mt-md-0">
                <label class="small text-muted mb-1">Nombre</label>
                <select name="limit" class="form-control">
                    @foreach([50, 100, 200, 500] as $limitOption)
                        <option value="{{ $limitOption }}" {{ (int) request('limit', $limit ?? 100) === $limitOption ? 'selected' : '' }}>
                            {{ $limitOption }} mails
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mt-2 mt-md-0">
                <button type="submit" class="btn btn-primary btn-block">Afficher</button>
            </div>
            <div class="col-md-3 mt-2 mt-md-0">
                <button type="submit" name="refresh" value="1" class="btn btn-outline-primary btn-block">
                    Actualiser depuis Gmail
                </button>
            </div>
        </div>
    </form>

    <div class="mail-table-wrap">
        <table class="table table-hover mb-0 mail-table" id="gmailMailsTable">
            <thead>
                <tr>
                    <th>Expéditeur / destinataire</th>
                    <th>Actions</th>
                    <th>Sujet</th>
                    <th>Résumé</th>
                    <th>Pièces</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mails as $mail)
                    @php
                        $payload = base64_encode(json_encode($mail, JSON_UNESCAPED_UNICODE));
                        $isOutgoing = ($mail['direction'] ?? 'in') === 'out';
                    @endphp
                    <tr>
                        <td>
                            <div><strong>De:</strong> {{ $mail['from'] ?: '-' }}</div>
                            @if($isOutgoing)
                                <div class="text-muted small"><strong>À:</strong> {{ $mail['to'] ?: '-' }}</div>
                                <span class="mail-badge mail-badge-out mt-1">Envoyé</span>
                            @else
                                @if(!empty($mail['acente']))
                                    <span class="mail-badge mail-badge-ok">{{ $mail['acente'] }}</span>
                                @else
                                    <span class="mail-badge mail-badge-warn">Agence inconnue</span>
                                @endif
                                <span class="mail-badge mail-badge-in mt-1">Reçu</span>
                            @endif
                        </td>
                        <td class="mail-actions">
                            <button type="button" class="btn btn-info btn-sm viewMailButton" data-payload="{{ $payload }}">Ouvrir</button>
                            @if(!$isOutgoing)
                                <button type="button" class="btn btn-outline-dark btn-sm xmlMailButton" data-payload="{{ $payload }}">XML</button>
                            @endif
                            @if($isOutgoing)
                                <button type="button" class="btn btn-secondary btn-sm" disabled>Réponse</button>
                            @elseif(empty($mail['existing_talep_id']))
                                <button type="button" class="btn btn-primary btn-sm createTalepButton" data-payload="{{ $payload }}">Créer</button>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm" disabled>Créée</button>
                            @endif
                        </td>
                        <td><div class="mail-subject">{{ Str::limit($mail['subject'], 90) }}</div></td>
                        <td><div class="mail-preview">{{ Str::limit(trim($mail['body']), 180) }}</div></td>
                        <td>
                            @if(!empty($mail['attachments']))
                                @foreach($mail['attachments'] as $attachment)
                                    <span class="btn btn-sm btn-outline-secondary disabled mb-1">{{ Str::limit($attachment['name'], 28) }}</span>
                                    @if(!empty($attachment['is_pdf']))
                                        @if(($attachment['parse_status'] ?? null) === 'parsed')
                                            <span class="mail-badge mail-badge-ok">PDF {{ !empty($attachment['ocr_used']) ? 'OCR' : 'texte' }}</span>
                                        @elseif(($attachment['parse_status'] ?? null) === 'error')
                                            <span class="mail-badge mail-badge-warn">PDF erreur</span>
                                        @else
                                            <span class="mail-badge">PDF {{ $attachment['parse_status'] ?? 'pending' }}</span>
                                        @endif
                                    @endif
                                    <br>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="mail-date">{{ $mail['date'] }}</td>
                        <td>
                            @if(!empty($mail['existing_talep_id']))
                                <a href="{{ $mail['existing_talep_url'] }}" class="mail-badge mail-badge-done">Demande #{{ $mail['existing_talep_id'] }}</a>
                                @if(!empty($mail['linked_by']))
                                    <div class="text-muted small mt-1">{{ $mail['linked_by'] }}</div>
                                @endif
                            @else
                                <span class="mail-badge mail-badge-warn">À traiter</span>
                                @if(!empty($mail['sync_status']))
                                    <div class="text-muted small mt-1">{{ $mail['sync_status'] }}</div>
                                @endif
                                @if(!empty($mail['talep_mail_id']))
                                    <div class="input-group input-group-sm mt-2" style="max-width: 190px;">
                                        <input type="number" min="1" class="form-control linkTalepIdInput" placeholder="# demande">
                                        <button type="button" class="btn btn-outline-primary linkMailButton" data-mail-id="{{ $mail['talep_mail_id'] }}">Lier</button>
                                    </div>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="mailDetailModal" tabindex="-1" role="dialog" aria-labelledby="mailDetailModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mailDetailModalLabel">Détail du mail</h5>
                <button type="button" class="close mail-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <h5 id="mailSubject"></h5>
                <div class="text-muted mb-2" id="mailMeta"></div>
                <div id="mailBody" class="mail-body-box"></div>
                <div id="mailAttachments" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary mail-modal-close" data-dismiss="modal" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mailXmlModal" tabindex="-1" role="dialog" aria-labelledby="mailXmlModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mailXmlModalLabel">Assistant XML</h5>
                <button type="button" class="close mail-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-5">
                        <label class="small text-muted">Texte envoyé à l'assistant</label>
                        <textarea id="mailXmlSource" class="form-control mail-xml-box"></textarea>
                    </div>
                    <div class="col-lg-7 mt-3 mt-lg-0">
                        <label class="small text-muted">XML généré</label>
                        <textarea id="mailXmlResult" class="form-control mail-xml-box"></textarea>
                    </div>
                </div>
                <div id="mailXmlMessage" class="small text-muted mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="copyMailXmlBtn">Copier XML</button>
                <button type="button" class="btn btn-primary" id="generateMailXmlBtn">Générer XML</button>
                <button type="button" class="btn btn-secondary mail-modal-close" data-dismiss="modal" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script>
$(function () {
    if ($.fn.DataTable) {
        $('#gmailMailsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Tous']],
            order: [[5, 'desc']],
            autoWidth: false,
            scrollX: true,
            language: {
                lengthMenu: 'Afficher _MENU_ mails',
                search: 'Rechercher',
                info: '_START_ à _END_ / _TOTAL_ mails',
                infoEmpty: '0 mail',
                infoFiltered: '(filtré de _MAX_ mails)',
                zeroRecords: 'Aucun mail trouvé',
                paginate: {
                    previous: 'Précédent',
                    next: 'Suivant'
                }
            }
        });
    }

    let activeMail = null;

    function decodePayload(payload) {
        const bytes = Uint8Array.from(atob(payload), c => c.charCodeAt(0));
        return JSON.parse(new TextDecoder().decode(bytes));
    }

    function mailText(mail) {
        return [
            'Date de réception de la demande: ' + (mail.date || ''),
            'Sujet: ' + (mail.subject || ''),
            'Expéditeur: ' + (mail.from || ''),
            '',
            'Important: la date de réception ci-dessus est date_demande/talep_tarihi. La date demandée dans le texte client est date_operation/service_date.',
            '',
            mail.body || '',
            '',
            mail.pdf_text ? 'Texte extrait des PDF joints:\n' + mail.pdf_text : ''
        ].join('\n');
    }

    function renderAttachments(attachments) {
        if (!attachments || !attachments.length) return '<p class="text-muted">Aucune pièce jointe.</p>';
        let html = '<h6>Pièces jointes</h6>';
        attachments.forEach(function (attachment) {
            const status = attachment.is_pdf
                ? ` <span class="mail-badge ${attachment.parse_status === 'parsed' ? 'mail-badge-ok' : 'mail-badge-warn'}">PDF ${attachment.ocr_used ? 'OCR' : (attachment.parse_status || 'pending')}</span>`
                : '';
            html += `<span class="btn btn-sm btn-outline-secondary disabled mb-1">${attachment.name}</span>${status}<br>`;
            if (attachment.parsed_preview) {
                html += `<div class="mail-preview mb-2">${escapedTextHtml(attachment.parsed_preview)}</div>`;
            }
        });
        return html;
    }

    function escapedTextHtml(text) {
        return $('<div>').text(text || '').html().replace(/\n/g, '<br>');
    }

    $(document).on('click', '.viewMailButton', function () {
        const mail = decodePayload(this.getAttribute('data-payload'));
        const direction = mail.direction === 'out' ? 'Envoyé' : 'Reçu';
        let meta = direction + ' · De: ' + (mail.from || '-');
        if (mail.direction === 'out') {
            meta += ' · À: ' + (mail.to || '-');
        }
        $('#mailSubject').text(mail.subject || 'Sans sujet');
        $('#mailMeta').text(meta + ' · ' + (mail.date || ''));
        $('#mailBody').html(mail.body_html || escapedTextHtml(mail.body || ''));
        $('#mailAttachments').html(renderAttachments(mail.attachments));
        $('#mailDetailModal').modal('show');
    });

    $(document).on('click', '.xmlMailButton', function () {
        activeMail = decodePayload(this.getAttribute('data-payload'));
        $('#mailXmlSource').val(mailText(activeMail));
        $('#mailXmlResult').val('');
        $('#mailXmlMessage').text('');
        $('#mailXmlModal').modal('show');
    });

    $('#generateMailXmlBtn').on('click', function () {
        const rawText = $('#mailXmlSource').val().trim();
        if (!rawText) {
            $('#mailXmlMessage').text('Aucun texte à convertir.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true);
        $('#mailXmlMessage').text('Conversion en cours...');

        fetch("{{ route('talepler.aiXml') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: rawText })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'La conversion XML a échoué.');
            $('#mailXmlResult').val(data.xml || '');
            $('#mailXmlMessage').text('XML généré.');
        })
        .catch(error => $('#mailXmlMessage').text(error.message))
        .finally(() => btn.prop('disabled', false));
    });

    $(document).on('click', '.mail-modal-close', function () {
        const modal = $(this).closest('.modal');
        if ($.fn.modal) {
            modal.modal('hide');
        } else {
            modal.removeClass('show').hide();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        }
    });

    $('#copyMailXmlBtn').on('click', async function () {
        const xml = $('#mailXmlResult').val().trim();
        if (!xml) {
            $('#mailXmlMessage').text('Aucun XML à copier.');
            return;
        }
        await navigator.clipboard.writeText(xml);
        $('#mailXmlMessage').text('XML copié.');
    });

    $(document).on('click', '.createTalepButton', function () {
        const mail = decodePayload(this.getAttribute('data-payload'));
        if (!confirm('Créer une demande depuis ce mail ?\n\n' + (mail.subject || 'Sans sujet'))) return;

        const btn = $(this);
        btn.prop('disabled', true).text('Analyse XML...');

        $.ajax({
            url: '{{ route("gmail.create.talep") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                subject: mail.subject,
                body: mail.body,
                body_html: mail.body_html,
                pdf_text: mail.pdf_text,
                from: mail.from,
                acente_id: mail.acente_id,
                message_id: mail.message_id,
                talep_mail_id: mail.talep_mail_id,
                date: mail.date,
                account: mail.account,
                uid: mail.uid,
                attachments: mail.attachments
            },
            success: function (response) {
                if (response.warning) {
                    alert(response.warning);
                }
                if (response.talep_url) {
                    window.location.href = response.talep_url;
                } else {
                    alert(response.success || 'Demande créée.');
                    window.location.reload();
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).text('Créer');
                alert(xhr.responseJSON?.error || xhr.responseJSON?.message || 'Erreur pendant la création de la demande.');
            }
        });
    });

    $(document).on('click', '.linkMailButton', function () {
        const btn = $(this);
        const talepId = btn.closest('.input-group').find('.linkTalepIdInput').val();
        const mailId = btn.data('mail-id');

        if (!talepId) {
            alert('Indiquez le numéro de demande.');
            return;
        }

        btn.prop('disabled', true).text('...');

        $.ajax({
            url: '{{ route("gmail.link.talep") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                talep_mail_id: mailId,
                talep_id: talepId
            },
            success: function (response) {
                if (response.talep_url) {
                    window.location.href = response.talep_url;
                } else {
                    window.location.reload();
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).text('Lier');
                alert(xhr.responseJSON?.message || 'Impossible de lier ce mail.');
            }
        });
    });
});
</script>
@endsection
