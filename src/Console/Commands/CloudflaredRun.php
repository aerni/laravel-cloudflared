<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\Concerns\ManagesProject;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\TunnelConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;

class CloudflaredRun extends Command
{
    use InteractsWithHerd, InteractsWithTunnel, ManagesProject;

    protected $signature = 'cloudflared:run';

    protected $description = 'Run the Cloudflare Tunnel of this project.';

    public function handle(): void
    {
        $this->verifyCloudflaredFoundInPath();
        $this->verifyHerdFoundInPath();

        if (! Cloudflared::isInstalled()) {
            $this->fail('Project file .cloudflared.yaml not found.');
        }

        $tunnelConfig = Cloudflared::tunnelConfig();

        $this->saveTunnelConfig($tunnelConfig);
        $this->createHerdLink($tunnelConfig->hostname());
        $this->runCloudflared($tunnelConfig);
    }

    // TODO: Only show process output if it was requested via a --debug or --logLevel or something like that.
    // Else, only show errors.

    // TODO: Look through this and see if there is anything to optimize.
    protected function runCloudflared(TunnelConfig $tunnelConfig): void
    {
        info(' ✔ Started tunnel.');

        // Set up signal handlers before starting the process
        pcntl_async_signals(true);

        $process = Process::forever()
            ->tty()
            ->start("cloudflared tunnel --config {$tunnelConfig->path()} run");

        // Track if we're already shutting down to prevent duplicate signal handling
        $shuttingDown = false;

        // Handle multiple termination signals
        $signalHandler = function ($signal) use ($process, &$shuttingDown, $tunnelConfig) {
            if ($shuttingDown) {
                return;
            }

            $shuttingDown = true;

            if ($process->running()) {
                $process->signal(SIGTERM);
                $process->wait();
            }

            $this->cleanupCloudflaredProcess($tunnelConfig);
            exit(0);
        };

        pcntl_signal(SIGINT, $signalHandler);  // Ctrl+C
        pcntl_signal(SIGTERM, $signalHandler); // Termination signal
        pcntl_signal(SIGHUP, $signalHandler);  // Hangup signal

        try {
            $process->wait();

            // If process exited normally or was terminated by our signal handler, clean up
            if (! $shuttingDown) {
                $this->cleanupCloudflaredProcess($tunnelConfig);
            }
        } catch (\Exception $e) {
            // Ensure process is terminated on any failure
            if ($process->running() && ! $shuttingDown) {
                $process->signal(SIGTERM);
                $process->wait();
            }

            if (! $shuttingDown) {
                $this->cleanupCloudflaredProcess($tunnelConfig);
            }

            throw $e;
        }
    }

    protected function cleanupCloudflaredProcess(TunnelConfig $tunnelConfig): void
    {
        info(' ✔ Stopped tunnel.');

        $this->deleteTunnelConfig($tunnelConfig);
    }
}
