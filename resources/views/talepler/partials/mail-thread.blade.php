@once
<style>
.mail-thread-list{display:flex;flex-direction:column;gap:10px}.mail-thread-item{border:1px solid #e5e7eb;border-radius:8px;background:#fff;overflow:hidden}.mail-thread-item-out{border-left:4px solid #4f46e5}.mail-thread-item-in{border-left:4px solid #059669}.mail-thread-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;background:#f8fafc;border-bottom:1px solid #e5e7eb;padding:10px 12px}.mail-thread-title{font-weight:900;color:#0f172a}.mail-thread-meta{color:#64748b;font-size:12px;font-weight:800;margin-top:2px}.mail-thread-body{max-height:520px;overflow:auto;padding:16px;color:#334155;line-height:1.6;word-break:break-word}.mail-thread-body p{margin:0 0 8px}.mail-thread-body blockquote{border-left:3px solid #cbd5e1;margin:8px 0;padding-left:10px;color:#64748b}.mail-thread-body a{color:#2563eb;font-weight:800}.mail-thread-body table{width:100%;border-collapse:collapse;margin:8px 0}.mail-thread-body td,.mail-thread-body th{border:1px solid #e5e7eb;padding:5px 7px;vertical-align:top}.mail-thread-badges{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}.mail-thread-badge{border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;border-radius:999px;padding:3px 8px;font-size:12px;font-weight:900}.mail-thread-badge-in{border-color:#bbf7d0;background:#ecfdf5;color:#047857}.mail-thread-badge-out{border-color:#c7d2fe;background:#eef2ff;color:#4338ca}.mail-thread-attachments{padding:0 12px 12px;display:flex;gap:6px;flex-wrap:wrap}.mail-thread-attachment{border:1px solid #e5e7eb;border-radius:999px;padding:3px 8px;font-size:12px;color:#475569;background:#fff}
</style>
@endonce

@php
    $mailThread = isset($talep) ? $talep->mailler : collect();
    $incomingMailCount = $mailThread->where('direction', 'in')->count();
    $outgoingMailCount = $mailThread->where('direction', 'out')->count();
@endphp

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Yazışmalar e-mail</span>
        <div class="d-flex align-items-center gap-1 flex-wrap">
            <span class="badge bg-light text-dark border">{{ $mailThread->count() }} mail</span>
            <span class="badge bg-success">{{ $incomingMailCount }} reçu</span>
            <span class="badge bg-primary">{{ $outgoingMailCount }} envoyé</span>
        </div>
    </div>
    <div class="card-body mail-thread-list">
        @forelse($mailThread as $mail)
            @php
                $isOutgoingMail = $mail->direction === 'out';
                $toEmails = collect($mail->to_emails_json ?: [])->implode(', ');
            @endphp
            <div class="mail-thread-item {{ $isOutgoingMail ? 'mail-thread-item-out' : 'mail-thread-item-in' }}">
                <div class="mail-thread-head">
                    <div>
                        <div class="mail-thread-title">{{ $mail->mail_baslik ?: '(sans sujet)' }}</div>
                        <div class="mail-thread-meta">
                            De: {{ $mail->from_name ?: $mail->from_email ?: '-' }}
                            @if($mail->from_name && $mail->from_email)
                                &lt;{{ $mail->from_email }}&gt;
                            @endif
                            @if($isOutgoingMail && $toEmails)
                                · À: {{ $toEmails }}
                            @endif
                            · {{ optional($mail->received_at ?: $mail->created_at)->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <div class="mail-thread-badges">
                        <span class="mail-thread-badge {{ $isOutgoingMail ? 'mail-thread-badge-out' : 'mail-thread-badge-in' }}">
                            {{ $isOutgoingMail ? 'Envoyé' : 'Reçu' }}
                        </span>
                        @if($mail->account)
                            <span class="mail-thread-badge">{{ $mail->account }}</span>
                        @endif
                        <span class="mail-thread-badge">{{ $mail->linked_by ?: 'auto' }}</span>
                    </div>
                </div>
                <div class="mail-thread-body">{!! $mail->mail_icerik_html ?: '<span class="text-muted">-</span>' !!}</div>
                @if(!empty($mail->attachment_names_json))
                    <div class="mail-thread-attachments">
                        @foreach($mail->attachment_names_json as $attachmentName)
                            <span class="mail-thread-attachment">{{ $attachmentName }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="text-muted">Aucun mail synchronisé pour cette demande.</div>
        @endforelse
    </div>
</div>
