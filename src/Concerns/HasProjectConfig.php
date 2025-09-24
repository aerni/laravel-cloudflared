<?php
namespace Aerni\Cloudflared\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

trait HasProjectConfig
{
    protected Collection $projectConfig;

    protected function projectConfig(): Collection
    {
        if (! isset($this->projectConfig)) {
            $this->projectConfig = collect(Yaml::parseFile($this->projectConfigPath()));
        }

        return $this->projectConfig;
    }

    protected function cloudflaredConfigContents(): string
    {
        return <<<YAML
tunnel: {$this->tunnel()}
credentials-file: {$this->credentialsFilePath()}

ingress:
  - hostname: {$this->hostname()}
    service: {$this->service()}
  - service: http_status:404
YAML;
    }

    protected function projectConfigPath(): string
    {
        return base_path('.cloudflared.yaml');
    }

    protected function tunnel(): string
    {
        return $this->projectConfig()->get('tunnel');
    }

    protected function hostname(): string
    {
        return $this->projectConfig()->get('hostname');
    }

    protected function service(): string
    {
        return config('app.url');
    }

    protected function credentialsFilePath(): string
    {
        return $this->assemble([getenv('HOME'), '.cloudflared', "{$this->tunnel()}.json"]);
    }

    protected function tunnelConfigPath(): string
    {
        return $this->assemble([getenv('HOME'), '.cloudflared', "{$this->tunnel()}.yaml"]);
    }

    protected function assemble($parts): ?string
    {
        $parts = func_get_args();

        if (is_array($parts[0])) {
            $parts = $parts[0];
        }

        if (! is_array($parts) || ! count($parts)) {
            return null;
        }

        return Str::of(implode('/', $parts))
            ->replace('\\', '/')
            ->replace('//', '/')
            ->toString();
    }
}
