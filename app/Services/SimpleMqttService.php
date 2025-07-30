<?php
// app/Services/SimpleMqttService.php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

// class SimpleMqttService
// {
//     private MqttClient $client;
//     private ConnectionSettings $settings;
//     private string $host;
//     private int $port;

//     public function __construct()
//     {
//         $this->host = env('MQTT_HOST', 'localhost');
//         $this->port = (int) env('MQTT_PORT', 1883);
        
//         $this->client = new MqttClient(
//             $this->host,
//             $this->port,
//             env('MQTT_CLIENT_ID', 'laravel_' . uniqid())
//         );

//         $this->settings = (new ConnectionSettings())
//             ->setUsername(env('MQTT_USERNAME'))
//             ->setPassword(env('MQTT_PASSWORD'))
//             ->setKeepAliveInterval(60)
//             ->setConnectTimeout(30)
//             ->setSocketTimeout(5);
//     }

//     /**
//      * Connect to MQTT broker with detailed error reporting
//      */
//     public function connect(): bool
//     {
//         try {
//             Log::info('Attempting MQTT connection', [
//                 'host' => $this->host,
//                 'port' => $this->port,
//                 'client_id' => $this->client->getClientId()
//             ]);

//             $this->client->connect($this->settings);
            
//             Log::info('MQTT connection successful');
//             return true;

//         } catch (\PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException $e) {
//             Log::error('MQTT broker connection failed', [
//                 'host' => $this->host,
//                 'port' => $this->port,
//                 'error' => $e->getMessage()
//             ]);
//             return false;

//         } catch (\PhpMqtt\Client\Exceptions\InvalidMessageException $e) {
//             Log::error('MQTT invalid message', [
//                 'error' => $e->getMessage()
//             ]);
//             return false;

//         } catch (\Exception $e) {
//             Log::error('MQTT connection error', [
//                 'host' => $this->host,
//                 'port' => $this->port,
//                 'error' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString()
//             ]);
//             return false;
//         }
//     }

//     /**
//      * Publish message with error handling
//      */
//     public function publish(string $topic, array $data): bool
//     {
//         try {
//             if (!$this->client->isConnected()) {
//                 if (!$this->connect()) {
//                     return false;
//                 }
//             }

//             $message = json_encode($data);
//             $this->client->publish($topic, $message, 1);
            
//             Log::info("MQTT message published", [
//                 'topic' => $topic,
//                 'message' => $message
//             ]);
            
//             return true;

//         } catch (\Exception $e) {
//             Log::error("MQTT publish failed", [
//                 'topic' => $topic,
//                 'error' => $e->getMessage()
//             ]);
//             return false;
//         }
//     }

//     /**
//      * Subscribe to topic with error handling
//      */
//     public function subscribe(string $topic, callable $callback): void
//     {
//         try {
//             if (!$this->client->isConnected()) {
//                 $this->connect();
//             }

//             $this->client->subscribe($topic, function ($receivedTopic, $message) use ($callback) {
//                 try {
//                     $data = json_decode($message, true) ?: [];
//                     call_user_func($callback, $receivedTopic, $data);
//                 } catch (\Exception $e) {
//                     Log::error('MQTT callback error', [
//                         'topic' => $receivedTopic,
//                         'error' => $e->getMessage()
//                     ]);
//                 }
//             }, 1);

//         } catch (\Exception $e) {
//             Log::error("MQTT subscribe failed", [
//                 'topic' => $topic,
//                 'error' => $e->getMessage()
//             ]);
//             throw $e;
//         }
//     }

//     /**
//      * Start listening with timeout support
//      */
//     public function listen(int $timeout = 0): void
//     {
//         try {
//             if ($timeout > 0) {
//                 $this->client->loop(true, $timeout);
//             } else {
//                 $this->client->loop(true);
//             }
//         } catch (\Exception $e) {
//             Log::error('MQTT listen error', [
//                 'error' => $e->getMessage()
//             ]);
//             throw $e;
//         }
//     }

//     /**
//      * Disconnect from broker
//      */
//     public function disconnect(): void
//     {
//         try {
//             if ($this->client->isConnected()) {
//                 $this->client->disconnect();
//                 Log::info('MQTT disconnected successfully');
//             }
//         } catch (\Exception $e) {
//             Log::error('MQTT disconnect error', [
//                 'error' => $e->getMessage()
//             ]);
//         }
//     }
// }