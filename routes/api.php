<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Webhook WAHA — balasan admin menandai sesi libur
|--------------------------------------------------------------------------
|
| Dipanggil oleh WAHA (bukan oleh user), sehingga tidak memakai Sanctum.
| Keamanan: `{secret}` dari WAHA_WEBHOOK_SECRET + HMAC opsional dari
| WAHA_WEBHOOK_HMAC_KEY. Lihat docs/rencana-implementasi-libur-via-balasan-wa.md
|
*/
Route::post('/whatsapp/webhook/{secret}', WhatsAppWebhookController::class)
    ->name('whatsapp.webhook');
