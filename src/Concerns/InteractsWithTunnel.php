<?php

namespace Aerni\Cloudflared\Concerns;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\spin;
use Aerni\Cloudflared\TunnelConfig;
use Illuminate\Support\Facades\Process;

trait InteractsWithTunnel
{
    protected function verifyCloudflaredFoundInPath(): void
    {
        if (Process::run('cloudflared --version')->successful()) {
            return;
        }

        error(' ⚠ cloudflared not found in PATH.');
        exit(1);
    }

    protected function deleteCloudflaredTunnel(string $name): void
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel delete {$name}"),
            message: "Deleting tunnel: {$name}"
        );

        if ($result->seeInErrorOutput("there should only be 1 non-deleted Tunnel named {$name}")) {
            warning(" ⚠ Can't delete tunnel {$name} as it doesn't exist.");
            return;
        }

        $result->throw();

        info(' ✔ Deleted tunnel.');
    }

    protected function deleteProjectConfigs(TunnelConfig $tunnelConfig): void
    {
        $tunnelConfig->delete();
        $tunnelConfig->projectConfig->delete();

        info(' ✔ Deleted tunnel configs.');
    }
}
