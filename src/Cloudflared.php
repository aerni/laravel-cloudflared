<?php

namespace Aerni\Cloudflared;

class Cloudflared
{
    public function projectConfig(): ProjectConfig
    {
        return once(fn () => ProjectConfig::load());
    }

    public function tunnelConfig(): TunnelConfig
    {
        return once(fn () => new TunnelConfig($this->projectConfig()));
    }

    public function isInstalled(): bool
    {
        return ProjectConfig::exists();
    }

    public function isAuthenticated(): bool
    {
        return Certificate::exists();
    }

    public function certificate(): Certificate
    {
        if (! $this->isAuthenticated()) {
            throw new \RuntimeException('Cloudflared is not authenticated. Please run "cloudflared tunnel login" first.');
        }

        return Certificate::load();
    }
}
