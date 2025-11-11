<?php

namespace Aerni\Cloudflared;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\Zones;

class CloudflareClient
{
    protected Zones $zones;
    protected DNS $dns;

    public function __construct(protected Certificate $certificate)
    {
        $auth = new APIToken($this->certificate->apiToken);
        $adapter = new Guzzle($auth);

        $this->zones = new Zones($adapter);
        $this->dns = new DNS($adapter);
    }

    public function certificate(): Certificate
    {
        return $this->certificate;
    }

    public function hash(): string
    {
        return $this->certificate->hash();
    }

    public function zones(): Zones
    {
        return $this->zones;
    }

    public function dns(): DNS
    {
        return $this->dns;
    }

    public function zoneId(): string
    {
        return $this->certificate->zoneId;
    }

    public function accountId(): string
    {
        return $this->certificate->accountId;
    }

    public function apiToken(): string
    {
        return $this->certificate->apiToken;
    }

    public function getZoneName(): string
    {
        return $this->zones
            ->getZoneById($this->certificate->zoneId)
            ->result->name;
    }

    public function getDnsRecordId(string $hostname, string $type = 'CNAME'): string
    {
        return $this->dns->getRecordID($this->certificate->zoneId, $type, $hostname);
    }

    public function deleteDnsRecord(string $hostname, string $type = 'CNAME'): bool
    {
        if (! $recordId = $this->getDnsRecordId($hostname, $type)) {
            return false;
        }

        return $this->dns->deleteRecord($this->certificate->zoneId, $recordId);
    }
}
