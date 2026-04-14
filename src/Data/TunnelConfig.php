<?php

namespace Aerni\Cloudflared\Data;

use Aerni\Cloudflared\Concerns\AssemblesPath;
use Illuminate\Support\Facades\File;

class TunnelConfig
{
    use AssemblesPath;

    public function __construct(public readonly ProjectConfig $projectConfig)
    {
        //
    }

    public function save(): void
    {
        File::put($this->path(), <<<YAML
tunnel: {$this->id()}
credentials-file: {$this->credentialsPath()}

ingress:
  - hostname: {$this->hostname()}
    service: {$this->service()}
  - service: http_status:404
YAML);
    }

    public function delete(): void
    {
        File::delete($this->path());
    }

    public function hostname(): string
    {
        return $this->projectConfig->hostname;
    }

    public function viteHostname(): ?string
    {
        return $this->projectConfig->viteHostname();
    }

    public function id(): string
    {
        return $this->projectConfig->id;
    }

    public function name(): string
    {
        return $this->projectConfig->name;
    }

    /**
     * The local origin URL the cloudflared daemon forwards tunnel traffic to.
     *
     * Reads from the `cloudflared.service_url` config value (set via
     * CLOUDFLARED_SERVICE_URL in .env). When not configured, falls back to
     * http://{hostname}.test — the Herd link URL created by cloudflared:run.
     *
     * This must be a locally reachable URL, never the public Cloudflare
     * hostname, which would cause the tunnel to loop back through Cloudflare.
     */
    public function service(): string
    {
        return config('cloudflared.service_url') ?: 'http://'.$this->hostname().'.test';
    }

    /**
     * The public-facing URL of the tunnel (scheme derived from APP_URL).
     *
     * This is what setAppUrl() uses to override config('app.url') when an
     * incoming request host matches the tunnel hostname. The scheme is taken
     * from APP_URL rather than service(), so that a local HTTP service URL
     * (e.g. http://myapp.test) does not downgrade the public URL to HTTP when
     * Cloudflare is terminating TLS at the edge.
     */
    public function url(): string
    {
        return parse_url(config('app.url'), PHP_URL_SCHEME).'://'.$this->hostname();
    }

    public function path(): string
    {
        return $this->assemble(getenv('HOME'), '.cloudflared', "{$this->id()}.yaml");
    }

    public function credentialsPath(): string
    {
        return $this->assemble(getenv('HOME'), '.cloudflared', "{$this->id()}.json");
    }
}
