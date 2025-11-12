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

    public function service(): string
    {
        return config('app.url');
    }

    public function url(): string
    {
        return parse_url($this->service(), PHP_URL_SCHEME).'://'.$this->hostname();
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
