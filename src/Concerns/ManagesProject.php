<?php

namespace Aerni\Cloudflared\Concerns;

use Aerni\Cloudflared\Data\ProjectConfig;
use Aerni\Cloudflared\Data\TunnelConfig;

use function Laravel\Prompts\info;

trait ManagesProject
{
    protected function saveProjectConfig(ProjectConfig $projectConfig): void
    {
        $projectConfig->save();

        info(' ✔ Saved project config: .cloudflared.yaml');
    }

    protected function deleteProject(TunnelConfig $tunnelConfig): void
    {
        $tunnelConfig->delete();
        $tunnelConfig->projectConfig->delete();

        info(' ✔ Deleted project config: .cloudflared.yaml');
    }

    protected function saveTunnelConfig(TunnelConfig $tunnelConfig): void
    {
        $tunnelConfig->save();

        info(" ✔ Saved tunnel config: {$tunnelConfig->path()}");
    }

    protected function deleteTunnelConfig(TunnelConfig $tunnelConfig): void
    {
        $tunnelConfig->delete();

        info(" ✔ Deleted tunnel config: {$tunnelConfig->path()}");
    }
}
