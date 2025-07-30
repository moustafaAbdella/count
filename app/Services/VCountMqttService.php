<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log;

class VCountMqttService
{
    public function subscribe()
    {
        $server = 'dd85b64fad8f4920b0d5e576907662e9.s1.eu.hivemq.cloud';
        $port = 8883;
        $clientId = 'laravel-vcount-'.uniqid();
        $username = 'laravel-user'; 
        $password = 'Laravel-user#1'; 
        $topic = 'vcount/data'; 

        try {
            $mqtt = new MqttClient($server, $port, $clientId);
            
            $mqtt->connect($username, $password);
            
            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->processMessage($topic, $message);
            });

            $mqtt->loop(true);
            
        } catch (\Exception $e) {
            Log::error('MQTT Error: '.$e->getMessage());
        }
    }


    protected function processMessage($topic, $message)
{
    Log::info('VCount Data Received', [
        'topic' => $topic,
        'raw_data' => $message,
        'decoded_data' => json_decode($message, true) ?? $message,
        'received_at' => now()->toDateTimeString()
    ]);
}
}