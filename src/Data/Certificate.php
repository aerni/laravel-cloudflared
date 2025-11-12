<?php

namespace Aerni\Cloudflared\Data;

use Illuminate\Support\Facades\File;

class Certificate
{
    public function __construct(
        public readonly string $zoneId,
        public readonly string $accountId,
        public readonly string $apiToken,
    ) {}

    public static function load(): self
    {
        if (! static::exists()) {
            throw new \RuntimeException('Cloudflared certificate not found. Please authenticate first by running "cloudflared tunnel login".');
        }

        $certContent = File::get(static::path());

        if (! preg_match('/-----BEGIN ARGO TUNNEL TOKEN-----(.*?)-----END ARGO TUNNEL TOKEN-----/s', $certContent, $matches)) {
            throw new \RuntimeException('Unable to parse cloudflared certificate. The certificate file may be corrupted.');
        }

        $decoded = base64_decode(trim($matches[1]));

        if (! $decoded) {
            throw new \RuntimeException('Unable to decode cloudflared certificate. The certificate file may be corrupted.');
        }

        $data = json_decode($decoded, true);

        if (! is_array($data)) {
            throw new \RuntimeException('Unable to parse cloudflared certificate data. The certificate file may be corrupted.');
        }

        $missingKeys = array_diff(['zoneID', 'accountID', 'apiToken'], array_keys($data));

        if (! empty($missingKeys)) {
            throw new \RuntimeException('Cloudflared certificate is missing required data: '.implode(', ', $missingKeys));
        }

        return new self(
            zoneId: $data['zoneID'],
            accountId: $data['accountID'],
            apiToken: $data['apiToken'],
        );
    }

    public static function path(): string
    {
        return getenv('HOME').'/.cloudflared/cert.pem';
    }

    public static function exists(): bool
    {
        return File::exists(static::path());
    }

    public function hash(): string
    {
        return md5($this->zoneId.$this->accountId.$this->apiToken);
    }
}
