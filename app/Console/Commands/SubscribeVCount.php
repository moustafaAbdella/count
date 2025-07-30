<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VCountMqttService;

class SubscribeVCount extends Command
{
    protected $signature = 'vcount:subscribe';
    protected $description = 'Subscribe to V-Count MQTT feed';

    public function handle()
    {
        $this->info('Starting V-Count MQTT subscriber...');
        (new VCountMqttService())->subscribe();
    }
}