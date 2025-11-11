<?php

namespace Aerni\Cloudflared\Console\Commands;

use Aerni\Cloudflared\Concerns\InteractsWithHerd;
use Aerni\Cloudflared\Concerns\InteractsWithTunnel;
use Aerni\Cloudflared\Concerns\ManagesProject;
use Aerni\Cloudflared\Exceptions\DnsRecordAlreadyExistsException;
use Aerni\Cloudflared\Facades\Cloudflared;
use Aerni\Cloudflared\ProjectConfig;
use Aerni\Cloudflared\TunnelConfig;
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
    use InteractsWithHerd, InteractsWithTunnel, ManagesProject;

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
        $hostname = $this->askForHostname();

        $vite = confirm(
            label: 'Are you planning to use vite-plugin-laravel-cloudflared?',
            hint: 'This will create a DNS record for Vite.',
        );

        $tunnelDetails = $this->createTunnel();

        $projectConfig = Cloudflared::makeProjectConfig(
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
                'Change hostname',
                'Repair DNS records',
                'Delete and recreate tunnel',
            ],
            default: 'Keep existing configuration'
        );

        match ($selection) {
            'Keep existing configuration' => $this->keepExisting(),
            'Change hostname' => $this->changeHostname($tunnelConfig->projectConfig),
            'Repair DNS records' => $this->repairDnsRecords($tunnelConfig->projectConfig),
            'Delete and recreate tunnel' => $this->recreateTunnel($tunnelConfig),
        };
    }

    protected function keepExisting(): void
    {
        error(' ⚠ Cancelled.');
        exit(0);
    }

    // Todo: If we use the Cloudflare API in the future, this should also delete the old DNS records.
    protected function changeHostname(ProjectConfig $config): void
    {
        $oldHostname = $config->hostname;
        $config->hostname = $this->askForHostname();

        $this->createDnsRecords($config);
        $this->deleteHerdLink($oldHostname);
        $this->createHerdLink($config->hostname);

        $config->save();

        info(" ✔ Changed hostname to: {$config->hostname}");
    }

    protected function repairDnsRecords(ProjectConfig $config): void
    {
        $message = $config->vite
            ? "Are you sure you want to update the DNS records for {$config->hostname} and {$config->viteHostname()} to point to your tunnel?"
            : "Are you sure you want to update the DNS record for {$config->hostname} to point to your tunnel?";

        $hint = $config->vite
            ? 'This will overwrite the existing DNS records.'
            : 'This will overwrite the existing DNS record.';

        if (! confirm(label: $message, hint: $hint)) {
            error(' ⚠ Cancelled.');
            exit(0);
        }

        $this->overwriteDnsRecord($config->id, $config->hostname);

        if ($config->vite) {
            $this->overwriteDnsRecord($config->id, $config->viteHostname());
        }

        info(' ✔ DNS records updated.');
    }

    protected function recreateTunnel(TunnelConfig $tunnelConfig): void
    {
        $this->deleteTunnel($tunnelConfig->name());
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

        $projectConfig->hostname = $this->askForHostname();
        $this->createDnsRecords($projectConfig);
    }

    protected function askForHostname(): string
    {
        return text(
            label: 'What hostname do you want to connect to this tunnel?',
            placeholder: "{$this->herdSiteName()}.domain.com",
            hint: "Use a subdomain that matches the name of this site (e.g., {$this->herdSiteName()}.domain.com).",
            validate: fn (string $value) => match (true) {
                empty($value) => 'The hostname is required.',
                count(array_filter(explode('.', $value))) < 3 => "The hostname must be a subdomain (e.g., {$this->herdSiteName()}.domain.com).",
                default => null,
            },
        );
    }
}
