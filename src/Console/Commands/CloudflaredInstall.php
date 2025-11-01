<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\ProjectConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class CloudflaredInstall extends Command
{
    use InteractsWithHerd, InteractsWithTunnel;

    protected $signature = 'cloudflared:install';

    protected $description = 'Create a Cloudflare Tunnel for this project.';

    protected ProjectConfig $projectConfig;

    public function handle()
    {
        $this->verifyCloudflaredFoundInPath();
        $this->verifyHerdFoundInPath();

        if (Cloudflared::isInstalled()) {
            $this->handleExistingInstallation();
        }

        $hostname = $this->askForHostname();
        $createViteDns = confirm(label: 'Do you want to create a Vite DNS record?');

        $this->createTunnel($hostname);
        $this->createAppDnsRecord();

        if ($createViteDns) {
            $this->createViteDnsRecord();
        }

        $this->createHerdLink($this->projectConfig->hostname);
        $this->saveProjectConfig();
    }

    protected function handleExistingInstallation(): void
    {
        $tunnelConfig = Cloudflared::tunnelConfig();

        warning(" ⚠ There is an existing tunnel for this project (hostname: {$tunnelConfig->hostname()}).");

        $selection = select(
            label: 'How do you want to proceed?',
            options: ['Abort', 'Delete existing tunnel and create a new one']
        );

        if ($selection === 'Abort') {
            error(' ⚠ Installation aborted.');
            exit(0);
        }

        $this->deleteTunnel($tunnelConfig->hostname());
        $this->deleteProjectConfigs($tunnelConfig);
    }

    protected function createTunnel(string $name): void
    {
        $tunnelInfo = spin(
            callback: fn () => Process::run("cloudflared tunnel info {$name}"),
            message: "Verifying that there is no existing tunnel for {$name}."
        );

        if ($tunnelInfo->successful()) {
            $this->handleExistingTunnel($name);

            return;
        }

        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel create {$name}"),
            message: 'Creating tunnel'
        );

        $result->throw();

        if (! preg_match('/Created tunnel .+ with id ([a-f0-9\-]+)/', $result->output(), $tunnelMatch)) {
            $this->fail('Unable to extract the tunnel ID.');
        }

        $this->projectConfig = Cloudflared::makeProjectConfig(tunnel: $tunnelMatch[1], hostname: $name);

        info(' ✔ Created tunnel.');
    }

    protected function handleExistingTunnel(string $name): void
    {
        warning(" ⚠ A tunnel for {$name} already exists.");

        $selection = select(
            label: 'How do you want to proceed?',
            options: ['Choose a different hostname', 'Delete existing tunnel and continue']
        );

        if ($selection === 'Choose a different hostname') {
            $this->createTunnel($this->askForHostname());

            return;
        }

        $this->deleteTunnel($name);
        $this->createTunnel($name);
    }

    protected function createAppDnsRecord(): void
    {
        $this->createDnsRecord($this->projectConfig->hostname);
    }

    protected function createViteDnsRecord(): void
    {
        $this->createDnsRecord("vite-{$this->projectConfig->hostname}");
    }

    protected function createDnsRecord(string $name, bool $overwrite = false): void
    {
        $command = $overwrite
            ? "cloudflared tunnel route dns --overwrite-dns {$this->projectConfig->tunnel} {$name}"
            : "cloudflared tunnel route dns {$this->projectConfig->tunnel} {$name}";

        $result = spin(
            callback: fn () => Process::run($command),
            message: "Creating DNS record: {$name}"
        );

        if ($result->seeInErrorOutput('Failed to add route: code: 1003')) {
            $this->handleExistingDnsRecord($name);

            return;
        }

        $result->throw();

        info(" ✔ Created DNS record: {$name}");
    }

    protected function handleExistingDnsRecord(string $name): void
    {
        warning(" ⚠ A DNS record for {$name} already exists.");

        $selection = select(
            label: 'How do you want to proceed?',
            options: ['Choose a different hostname', 'Overwrite existing record and continue', 'Abort and delete the tunnel']
        );

        if ($selection === 'Choose a different hostname') {
            $this->deleteTunnel($this->projectConfig->hostname);
            $this->handle();

            return;
        }

        if ($selection === 'Overwrite existing record and continue') {
            $this->createDnsRecord(name: $name, overwrite: true);

            return;
        }

        $this->deleteTunnel($this->projectConfig->hostname);
        exit(0);
    }

    protected function saveProjectConfig(): void
    {
        $this->projectConfig->save();

        info(' ✔ Created project file.');
    }

    protected function askForHostname(): string
    {
        return text(
            label: 'The hostname you want to connect to this tunnel.',
            placeholder: "{$this->herdSiteName()}.domain.com",
            hint: "Use a subdomain that matches the name of this site (e.g., {$this->herdSiteName()}.domain.com).",
            validate: fn (string $value) => match (true) {
                empty($value) => 'The hostname field is required.',
                count(array_filter(explode('.', $value))) < 3 => "The hostname must be a subdomain (e.g., {$this->herdSiteName()}.domain.com).",
                default => null,
            },
        );
    }

    // TODO: Handle exit handlers in case users abort the command midway.
    // - delete tunnels that were created.
    // - delete .cloudflared.yaml file.
}
