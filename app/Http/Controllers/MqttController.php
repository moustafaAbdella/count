<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SimpleMqttService;


class MqttController extends Controller
{
    private SimpleMqttService $mqttService;

    public function __construct(SimpleMqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    /**
     * Send message to MQTT topic
     */
    public function sendMessage(Request $request)
    {
        $topic = $request->input('topic');
        $message = $request->input('message');

        $success = $this->mqttService->publish($topic, ['message' => $message]);

        return response()->json([
            'success' => $success,
            'topic' => $topic,
            'message' => $message
        ]);
    }

    /**
     * Send command to device
     */
    public function sendDeviceCommand(Request $request)
    {
        $deviceId = $request->input('device_id');
        $command = $request->input('command');
        $data = $request->input('data', []);

        $topic = "devices/{$deviceId}/commands";
        
        $payload = [
            'command' => $command,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ];

        $success = $this->mqttService->publish($topic, $payload);

        return response()->json([
            'success' => $success,
            'device_id' => $deviceId,
            'command' => $command
        ]);
    }

    /**
     * Get MQTT status
     */
    public function status()
    {
        return response()->json([
            'mqtt_connected' => $this->mqttService->connect(),
            'timestamp' => now()
        ]);
    }
}
