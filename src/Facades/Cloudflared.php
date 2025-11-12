<?php

namespace Aerni\Cloudflared\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isInstalled()
 * @method static \Aerni\Cloudflared\ProjectConfig projectConfig()
 * @method static \Aerni\Cloudflared\TunnelConfig tunnelConfig()
 *
 * @see \Aerni\Cloudflared\Cloudflared
 */
class Cloudflared extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Aerni\Cloudflared\Cloudflared::class;
    }
}
