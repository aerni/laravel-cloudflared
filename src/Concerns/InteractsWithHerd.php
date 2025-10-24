<?php

namespace Aerni\Cloudflared\Concerns;

use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\info;

trait InteractsWithHerd
{
    protected function verifyHerdFoundInPath(): void
    {
        if (Process::run('herd --version')->failed()) {
            $this->fail('Laravel Herd not found in PATH.');
        }
    }

    protected function createHerdLink(string $hostname): void
    {
        Process::run("herd link {$hostname}")->throw();

        info(' ✔ Created Herd link.');
    }

    protected function deleteHerdLink(string $hostname): void
    {
        Process::run("herd unlink {$hostname}")->throw();

        info(' ✔ Deleted Herd link.');
    }

    protected function herdSiteName(): string
    {
        return basename(base_path());
    }
}
