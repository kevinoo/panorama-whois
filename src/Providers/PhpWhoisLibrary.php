<?php

namespace kevinoo\PanoramaWhois\Providers;

use kevinoo\PanoramaWhois\Helpers;

/**
 *
 */
class PhpWhoisLibrary extends AbstractProvider
{
    public static function getWhoIs( array $domain_name_info ): array
    {
        $whois = new \phpWhois\Whois();
        $whois->deepWhois = true;
        $who_is_result = $whois->lookup(Helpers::idn_to_utf8_prevent_lowercase($domain_name_info['domain']));

        return static::handleWhoIsText( (array) ($who_is_result['rawdata'] ?? []), $domain_name_info['tld'] );
    }
}
