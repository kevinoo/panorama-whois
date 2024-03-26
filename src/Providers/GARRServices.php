<?php

namespace kevinoo\PanoramaWhois\Providers;

use Exception;
use DateTime;
use DateTimeZone;


/**
 * https://www.servizi.garr.it/
 */
class GARRServices extends AbstractProvider
{
    public static function getWhoIs( array $domain_name_info ): array
    {
        $ch = curl_init();
        curl_setopt_array($ch,[
            CURLOPT_URL => 'https://www.servizi.garr.it/code-s/whois.php',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'net' => $domain_name_info['domain'],
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
        ]);
        curl_close($ch);

        $whois_raw = explode('<tr>',substr(curl_exec($ch),stripos(curl_exec($ch),'<td>domain')));
        unset($whois_raw[0]);

        foreach( $whois_raw as &$row ){
            $row = trim(strip_tags($row));
        }
        unset($row);

        return static::handleWhoIsText($whois_raw,$domain_name_info['tld']);
    }
}
