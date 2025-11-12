<?php

namespace Aerni\Cloudflared\Concerns;

use Aerni\Cloudflared\Clients\CloudflareClient;
use Aerni\Cloudflared\Data\TunnelConfig;
use Aerni\Cloudflared\Exceptions\NotATunnelDnsRecordException;
use Illuminate\Support\Facades\Cache;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
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
                "cloudflared.domain.{$this->cloudflare()->certificate->hash()}",
                fn () => $this->cloudflare()->zoneName()
            );
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    protected function deleteDnsRecord(string $hostname): void
    {
        try {
            $result = spin(
                callback: fn () => $this->cloudflare()->deleteDnsRecord($hostname),
                message: "Deleting DNS record: {$hostname}"
            );

            $result
                ? info(" ✔ Deleted DNS record: {$hostname}")
                : warning(" ⚠ Can't delete DNS record {$hostname} because it doesn't exist.");
        } catch (NotATunnelDnsRecordException) {
            warning(" ⚠ Skipped deleting DNS record {$hostname} because it's not a Cloudflare Tunnel record.");
        }
    }

    protected function deleteDnsRecords(TunnelConfig $tunnelConfig): void
    {
        $this->deleteDnsRecord($tunnelConfig->hostname());

        if ($tunnelConfig->projectConfig->vite) {
            $this->deleteDnsRecord($tunnelConfig->viteHostname());
        }
    }
}
