<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppGroup;
use App\Services\WhatsApp\WhatsAppGroupManager;
use Illuminate\Http\Request;

class WhatsAppGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:whatsapp-groups.view')->only(['index', 'qr']);
        $this->middleware('permission:whatsapp-groups.manage')->only(['update', 'sync']);
    }

    public function index(WhatsAppGroupManager $manager)
    {
        $groups = WhatsAppGroup::orderBy('name')->paginate(80);
        $status = null;

        try {
            $status = $manager->status();
        } catch (\Throwable $e) {
            $status = ['status' => 'offline', 'whatsapp' => 'disconnected'];
        }

        return view('whatsapp_groups.index', compact('groups', 'status'));
    }

    public function qr(WhatsAppGroupManager $manager)
    {
        $status = null;
        $qr = null;
        $error = null;

        try {
            $status = $manager->status();
            $qr = $manager->qr();
        } catch (\Throwable $e) {
            $error = 'QR indisponible. WhatsApp est peut-être déjà connecté ou le gateway n’est pas prêt.';
        }

        return view('whatsapp_groups.qr', compact('status', 'qr', 'error'));
    }

    public function update(Request $request, WhatsAppGroup $group)
    {
        $group->update([
            'is_active' => $request->boolean('is_active'),
            'analysis_enabled' => $request->boolean('analysis_enabled'),
        ]);

        return back()->with('flash_message', 'Groupe mis à jour.');
    }

    public function sync(WhatsAppGroupManager $manager)
    {
        $count = $manager->syncGroups();

        return back()->with('flash_message', $count . ' groupes synchronisés.');
    }
}
