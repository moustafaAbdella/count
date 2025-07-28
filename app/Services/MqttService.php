<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;

class MqttService
{
    public function subscribe()
    {
        $server = 'https://www.admin.devorastudio.com';  
        $port = 1883;
        $clientId = 'laravel-server-' . uniqid();
        $topic = 'vcount/data';

        $mqtt = new MqttClient($server, $port, $clientId);
        
        $mqtt->connect();
        $mqtt->subscribe($topic, function ($topic, $message) {
            Log::channel('mqtt')->info('Received Data:', [
                'topic' => $topic,
                'message' => json_decode($message, true)
            ]);
        });

        $mqtt->loop(true);
    }
}