<?php

namespace kevinoo\PanoramaWhois\Support\Facades;

use Illuminate\Support\Facades\Facade;
use kevinoo\PanoramaWhois\PanoramaWhois as RealPanoramaWhois;
use kevinoo\PanoramaWhois\Providers\AbstractProvider;


/**
 * @see \kevinoo\PanoramaWhois\PanoramaWhois
 * @method static array getWhoIS( string $domain_name, bool $cached=true )
 * @method static AbstractProvider[] getProviders()
 */
class PanoramaWhois extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RealPanoramaWhois::class;
    }
}
