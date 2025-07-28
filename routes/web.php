<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MqttController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});


Route::post('/v4/import', function (Request $request) {
    Log::channel('vcount')->info('Received data', [
        'headers' => $request->headers->all(),
        'body' => $request->all(),
        'ip' => $request->ip()
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Data received successfully'
    ]);
});

Route::post('/import-count', [MqttController::class, 'sendMessage']);
Route::post('/senddevicecommand', [MqttController::class, 'sendDeviceCommand']);
// Route::post('/status', [MqttController::class, 'status']);

    Route::prefix('devices')->name('devices.')->group(function () {
        Route::post('/{device}/command', [MqttController::class, 'sendDeviceCommand'])
             ->name('command');
        
         Route::get('/status', [MqttController::class, 'status'])
         ->name('status');


    });

Route::post('/v4/import_count', function (Request $request) {
    Log::info('Complete Request Object:', ['request' => $request,
'Request Body' => $request->all()

]);
    
    return response()->json(['status' => 'success']);
})->withoutMiddleware(['web', 'csrf']);