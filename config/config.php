<?php

return [

    'whois_providers' => [
        kevinoo\PanoramaWhois\Providers\WhoIsCom::class,
        kevinoo\PanoramaWhois\Providers\PhpWhoisLibrary::class,
        kevinoo\PanoramaWhois\Providers\GARRServices::class,
    ],

];
