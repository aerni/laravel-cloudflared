<?php

namespace Aerni\Cloudflared\Concerns;

use Aerni\Cloudflared\Data\TunnelDetails;
use Aerni\Cloudflared\Exceptions\DnsRecordAlreadyExistsException;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

trait InteractsWithTunnel
{
    protected function verifyCloudflaredFoundInPath(): void
    {
        if (Process::run('cloudflared --version')->failed()) {
            $this->fail('cloudflared not found in PATH.');
        }
    }

    protected function tunnelExists(string $name): bool
    {
        return spin(
            callback: fn () => Process::run("cloudflared tunnel info {$name}")->successful(),
            message: "Verifying tunnel {$name} exists"
        );
    }

    protected function createTunnel(): TunnelDetails
    {
        $name = $this->generateUniqueTunnelName();

        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel create {$name}"),
            message: "Creating tunnel: {$name}"
        );

        $result->throw();

        if (! preg_match('/Created tunnel .+ with id ([a-f0-9\-]+)/', $result->output(), $tunnelMatch)) {
            $this->fail('Unable to extract tunnel ID from cloudflared output.');
        }

        info(" ✔ Created tunnel: {$name}");

        return new TunnelDetails(id: $tunnelMatch[1], name: $name);
    }

    protected function deleteTunnel(string $name): void
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel delete {$name}"),
            message: "Deleting tunnel: {$name}"
        );

        if ($result->seeInErrorOutput("there should only be 1 non-deleted Tunnel named {$name}")) {
            warning(" ⚠ Can't delete tunnel {$name} because it doesn't exist.");

            return;
        }

        $result->throw();

        info(" ✔ Deleted tunnel: {$name}");
    }

    protected function createDnsRecord(string $id, string $hostname): void
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel route dns {$id} {$hostname}"),
            message: "Creating DNS record: {$hostname}"
        );

        if ($result->seeInErrorOutput('Failed to add route: code: 1003')) {
            throw new DnsRecordAlreadyExistsException($hostname);
        }

        $result->throw();

        info(" ✔ Created DNS record: {$hostname}");
    }

    protected function overwriteDnsRecord(string $id, string $hostname): void
    {
        $result = spin(
            callback: fn () => Process::run("cloudflared tunnel route dns --overwrite-dns {$id} {$hostname}"),
            message: "Overwriting DNS record: {$hostname}"
        );

        $result->throw();

        info(" ✔ Overwrote DNS record: {$hostname}");
    }

    protected function generateUniqueTunnelName(): string
    {
        $projectName = basename(base_path());
        $randomId = Str::lower(Str::random(8));

        return "{$projectName}-{$randomId}";
    }
}
