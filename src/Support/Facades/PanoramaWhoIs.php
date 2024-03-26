<?php

namespace kevinoo\PanoramaWhois\Support\Facades;

use Illuminate\Support\Facades\Facade;
use kevinoo\PanoramaWhois\PanoramaWhoIs as RealPanoramaWhoIs;
use kevinoo\PanoramaWhois\Providers\AbstractProvider;


/**
 * @method static array defaultProviders()
 * @method static array addProvider( AbstractProvider $provider )
 * @method static array setProviders( array $providers )
 * @method static array getWhoIS( string $domain_name, bool $cached )
 */
class PanoramaWhoIs extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RealPanoramaWhoIs::class;
    }
}
