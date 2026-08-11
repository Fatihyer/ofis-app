<?php

namespace App\Http\Controllers;

use App\Services\ClaudePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaudePricingController extends Controller
{
    public function __construct(private ClaudePricingService $claude) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message'  => 'required|string|max:2000',
            'history'  => 'sometimes|array|max:20',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:4000',
        ]);

        $messages = collect($request->input('history', []))
            ->map(fn($m) => ['role' => $m['role'], 'content' => $m['content']])
            ->push(['role' => 'user', 'content' => $request->input('message')])
            ->values()
            ->all();

        $answer = $this->claude->chat($messages);

        return response()->json(['answer' => $answer]);
    }

    public function ping(): JsonResponse
    {
        $answer = $this->claude->chat([
            ['role' => 'user', 'content' => 'Réponds uniquement par OK.'],
        ]);

        return response()->json(['ok' => str_contains($answer, 'OK'), 'answer' => $answer]);
    }
}
