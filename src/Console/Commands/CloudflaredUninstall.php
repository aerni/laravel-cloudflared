<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\HasProjectConfig;
use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;

class CloudflaredUninstall extends Command
{
    use HasProjectConfig, InteractsWithHerd;

    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Delete the Cloudflare Tunnel of this project.';

    public function handle(): void
    {
        if (! File::exists($this->projectConfigPath())) {
            $this->fail("Missing file <info>.cloudflared.yaml</info>. There is nothing to uninstall.");
        }

        $this->deleteTunnel();
        $this->deleteHerdLink($this->hostname());
        // Optionally: Can we also delete the associated DNS records?
    }

    protected function deleteTunnel(): void
    {
        spin(
            callback: function () {
                Process::run("cloudflared tunnel delete {$this->hostname()}")->throw();
                File::delete($this->projectConfigPath());
                File::delete($this->tunnelConfigPath());
            },
            message: "Deleting tunnel: {$this->hostname()}"
        );

        info("<info>[✔]</info> Deleted tunnel: {$this->hostname()}");
    }
}
