<?php

namespace App\Http\Controllers;

use App\Models\WhatsappMessage;
use App\Models\WhatsappTransferDraft;
use App\Services\WhatsappTransferDraftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappInboxController extends Controller
{
    public function webhook(Request $request, WhatsappTransferDraftService $draftService)
    {
        $payload = $request->all();
        $sid = $request->input('MessageSid') ?: $request->input('SmsSid');

        $message = WhatsappMessage::updateOrCreate(
            ['twilio_sid' => $sid ?: uniqid('manual_', true)],
            [
                'from_number' => $request->input('From'),
                'to_number' => $request->input('To'),
                'body' => $request->input('Body'),
                'raw_payload' => $payload,
                'status' => 'received',
            ]
        );

        $draftService->createOrUpdateDraft($message);

        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
            ->header('Content-Type', 'text/xml');
    }

    public function index(Request $request)
    {
        $this->authorizeInbox();

        $status = $request->query('status', 'pending');

        $messages = WhatsappMessage::with('draft')
            ->when($status !== 'all', function ($q) use ($status) {
                $q->whereHas('draft', fn ($sub) => $sub->where('status', $status));
            })
            ->latest('id')
            ->paginate(30)
            ->appends($request->query());

        $counts = [
            'pending' => WhatsappTransferDraft::where('status', 'pending')->count(),
            'error' => WhatsappTransferDraft::where('status', 'error')->count(),
            'ignored' => WhatsappTransferDraft::where('status', 'ignored')->count(),
            'approved' => WhatsappTransferDraft::where('status', 'approved')->count(),
            'all' => WhatsappTransferDraft::count(),
        ];

        return view('whatsapp.inbox', compact('messages', 'status', 'counts'));
    }

    public function show(WhatsappMessage $message)
    {
        $this->authorizeInbox();

        $message->load('draft');

        return view('whatsapp.show', compact('message'));
    }

    public function reparse(WhatsappMessage $message, WhatsappTransferDraftService $draftService)
    {
        $this->authorizeInbox();

        $draftService->createOrUpdateDraft($message);

        return redirect()->route('whatsapp.inbox.show', $message)->with('flash_message', 'Message WhatsApp reanalyse.');
    }

    public function ignore(WhatsappMessage $message)
    {
        $this->authorizeInbox();

        $draft = $message->draft;
        if ($draft) {
            $draft->update([
                'status' => 'ignored',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        }

        $message->update(['status' => 'ignored']);

        return redirect()->route('whatsapp.inbox')->with('flash_message', 'Message WhatsApp ignore.');
    }

    public function approve(WhatsappMessage $message)
    {
        $this->authorizeInbox();

        $draft = $message->draft;
        abort_unless($draft, 404);

        $draft->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $message->update(['status' => 'approved']);

        return redirect()->route('whatsapp.inbox.show', $message)->with('flash_message', 'Brouillon approuve. Creation/modification finale a valider dans la prochaine etape.');
    }

    private function authorizeInbox(): void
    {
        $user = Auth::user();

        abort_unless($user && $user->hasRole('Superadmin'), 403, 'Acces reserve aux super administrateurs.');
    }
}
