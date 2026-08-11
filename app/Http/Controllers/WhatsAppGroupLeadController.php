<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWhatsAppGroupMessageRequest;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppGroupLead;
use App\Models\WhatsAppQuickReply;
use App\Services\WhatsApp\WhatsAppGroupManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppGroupLeadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:whatsapp-leads.view')->only(['index', 'show']);
        $this->middleware('permission:whatsapp-leads.manage')->only(['updateStatus']);
        $this->middleware('permission:whatsapp-groups.reply')->only(['sendReply']);
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'new');
        $priority = $request->query('priority');
        $vehicle = $request->query('vehicle');

        $leads = WhatsAppGroupLead::with(['group', 'message', 'duplicateOf.group'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($priority === 'high', fn ($query) => $query->where('score', '>=', 85))
            ->when($priority === 'important', fn ($query) => $query->where('score', '>=', 70))
            ->when($request->query('today') === '1', fn ($query) => $query->whereDate('created_at', now()->toDateString()))
            ->when($vehicle, fn ($query) => $query->where('vehicle_type', $vehicle))
            ->latest('id')
            ->paginate(30)
            ->appends($request->query());

        $counts = [
            'new' => WhatsAppGroupLead::where('status', WhatsAppGroupLead::STATUS_NEW)->count(),
            'high' => WhatsAppGroupLead::where('score', '>=', 85)->count(),
            'today' => WhatsAppGroupLead::whereDate('created_at', now()->toDateString())->count(),
            'all' => WhatsAppGroupLead::count(),
        ];

        return view('whatsapp_group_leads.index', compact('leads', 'status', 'counts'));
    }

    public function show(WhatsAppGroupLead $lead)
    {
        $lead->load(['group', 'message.sentBy', 'duplicateOf.group']);
        $quickReplies = WhatsAppQuickReply::where('is_active', true)->orderBy('title')->get();

        return view('whatsapp_group_leads.show', compact('lead', 'quickReplies'));
    }

    public function updateStatus(Request $request, WhatsAppGroupLead $lead)
    {
        $data = $request->validate([
            'status' => ['required', 'in:reviewed,interesting,quoted,converted,not_interested,duplicate'],
        ]);

        $lead->update($data);

        return back()->with('flash_message', 'Statut mis à jour.');
    }

    public function sendReply(SendWhatsAppGroupMessageRequest $request, WhatsAppGroup $group, WhatsAppGroupManager $manager)
    {
        $messageText = trim((string) $request->input('message'));
        $idempotencyKey = $request->input('idempotency_key') ?: sha1(Auth::id() . '|' . $group->id . '|' . $messageText);
        $lockKey = 'whatsapp_group_send:' . Auth::id() . ':' . $group->id . ':' . $idempotencyKey;

        if (!Cache::add($lockKey, true, now()->addSeconds(15))) {
            return back()->withErrors(['message' => 'Envoi déjà en cours.']);
        }

        try {
            $manager->sendGroupMessage($group, $messageText, Auth::id());
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('Manual WhatsApp group send failed', [
                'group_id' => $group->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            Cache::forget($lockKey);

            return back()->withErrors(['message' => 'Mesaj gönderilemedi. WhatsApp bağlantısını kontrol edin. Bu gruba mesaj gönderme yetkiniz olmayabilir.']);
        }

        return back()->with('flash_message', 'Message envoyé au groupe WhatsApp.');
    }
}
