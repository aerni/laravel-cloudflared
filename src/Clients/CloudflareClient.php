<?php

namespace Aerni\Cloudflared\Clients;

use Aerni\Cloudflared\Data\Certificate;
use Aerni\Cloudflared\Exceptions\NotATunnelDnsRecordException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class CloudflareClient
{
    public function __construct(public readonly Certificate $certificate)
    {
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl('https://api.cloudflare.com/client/v4')
            ->withToken($this->certificate->apiToken)
            ->acceptJson()
            ->asJson()
            ->timeout(10)
            ->retry(2, 100)
            ->throw();
    }

    public function zoneName(): string
    {
        return $this->http()
            ->get("zones/{$this->certificate->zoneId}")
            ->json('result.name');
    }

    public function dnsRecordId(string $hostname): ?string
    {
        return $this->http()
            ->get("zones/{$this->certificate->zoneId}/dns_records", [
                'type' => 'CNAME',
                'name' => $hostname,
            ])
            ->json('result.0.id');
    }

    public function isTunnelRecord(string $recordId): bool
    {
        $content = $this->http()
            ->get("zones/{$this->certificate->zoneId}/dns_records/{$recordId}")
            ->json('result.content');

        return $content !== null && str_ends_with($content, '.cfargotunnel.com');
    }

    public function deleteDnsRecord(string $hostname): bool
    {
        if (! $recordId = $this->dnsRecordId($hostname)) {
            return false;
        }

        if (! $this->isTunnelRecord($recordId)) {
            throw new NotATunnelDnsRecordException($hostname);
        }

        $this->http()->delete("zones/{$this->certificate->zoneId}/dns_records/{$recordId}");

        return true;
    }
}
