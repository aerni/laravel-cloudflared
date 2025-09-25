<?php

namespace Aerni\Cloudflared\Concerns;

use Illuminate\Support\Facades\Process;
use function Laravel\Prompts\info;

trait InteractsWithHerd
{
    protected function createHerdLink(string $hostname)
    {
        Process::run("herd link {$hostname}")->throw();

        info("<info>[✔]</info> Created Herd link: {$hostname}");
    }

    protected function deleteHerdLink(string $hostname): void
    {
        Process::run("herd unlink {$hostname}")->throw();

        info("<info>[✔]</info> Deleted Herd link: {$hostname}");
    }

    protected function herdSiteName(): string
    {
        return basename(base_path());
    }
}
