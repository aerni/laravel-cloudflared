<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\InteractsWithCloudflareApi;
use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\Concerns\ManagesProject;
use Aerni\Cloudflared\Data\ProjectConfig;
use Aerni\Cloudflared\Data\TunnelConfig;
use Aerni\Cloudflared\Exceptions\DnsRecordAlreadyExistsException;
use Aerni\Cloudflared\Facades\Cloudflared;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class CloudflaredInstall extends Command
{
    use InteractsWithCloudflareApi, InteractsWithHerd, InteractsWithTunnel, ManagesProject;

    protected $signature = 'cloudflared:install';

    protected $description = 'Create a Cloudflare Tunnel for this project.';

    public function handle()
    {
        $this->verifyCloudflaredFoundInPath();
        $this->verifyHerdFoundInPath();

        Cloudflared::isInstalled()
            ? $this->handleExistingInstallation()
            : $this->handleNewInstallation();
    }

    protected function handleNewInstallation(): void
    {
        $hostname = $this->askForSubdomain();

        $vite = confirm(
            label: 'Are you planning to use vite-plugin-laravel-cloudflared?',
            hint: "Creates DNS record: vite-{$hostname}",
        );

        $tunnelDetails = $this->createTunnel();

        $projectConfig = new ProjectConfig(
            id: $tunnelDetails->id,
            name: $tunnelDetails->name,
            hostname: $hostname,
            vite: $vite
        );

        $this->createDnsRecords($projectConfig);
        $this->createHerdLink($projectConfig->hostname);
        $this->saveProjectConfig($projectConfig);
    }

    protected function handleExistingInstallation(): void
    {
        $tunnelConfig = Cloudflared::tunnelConfig();

        if (! $this->tunnelExists($tunnelConfig->name())) {
            warning(" ⚠ Tunnel {$tunnelConfig->name()} doesn't exist. Cleaning up old configs and creating a new tunnel.");

            $this->deleteHerdLink($tunnelConfig->hostname());
            $this->deleteProject($tunnelConfig);
            $this->handleNewInstallation();

            return;
        }

        warning(" ⚠ Tunnel {$tunnelConfig->name()} exists.");

        $selection = select(
            label: 'What would you like to do?',
            options: [
                'Keep existing configuration',
                'Change subdomain',
                'Repair DNS records',
                'Delete and recreate tunnel',
            ],
            default: 'Keep existing configuration'
        );

        match ($selection) {
            'Keep existing configuration' => $this->keepExisting(),
            'Change subdomain' => $this->changeSubdomain($tunnelConfig->projectConfig),
            'Repair DNS records' => $this->repairDnsRecords($tunnelConfig->projectConfig),
            'Delete and recreate tunnel' => $this->recreateTunnel($tunnelConfig),
        };
    }

    protected function keepExisting(): void
    {
        error(' ⚠ Cancelled.');
        exit(0);
    }

    protected function changeSubdomain(ProjectConfig $projectConfig): void
    {
        $oldHostname = $projectConfig->hostname;
        $oldViteHostname = $projectConfig->viteHostname();

        $projectConfig->hostname = $this->askForSubdomain();

        $this->deleteDnsRecord($oldHostname);

        if ($projectConfig->vite) {
            $this->deleteDnsRecord($oldViteHostname);
        }

        $this->deleteHerdLink($oldHostname);

        $this->createDnsRecords($projectConfig);

        $this->createHerdLink($projectConfig->hostname);

        $projectConfig->save();
    }

    protected function repairDnsRecords(ProjectConfig $projectConfig): void
    {
        $message = $projectConfig->vite
            ? "Are you sure you want to update the DNS records for {$projectConfig->hostname} and {$projectConfig->viteHostname()} to point to your tunnel?"
            : "Are you sure you want to update the DNS record for {$projectConfig->hostname} to point to your tunnel?";

        $hint = $projectConfig->vite
            ? 'This will overwrite the existing DNS records.'
            : 'This will overwrite the existing DNS record.';

        if (! confirm(label: $message, hint: $hint)) {
            error(' ⚠ Cancelled.');
            exit(0);
        }

        $this->overwriteDnsRecord($projectConfig->id, $projectConfig->hostname);

        if ($projectConfig->vite) {
            $this->overwriteDnsRecord($projectConfig->id, $projectConfig->viteHostname());
        }
    }

    protected function recreateTunnel(TunnelConfig $tunnelConfig): void
    {
        $this->deleteTunnel($tunnelConfig->name());
        $this->deleteDnsRecord($tunnelConfig->hostname());

        if ($tunnelConfig->projectConfig->vite) {
            $this->deleteDnsRecord($tunnelConfig->viteHostname());
        }

        $this->deleteHerdLink($tunnelConfig->hostname());
        $this->deleteProject($tunnelConfig);
        $this->handleNewInstallation();
    }

    protected function createDnsRecords(ProjectConfig $projectConfig): void
    {
        $hostnames = [$projectConfig->hostname];

        if ($projectConfig->vite) {
            $hostnames[] = $projectConfig->viteHostname();
        }

        $existingRecords = [];

        foreach ($hostnames as $hostname) {
            try {
                $this->createDnsRecord($projectConfig->id, $hostname);
            } catch (DnsRecordAlreadyExistsException $e) {
                $existingRecords[] = $hostname;
            }
        }

        if (! empty($existingRecords)) {
            $this->handleExistingDnsRecords($projectConfig, $existingRecords);
        }
    }

    protected function handleExistingDnsRecords(ProjectConfig $projectConfig, array $existingRecords): void
    {
        warning(' ⚠ '.trans_choice(
            '{1} DNS record :records already exists.|[2,*] DNS records :records already exist.',
            count($existingRecords),
            ['records' => Arr::join($existingRecords, ', ', ' and ')]
        ));

        $selection = select(
            label: 'How do you want to proceed?',
            options: [
                'Overwrite and point to your tunnel',
                'Choose a different hostname',
            ]
        );

        if ($selection === 'Overwrite and point to your tunnel') {
            foreach ($existingRecords as $hostname) {
                $this->overwriteDnsRecord($projectConfig->id, $hostname);
            }

            return;
        }

        $projectConfig->hostname = $this->askForSubdomain();
        $this->createDnsRecords($projectConfig);
    }

    protected function askForSubdomain(): string
    {
        $domain = $this->authenticatedDomain();

        $subdomain = text(
            label: 'What subdomain do you want to use for this tunnel?',
            placeholder: $this->herdSiteName(),
            hint: "The tunnel will be available at {subdomain}.{$domain}",
        );

        return "{$subdomain}.{$domain}";
    }
}
