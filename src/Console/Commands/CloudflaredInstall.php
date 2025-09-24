<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\HasProjectConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use RuntimeException;

class CloudflaredInstall extends Command
{
    use HasProjectConfig;

    protected $signature = 'cloudflared:install';

    protected $description = 'Create a Cloudflare Tunnel for this project.';

    protected string $hostname;
    protected string $tunnelId;

    public function handle(): void
    {
        if (File::exists($this->projectConfigPath())) {
            $this->fail('Cloudflared is already installed for this project.');
        }

        $herdSite = basename(base_path());

        $this->hostname = text(
            label: 'What hostname do you want to associate with this tunnel?',
            placeholder: "{$herdSite}.domain.com",
            hint: 'It is recommended to match the subdomain to your Laravel Herd site.',
            validate: fn (string $value) => match (true) {
                empty($value) => 'The hostname field is required.',
                count(array_filter(explode('.', $value))) < 3 => "The hostname must include a subdomain (e.g., {$herdSite}.domain.com).",
                default => null,
            },
        );

        $continue = confirm(
            label: "Do you want to overwrite potentially existing DNS records for \"{$this->hostname}\" and \"vite-{$this->hostname}\"?",
            yes: 'Yes, continue',
            no: 'No, abort',
            hint: 'The terms must be accepted to continue.'
        );

        if (! $continue) {
            return;
        }

        $this->createCloudflaredTunnel();
        $this->createCloudflaredFile();
        $this->createAppDnsRecord();
        $this->createViteDnsRecord();
        $this->createHerdLink();
    }

    protected function createCloudflaredTunnel(): void
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel create {$this->hostname}")->throw(),
            message: "Creating tunnel: {$this->hostname}"
        );

        if (! preg_match('/Created tunnel .+ with id ([a-f0-9\-]+)/', $result->output(), $tunnelMatch)) {
            $this->fail('Unable to extract the tunnel ID.');
        }

        $this->tunnelId = $tunnelMatch[1];

        info("<info>[✔]</info> Created tunnel: {$this->hostname}");
    }

    protected function createCloudflaredFile(): void
    {
        File::put('.cloudflared.yaml', <<<YAML
tunnel: {$this->tunnelId}
hostname: {$this->hostname}
YAML);
    }

    protected function createDnsRecord(string $name): void
    {
        spin(
            callback: fn () => Process::run("cloudflared tunnel route dns --overwrite-dns {$this->hostname} {$name}")->throw(),
            message: "Creating DNS record: {$name}"
        );

        info("<info>[✔]</info> Created DNS record: {$name}");
    }

    protected function createAppDnsRecord(): void
    {
        $this->createDnsRecord($this->hostname);
    }

    protected function createViteDnsRecord(): void
    {
        $this->createDnsRecord("vite-{$this->hostname}");
    }

    protected function createHerdLink(): void
    {
        Process::run("herd link {$this->hostname}")->throw();

        info("<info>[✔]</info> Created Herd link: {$this->hostname}");
    }
}
