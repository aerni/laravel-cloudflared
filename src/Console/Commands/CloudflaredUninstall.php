<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\HasProjectConfig;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class CloudflaredUninstall extends Command
{
    use HasProjectConfig;

    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Delete the Cloudflare Tunnel of this project.';

    public function handle(): void
    {
        $this->deleteTunnel();
        // Optionally: Can we also delete the associated DNS records?
        $this->deleteHerdLink();
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

    protected function deleteHerdLink(): void
    {
        Process::run("herd unlink {$this->hostname()}")->throw();

        info("<info>[✔]</info> Deleted Herd link: {$this->hostname()}");
    }
}
