<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\TunnelConfig;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class CloudflaredUninstall extends Command
{
    use InteractsWithHerd, InteractsWithTunnel;

    protected $signature = 'cloudflared:uninstall';

    protected $description = 'Delete the Cloudflare Tunnel of this project.';

    protected TunnelConfig $tunnelConfig;

    public function handle()
    {
        $this->verifyCloudflaredFoundInPath();
        $this->verifyHerdFoundInPath();

        if (! Cloudflared::isInstalled()) {
            $this->fail('Missing project file: .cloudflared.yaml');
        }

        $this->tunnelConfig = Cloudflared::tunnelConfig();

        $confirmed = confirm(
            label: "Are you sure you want to uninstall the {$this->tunnelConfig->hostname()} tunnel?",
            hint: 'Deletes the cloudflared tunnel, Herd link, and all associated configs.',
        );

        if (! $confirmed) {
            error(' ⚠ Uninstallation aborted.');
            return self::SUCCESS;
        }

        $this->deleteCloudflaredTunnel($this->tunnelConfig->hostname());
        $this->deleteHerdLink($this->tunnelConfig->hostname());
        $this->deleteProjectConfigs($this->tunnelConfig);
        // Optionally: Delete DNS record. This requires a Cloudflare API token.
    }
}
