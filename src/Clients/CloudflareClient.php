<?php

namespace Aerni\Cloudflared\Clients;

use Aerni\Cloudflared\Data\Certificate;
use Aerni\Cloudflared\Exceptions\NotATunnelDnsRecordException;
use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\Zones;

class CloudflareClient
{
    public readonly Zones $zones;

    public readonly DNS $dns;

    public function __construct(public readonly Certificate $certificate)
    {
        $auth = new APIToken($this->certificate->apiToken);
        $adapter = new Guzzle($auth);

        $this->zones = new Zones($adapter);
        $this->dns = new DNS($adapter);
    }

    public function zoneName(): string
    {
        return $this->zones
            ->getZoneById($this->certificate->zoneId)
            ->result->name;
    }

    public function dnsRecordId(string $hostname, string $type = 'CNAME'): string
    {
        return $this->dns->getRecordID($this->certificate->zoneId, $type, $hostname);
    }

    public function isTunnelRecord(string $recordId): bool
    {
        $record = $this->dns->getRecordDetails($this->certificate->zoneId, $recordId);

        return isset($record->content) && str_ends_with($record->content, '.cfargotunnel.com');
    }

    public function deleteDnsRecord(string $hostname, string $type = 'CNAME'): bool
    {
        if (! $recordId = $this->dnsRecordId($hostname, $type)) {
            return false;
        }

        if (! $this->isTunnelRecord($recordId)) {
            throw new NotATunnelDnsRecordException($hostname);
        }

        return $this->dns->deleteRecord($this->certificate->zoneId, $recordId);
    }
}
