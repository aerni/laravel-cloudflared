<?php

namespace Aerni\Cloudflared;

use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class ProjectConfig
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public string $hostname,
        public bool $vite = false
    ) {}

    public static function load(): self
    {
        if (! static::exists()) {
            throw new \RuntimeException('No project configuration found. Run "php artisan cloudflared:install" first.');
        }

        return new self(...Yaml::parseFile(static::path()));
    }

    public function save(): void
    {
        $vite = $this->vite ? 'true' : 'false';

        File::put(static::path(), <<<YAML
id: {$this->id}
name: {$this->name}
hostname: {$this->hostname}
vite: {$vite}
YAML);
    }

    public function viteHostname(): ?string
    {
        return $this->vite ? "vite-{$this->hostname}" : null;
    }

    public function delete(): void
    {
        File::delete(static::path());
    }

    public static function exists(): bool
    {
        return File::exists(static::path());
    }

    public static function path(): string
    {
        return base_path('.cloudflared.yaml');
    }
}
