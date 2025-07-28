<?php
// app/Console/Commands/SimpleMqttListener.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimpleMqttService;
use App\Models\Device;
use Illuminate\Support\Facades\Log;

class SimpleMqttListener extends Command
{
    /**
     * The name and signature of the console command.
     * Updated to accept connection argument and timeout option
     */
    protected $signature = 'mqtt:listen 
                            {connection=default : MQTT connection name}
                            {--timeout=0 : Timeout in seconds (0 for infinite)}
                            {--topics=* : Specific topics to subscribe to}';

    /**
     * The console command description.
     */
    protected $description = 'Listen to MQTT messages from devices';

    /**
     * Execute the console command.
     */
    public function handle(SimpleMqttService $mqttService): int
    {
        $connection = $this->argument('connection');
        $timeout = (int) $this->option('timeout');
        $topics = $this->option('topics');

        $this->info("🚀 Starting MQTT Listener with connection: {$connection}");

        // Display current configuration
        $this->displayConfiguration();

        try {
            // Connect to MQTT broker
            if (!$mqttService->connect()) {
                $this->error('❌ Failed to connect to MQTT broker');
                $this->displayTroubleshootingSteps();
                return Command::FAILURE;
            }

            $this->info('✅ Connected to MQTT broker successfully');

            // Subscribe to topics
            $this->subscribeToTopics($mqttService, $topics);

            $this->info('📡 Listening for MQTT messages... Press Ctrl+C to stop');

            // Start listening with timeout if specified
            if ($timeout > 0) {
                $this->info("⏰ Listening with timeout: {$timeout} seconds");
                $mqttService->listen($timeout);
            } else {
                $mqttService->listen();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ MQTT Listener failed: ' . $e->getMessage());
            Log::error('MQTT Listener error', [
                'connection' => $connection,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Display current MQTT configuration
     */
    private function displayConfiguration(): void
    {
        $this->info('🔧 MQTT Configuration:');
        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                ['Host', env('MQTT_HOST', 'localhost'), env('MQTT_HOST') ? '✅' : '❌'],
                ['Port', env('MQTT_PORT', '1883'), env('MQTT_PORT') ? '✅' : '❌'],
                ['Username', env('MQTT_USERNAME') ? '*' : 'Not Set', env('MQTT_USERNAME') ? '✅' : '❌'],
                ['Password', env('MQTT_PASSWORD') ? '*' : 'Not Set', env('MQTT_PASSWORD') ? '✅' : '❌'],
                ['Client ID', env('MQTT_CLIENT_ID', 'laravel_' . uniqid()), '✅'],
            ]
        );
        $this->newLine();
    }

    /**
     * Subscribe to MQTT topics
     */
    private function subscribeToTopics(SimpleMqttService $mqttService, array $topics = []): void
    {
        if (empty($topics)) {
            // Subscribe to default topics
            $defaultTopics = [
                'devices/+/data' => 'handleDeviceData',
                'devices/+/responses' => 'handleDeviceResponse', 
                'devices/+/status' => 'handleDeviceStatus',
                'car-checkers/+/entry-check' => 'handleEntryCheck',
                'car-checkers/+/final-check' => 'handleFinalCheck'
            ];

            foreach ($defaultTopics as $topic => $handler) {
                $mqttService->subscribe($topic, [$this, $handler]);
                $this->line("📡 Subscribed to: {$topic}");
            }
        } else {
            // Subscribe to specific topics
            foreach ($topics as $topic) {
                $mqttService->subscribe($topic, [$this, 'handleGenericMessage']);
                $this->line("📡 Subscribed to: {$topic}");
            }
        }
    }

    /**
     * Display troubleshooting steps
     */
    private function displayTroubleshootingSteps(): void
    {
        $this->warn('🔍 Troubleshooting Steps:');
        $this->line('1. Check if MQTT broker is running:');
        $this->line('   - For local Mosquitto: sudo systemctl start mosquitto');
        $this->line('   - For Docker: docker run -it -p 1883:1883 eclipse-mosquitto');
        $this->newLine();
        
        $this->line('2. Test connection manually:');
        $this->line('   - mosquitto_pub -h localhost -p 1883 -t test/topic -m "hello"');
        $this->line('   - mosquitto_sub -h localhost -p 1883 -t test/topic');
        $this->newLine();
        
        $this->line('3. Check your .env file has:');
        $this->line('   MQTT_HOST=localhost');
        $this->line('   MQTT_PORT=1883');
        $this->line('   MQTT_USERNAME=your_username');
        $this->line('   MQTT_PASSWORD=your_password');
        $this->newLine();
        
        $this->line('4. Verify network connectivity:');
        $this->line('   - telnet ' . env('MQTT_HOST', 'localhost') . ' ' . env('MQTT_PORT', '1883'));
    }

    /**
     * Handle device data messages
     */
    public function handleDeviceData(string $topic, array $data): void
    {
        $deviceId = $this->extractDeviceId($topic);
        
        $this->line("📊 Device Data Received:");
        $this->line("   Device ID: {$deviceId}");
        $this->line("   Entries: " . ($data['entries_count'] ?? 'N/A'));
        $this->line("   Exits: " . ($data['exits_count'] ?? 'N/A'));
        $this->line("   Timestamp: " . ($data['timestamp'] ?? now()));
        
        // Update device in database
        try {
            $device = Device::where('device_id', $deviceId)->first();
            if ($device) {
                $device->update([
                    'last_seen' => now(),
                    'status' => 'online'
                ]);
                
                // Store counting data if needed
                // CountingData::create([...]);
            }
        } catch (\Exception $e) {
            $this->error("Failed to update device {$deviceId}: " . $e->getMessage());
        }
    }

    /**
     * Handle device responses
     */
    public function handleDeviceResponse(string $topic, array $data): void
    {
        $deviceId = $this->extractDeviceId($topic);
        
        $this->line("💬 Device Response:");
        $this->line("   Device ID: {$deviceId}");
        $this->line("   Response: " . json_encode($data, JSON_PRETTY_PRINT));
        
        // Update command status if command_id exists
        if (isset($data['command_id'])) {
            try {
                \DB::table('device_commands')
                    ->where('id', $data['command_id'])
                    ->update([
                        'status' => $data['status'] ?? 'completed',
                        'response_data' => $data,
                        'executed_at' => now()
                    ]);
                
                $this->info("✅ Command {$data['command_id']} status updated");
            } catch (\Exception $e) {
                $this->error("Failed to update command status: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle device status updates
     */
    public function handleDeviceStatus(string $topic, array $data): void
    {
        $deviceId = $this->extractDeviceId($topic);
        
        $this->line("📈 Device Status Update:");
        $this->line("   Device ID: {$deviceId}");
        $this->line("   Status: " . ($data['status'] ?? 'unknown'));
        $this->line("   Data: " . json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Handle car checker entry check
     */
    public function handleEntryCheck(string $topic, array $data): void
    {
        $checkerId = $this->extractDeviceId($topic);
        
        $this->line("🚗 Car Entry Check:");
        $this->line("   Checker ID: {$checkerId}");
        $this->line("   Invoice: " . ($data['invoice_number'] ?? 'N/A'));
        $this->line("   Data: " . json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Handle car checker final check
     */
    public function handleFinalCheck(string $topic, array $data): void
    {
        $checkerId = $this->extractDeviceId($topic);
        
        $this->line("✅ Car Final Check:");
        $this->line("   Checker ID: {$checkerId}");
        $this->line("   Invoice: " . ($data['invoice_number'] ?? 'N/A'));
        $this->line("   Data: " . json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Handle generic messages
     */
    public function handleGenericMessage(string $topic, array $data): void
    {
        $this->line("📨 Generic Message:");
        $this->line("   Topic: {$topic}");
        $this->line("   Data: " . json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Extract device ID from topic
     */
    private function extractDeviceId(string $topic): string
    {
        $parts = explode('/', $topic);
        return $parts[1] ?? 'unknown';
    }
}