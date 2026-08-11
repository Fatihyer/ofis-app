<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TransfersController;
use App\Http\Controllers\MissionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwilioVoiceController;
use App\Http\Controllers\TalepController;
use App\Http\Controllers\GoogleAdsLeadController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\HermesController;
use App\Http\Controllers\ClaudePricingController;
use App\Http\Controllers\WhatsAppGroupWebhookController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::post('/claude/chat', [ClaudePricingController::class, 'chat']);
Route::get('/claude/ping', [ClaudePricingController::class, 'ping']);

Route::post('/ai/ping', function () {
    $r = Http::withToken(config('services.openai.key'))
        ->acceptJson()
        ->post(config('services.openai.base_url') . '/chat/completions', [
            'model' => config('services.openai.model'),
            'temperature' => 0,
            'messages' => [
                ['role' => 'system', 'content' => 'Reply only with OK.'],
                ['role' => 'user', 'content' => 'ping'],
            ],
        ]);

    return response()->json([
        'ok' => $r->ok(),
        'status' => $r->status(),
        'answer' => data_get($r->json(), 'choices.0.message.content'),
        'error' => $r->ok() ? null : $r->json(),
    ], $r->ok() ? 200 : 500);
});

Route::post('/cansu-transfer', [TalepController::class, 'storeparisvia']);
Route::post('/google/leads', [GoogleAdsLeadController::class, 'store']);
Route::post('/webhooks/whatsapp/groups', WhatsAppGroupWebhookController::class);


Route::match(['GET', 'POST'], '/twilio-message', [TwilioVoiceController::class, 'message']);



Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:sanctum')->get('/transfers', [TransfersController::class, 'getTransfers']);
Route::middleware('auth:sanctum')->get('/transfer/{id}', [TransfersController::class, 'getTransferDetail']);
Route::middleware('auth:sanctum')->put('/confirmTransfer/{id}', [TransfersController::class, 'confirmTransfer']);
Route::middleware('auth:sanctum')->get('missions/{transferid}', [MissionController::class, 'getMissionDetails']);
Route::middleware('auth:sanctum')->post('m/s/{transferid}', [MissionController::class, 'startMissionapi']);
Route::middleware('auth:sanctum')->post('m/surplace/{transferid}', [MissionController::class, 'surPlaceapi']);
Route::middleware('auth:sanctum')->get('m/o/{transferid}', [MissionController::class, 'markOnBoardapi']);
Route::middleware('auth:sanctum')->get('m/f/{transferid}', [MissionController::class, 'finishMissionapi']);
Route::middleware('auth:sanctum')->post('/m/finishmissiondepot/{transferid}', [MissionController::class, 'finishmissiondepotapi']);
    
