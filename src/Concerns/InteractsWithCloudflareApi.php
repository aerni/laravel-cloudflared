<?php

namespace Aerni\Cloudflared\Concerns;

use Aerni\Cloudflared\CloudflareClient;
use Aerni\Cloudflared\TunnelConfig;
use Illuminate\Support\Facades\Cache;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait InteractsWithCloudflareApi
{
    protected function cloudflare(): CloudflareClient
    {
        return app(CloudflareClient::class);
    }

    protected function authenticatedDomain(): string
    {
        try {
            return Cache::rememberForever(
                "cloudflared.domain.{$this->cloudflare()->hash()}",
                fn () => $this->cloudflare()->getZoneName()
            );
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    protected function deleteDnsRecord(string $hostname): void
    {
        $result = spin(
            callback: fn () => $this->cloudflare()->deleteDnsRecord($hostname),
            message: "Deleting DNS record: {$hostname}"
        );

        $result
            ? info(" ✔ Deleted DNS record: {$hostname}")
            : warning(" ⚠ Can't delete DNS record {$hostname} because it doesn't exist.");
    }

    protected function deleteDnsRecords(TunnelConfig $tunnelConfig): void
    {
        $this->deleteDnsRecord($tunnelConfig->hostname());

        if ($tunnelConfig->projectConfig->vite) {
            $this->deleteDnsRecord($tunnelConfig->viteHostname());
        }
    }
}
