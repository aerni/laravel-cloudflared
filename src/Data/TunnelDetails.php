<?php

namespace Aerni\Cloudflared\Data;

class TunnelDetails
{
    public function __construct(
        public readonly string $id,
        public readonly string $name
    ) {}
}
