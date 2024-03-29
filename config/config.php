<?php

return [

    'cache' => [

        // Define the default value of the $cached param for PanoramaWhois::getWhoIS() method
        // TRUE => Returns the cached value if is a valid cache (check "ttl" key)
        // FALSE => Returns always refreshed response (saving the result into the cache)
        'enable' => true,

        // The ttl of the cache in seconds (default: 2 weeks = 14 days)
        // The whois cached is valid for this value
        'ttl' => 1296000

    ],

    /**
     * List of providers to call for retrieve Whois data
     * @see \kevinoo\PanoramaWhois\Providers\AbstractProvider
    */
    'whois_providers' => [
        kevinoo\PanoramaWhois\Providers\WhoIsCom::class,
        kevinoo\PanoramaWhois\Providers\PhpWhoisLibrary::class,
        kevinoo\PanoramaWhois\Providers\GARRServices::class,
    ],

];
