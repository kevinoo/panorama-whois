<?php

namespace kevinoo\PanoramaWhois;

use Illuminate\Database\Capsule\Manager as DB;
use JetBrains\PhpStorm\ArrayShape;


class Helpers
{
    /**
     * Used to construct database SQLite connection
     * @return void
    */
    public static function boot(): void
    {
        $capsule = new DB();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'host' => __DIR__ .'/database/panorama-whois.sqlite',
            'database' => __DIR__ .'/database/panorama-whois.sqlite',
        ]);

        $capsule->addConnection([
            'driver' => env('PANORAMA_WHOIS_CACHE_DB_CONNECTION','sqlite'),
            'host' => env('PANORAMA_WHOIS_CACHE_DB_HOST',__DIR__ .'/database/cached-whois.sqlite'),
            'database' => env('PANORAMA_WHOIS_CACHE_DB_DATABASE',__DIR__ .'/database/cached-whois.sqlite'),
            'username' => env('PANORAMA_WHOIS_CACHE_DB_USERNAME'),
            'password' => env('PANORAMA_WHOIS_CACHE_DB_PASSWORD'),
        ],'cached');

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    /**
     * @param string $url
     * @return array
     */
    #[ArrayShape(['tld'=>'string', 'domain'=>'string', 'website'=>'string'])]
    public static function getUrlInfo( string $url ): array
    {
        preg_match('/(?:.*:\/\/)?([^\/?]*)/',$url, $matches);
        $website = str_replace('www.','', $matches[1]);

        // The website and domain are the same: they are an IP
        if( filter_var($website,FILTER_VALIDATE_IP) === true ){
            return [
                'website' => $website,
                'domain' => $website,
                'tld' => null,
            ];
        }

        if( count(explode('.',$website)) === 2 ){
            $tld = explode('.',$website)[1];
        } else {
            $tld = DB::selectOne("
            SELECT tld FROM tlds WHERE '$website' LIKE '%.'||tld ORDER BY INSTR('.'||tld,'$website')
        ")?->tld ?? null;
        }

        if( $tld === null ){
            return [
                'website' => $website,
                'domain' => $website,
                'tld' => null,
            ];
        }

        $cleaned_domain = str_replace(".$tld",'',$website);
        $domain = last(explode('.',$cleaned_domain)) .'.'. $tld;

        return [
            'website' => $website,
            'domain' => $domain,
            'tld' => $tld,
        ];
    }

    /**
     * Returns ISO3 code of the country
     * @param string $ip_address The IP to check
     * @return string|null
     */
    public static function retriveCountryByAddressIP( string $ip_address ): ?string
    {
        $row = DB::select("
            SELECT country_code
                FROM ip_ranges_by_countries
                WHERE :ip BETWEEN ip_from AND ip_to
        ",[':ip'=>ip2long($ip_address)])[0] ?? [];

        if( !empty($row) ){
            return static::getCountryISO3($row->country_code);
        }

        return null;
    }

    /**
     * Cast the country code iso2 to iso3
     * @param string $iso2 The code to cast
     * @return string|null
     */
    public static function getCountryISO3( string $iso2 ): ?string
    {
        static $countries_ISO3 = null;

        if ($countries_ISO3 === null) {
            $countries_ISO3 = array_column(DB::select("SELECT code, iso2 FROM countries WHERE iso2 IS NOT NULL"), 'code', 'iso2');
        }

        return $countries_ISO3[$iso2] ?? null;
    }

    /**
     * Convert a string in utf8
     * @param string $string The string to convert in utf8
     * @return string
     */
    public static function idn_to_utf8_prevent_lowercase( string $string ): string
    {
        $utf8 = idn_to_utf8($string);

        if( $utf8 === false || $utf8 === strtolower($string) ){
            return $string;
        }

        // Use the converted $idn
        return $utf8;
    }
}
