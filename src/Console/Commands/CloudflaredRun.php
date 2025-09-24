<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\HasProjectConfig;
use Illuminate\Console\Command;
use function Laravel\Prompts\info;
use Illuminate\Support\Facades\Process;

class CloudflaredRun extends Command
{
    use HasProjectConfig;

    protected $signature = 'cloudflared:run';

    protected $description = 'Run the Cloudflare Tunnel of this project.';

    public function handle(): void
    {
        $this->createCloudflaredConfig();
        $this->runCloudflared();
    }

    protected function createCloudflaredConfig(): void
    {
        file_put_contents($this->tunnelConfigPath(), $this->cloudflaredConfigContents());
    }

    protected function runCloudflared(): void
    {
        Process::forever()
            ->tty()
            ->run("cloudflared tunnel --config {$this->tunnelConfigPath()} run")
            ->throw();
    }
}
