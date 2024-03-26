<?php

namespace kevinoo\PanoramaWhois\Providers;

use Exception;
use DateTime;
use DateTimeZone;

/**
 * https://www.whois.com/
 */
class WhoIsCom extends AbstractProvider
{
    public static function getWhoIs( array $domain_name_info ): array
    {
        $ch = curl_init();
        curl_setopt_array($ch,[
            CURLOPT_URL => 'https://www.whois.com/whois/'. $domain_name_info['domain'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
        ]);
        preg_match( '/<pre class="df-raw" id="\w+">(?<whois_raw>.*)<\/pre>/s', curl_exec($ch), $matches );
        curl_close($ch);

        if( empty($matches['whois_raw']) ){
            return [];
        }

        return static::handleWhoIsText(
            raw_data_text: explode("\n",str_replace("\r",'',$matches['whois_raw'])),
            tld: $domain_name_info['tld'],
            whois_domain: 'https://www.whois.com/'
        );

    }
}
