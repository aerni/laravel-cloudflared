<?php

use Aerni\Cloudflared\Clients\CloudflareClient;
use Aerni\Cloudflared\Data\Certificate;
use Aerni\Cloudflared\Exceptions\NotATunnelDnsRecordException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->certificate = new Certificate(
        zoneId: 'zone-123',
        accountId: 'account-456',
        apiToken: 'test-token',
    );

    $this->client = new CloudflareClient($this->certificate);
});

describe('zoneName', function () {
    it('returns the zone name', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123' => Http::response([
                'result' => ['name' => 'example.com'],
            ]),
        ]);

        expect($this->client->zoneName())->toBe('example.com');
    });

    it('throws on a failed response', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123' => Http::response(status: 500),
        ]);

        $this->client->zoneName();
    })->throws(RequestException::class);
});

describe('dnsRecordId', function () {
    it('returns the dns record id', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records?*' => Http::response([
                'result' => [['id' => 'record-789']],
            ]),
        ]);

        expect($this->client->dnsRecordId('tunnel.example.com'))->toBe('record-789');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.cloudflare.com/client/v4/zones/zone-123/dns_records?type=CNAME&name=tunnel.example.com');
    });

    it('returns null when the dns record does not exist', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records?*' => Http::response([
                'result' => [],
            ]),
        ]);

        expect($this->client->dnsRecordId('missing.example.com'))->toBeNull();
    });
});

describe('isTunnelRecord', function () {
    it('identifies a tunnel record', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records/record-789' => Http::response([
                'result' => ['content' => 'some-uuid.cfargotunnel.com'],
            ]),
        ]);

        expect($this->client->isTunnelRecord('record-789'))->toBeTrue();
    });

    it('identifies a non-tunnel record', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records/record-789' => Http::response([
                'result' => ['content' => '1.2.3.4'],
            ]),
        ]);

        expect($this->client->isTunnelRecord('record-789'))->toBeFalse();
    });
});

describe('deleteDnsRecord', function () {
    it('deletes a tunnel dns record', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records?*' => Http::response([
                'result' => [['id' => 'record-789']],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records/record-789' => Http::sequence()
                ->push(['result' => ['content' => 'some-uuid.cfargotunnel.com']])
                ->push(['result' => ['id' => 'record-789']]),
        ]);

        expect($this->client->deleteDnsRecord('tunnel.example.com'))->toBeTrue();

        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    });

    it('returns false when deleting a non-existent dns record', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records?*' => Http::response([
                'result' => [],
            ]),
        ]);

        expect($this->client->deleteDnsRecord('missing.example.com'))->toBeFalse();
    });

    it('throws when deleting a non-tunnel dns record', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records?*' => Http::response([
                'result' => [['id' => 'record-789']],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-123/dns_records/record-789' => Http::response([
                'result' => ['content' => '1.2.3.4'],
            ]),
        ]);

        $this->client->deleteDnsRecord('regular.example.com');
    })->throws(NotATunnelDnsRecordException::class, 'regular.example.com');
});

describe('request configuration', function () {
    it('sends the correct authorization header', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123' => Http::response([
                'result' => ['name' => 'example.com'],
            ]),
        ]);

        $this->client->zoneName();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token'));
    });

    it('sends json content type headers', function () {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-123' => Http::response([
                'result' => ['name' => 'example.com'],
            ]),
        ]);

        $this->client->zoneName();

        Http::assertSent(fn ($request) => $request->hasHeader('Accept', 'application/json'));
    });
});
