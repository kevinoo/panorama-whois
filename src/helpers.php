<?php

use Illuminate\Database\Capsule\Manager as DB;
use JetBrains\PhpStorm\ArrayShape;


$capsule = new DB();
$capsule->addConnection([
    "driver" => "sqlite",
    "host" => '../src/database/panorama-whois.sqlite',
    "database" => '../src/database/panorama-whois.sqlite',
    "username" => "root",
    "password" => ""
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

/**
 * @param string $url
 * @return array
 */
#[ArrayShape(['tld'=>'string', 'domain'=>'string', 'website'=>'string'])]
function getUrlInfo( string $url ): array
{
    preg_match('/(?:.*:\/\/)?([^\/?]*)/',$url, $matches);
    $website = str_replace('www.','', $matches[1]);

    // The website and domain are the same: they are an IP
    if( filter_var($website,FILTER_VALIDATE_IP) ){
        return [
            'website' => $website,
            'domain' => $website,
            'tld' => null,
        ];
    }

    if( count(explode('.',$website)) === 2 ){
        $tld = explode('.',$website)[1];
    }else{
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
 * @param string $ip_address
 * @return string|null
 */
function retriveCountryByAddressIP( string $ip_address ): ?string
{
    $row = DB::select("
        SELECT country_code
            FROM ip_ranges_by_countries
            WHERE :ip BETWEEN ip_from AND ip_to
    ",[':ip'=>ip2long($ip_address)])[0] ?? [];

    if( !empty($row) ){
        return getCountryISO3($row->country_code);
    }

    return null;
}

function getCountryISO3( string $iso2 ): ?string
{
    $countries_ISO3 = [
        'AW' => 'ABW',
        'AF' => 'AFG',
        'AO' => 'AGO',
        'AI' => 'AIA',
        'AX' => 'ALA',
        'AL' => 'ALB',
        'AD' => 'AND',
        'AE' => 'ARE',
        'AR' => 'ARG',
        'AM' => 'ARM',
        'AS' => 'ASM',
        'AQ' => 'ATA',
        'TF' => 'ATF',
        'AG' => 'ATG',
        'AU' => 'AUS',
        'AT' => 'AUT',
        'AZ' => 'AZE',
        'BI' => 'BDI',
        'BE' => 'BEL',
        'BJ' => 'BEN',
        'BQ' => 'BES',
        'BF' => 'BFA',
        'BD' => 'BGD',
        'BG' => 'BGR',
        'BH' => 'BHR',
        'BS' => 'BHS',
        'BA' => 'BIH',
        'BL' => 'BLM',
        'BY' => 'BLR',
        'BZ' => 'BLZ',
        'BM' => 'BMU',
        'BO' => 'BOL',
        'BR' => 'BRA',
        'BB' => 'BRB',
        'BN' => 'BRN',
        'BT' => 'BTN',
        'BV' => 'BVT',
        'BW' => 'BWA',
        'CF' => 'CAF',
        'CA' => 'CAN',
        'CC' => 'CCK',
        'CH' => 'CHE',
        'CL' => 'CHL',
        'CN' => 'CHN',
        'CI' => 'CIV',
        'CM' => 'CMR',
        'CD' => 'COD',
        'CG' => 'COG',
        'CK' => 'COK',
        'CO' => 'COL',
        'KM' => 'COM',
        'CV' => 'CPV',
        'CR' => 'CRI',
        'CU' => 'CUB',
        'CW' => 'CUW',
        'CX' => 'CXR',
        'KY' => 'CYM',
        'CY' => 'CYP',
        'CZ' => 'CZE',
        'DE' => 'DEU',
        'DJ' => 'DJI',
        'DM' => 'DMA',
        'DK' => 'DNK',
        'DO' => 'DOM',
        'DZ' => 'DZA',
        'EC' => 'ECU',
        'EG' => 'EGY',
        'ER' => 'ERI',
        'EH' => 'ESH',
        'ES' => 'ESP',
        'EE' => 'EST',
        'ET' => 'ETH',
        'FI' => 'FIN',
        'FJ' => 'FJI',
        'FK' => 'FLK',
        'FR' => 'FRA',
        'FO' => 'FRO',
        'FM' => 'FSM',
        'GA' => 'GAB',
        'GB' => 'GBR',
        'GE' => 'GEO',
        'GG' => 'GGY',
        'GH' => 'GHA',
        'GI' => 'GIB',
        'GN' => 'GIN',
        'GP' => 'GLP',
        'GM' => 'GMB',
        'GW' => 'GNB',
        'GQ' => 'GNQ',
        'GR' => 'GRC',
        'GD' => 'GRD',
        'GL' => 'GRL',
        'GT' => 'GTM',
        'GF' => 'GUF',
        'GU' => 'GUM',
        'GY' => 'GUY',
        'HK' => 'HKG',
        'HM' => 'HMD',
        'HN' => 'HND',
        'HR' => 'HRV',
        'HT' => 'HTI',
        'HU' => 'HUN',
        'ID' => 'IDN',
        'IM' => 'IMN',
        'IN' => 'IND',
        'IO' => 'IOT',
        'IE' => 'IRL',
        'IR' => 'IRN',
        'IQ' => 'IRQ',
        'IS' => 'ISL',
        'IL' => 'ISR',
        'IT' => 'ITA',
        'JM' => 'JAM',
        'JE' => 'JEY',
        'JO' => 'JOR',
        'JP' => 'JPN',
        'KZ' => 'KAZ',
        'KE' => 'KEN',
        'KG' => 'KGZ',
        'KH' => 'KHM',
        'KI' => 'KIR',
        'KN' => 'KNA',
        'KR' => 'KOR',
        'KW' => 'KWT',
        'LA' => 'LAO',
        'LB' => 'LBN',
        'LR' => 'LBR',
        'LY' => 'LBY',
        'LC' => 'LCA',
        'LI' => 'LIE',
        'LK' => 'LKA',
        'LS' => 'LSO',
        'LT' => 'LTU',
        'LU' => 'LUX',
        'LV' => 'LVA',
        'MO' => 'MAC',
        'MF' => 'MAF',
        'MA' => 'MAR',
        'MC' => 'MCO',
        'MD' => 'MDA',
        'MG' => 'MDG',
        'MV' => 'MDV',
        'MX' => 'MEX',
        'MH' => 'MHL',
        'MK' => 'MKD',
        'ML' => 'MLI',
        'MT' => 'MLT',
        'MM' => 'MMR',
        'ME' => 'MNE',
        'MN' => 'MNG',
        'MP' => 'MNP',
        'MZ' => 'MOZ',
        'MR' => 'MRT',
        'MS' => 'MSR',
        'MQ' => 'MTQ',
        'MU' => 'MUS',
        'MW' => 'MWI',
        'MY' => 'MYS',
        'YT' => 'MYT',
        'NA' => 'NAM',
        'NC' => 'NCL',
        'NE' => 'NER',
        'NF' => 'NFK',
        'NG' => 'NGA',
        'NI' => 'NIC',
        'NU' => 'NIU',
        'NL' => 'NLD',
        'NO' => 'NOR',
        'NP' => 'NPL',
        'NR' => 'NRU',
        'NZ' => 'NZL',
        'OM' => 'OMN',
        'PK' => 'PAK',
        'PA' => 'PAN',
        'PN' => 'PCN',
        'PE' => 'PER',
        'PH' => 'PHL',
        'PW' => 'PLW',
        'PG' => 'PNG',
        'PL' => 'POL',
        'PR' => 'PRI',
        'KP' => 'PRK',
        'PT' => 'PRT',
        'PY' => 'PRY',
        'PS' => 'PSE',
        'PF' => 'PYF',
        'QA' => 'QAT',
        'RE' => 'REU',
        'RO' => 'ROU',
        'RU' => 'RUS',
        'RW' => 'RWA',
        'SA' => 'SAU',
        'SD' => 'SDN',
        'SN' => 'SEN',
        'SG' => 'SGP',
        'GS' => 'SGS',
        'SH' => 'SHN',
        'SJ' => 'SJM',
        'SB' => 'SLB',
        'SL' => 'SLE',
        'SV' => 'SLV',
        'SM' => 'SMR',
        'SO' => 'SOM',
        'PM' => 'SPM',
        'RS' => 'SRB',
        'SS' => 'SSD',
        'ST' => 'STP',
        'SR' => 'SUR',
        'SK' => 'SVK',
        'SI' => 'SVN',
        'SE' => 'SWE',
        'SZ' => 'SWZ',
        'SX' => 'SXM',
        'SC' => 'SYC',
        'SY' => 'SYR',
        'TC' => 'TCA',
        'TD' => 'TCD',
        'TG' => 'TGO',
        'TH' => 'THA',
        'TJ' => 'TJK',
        'TK' => 'TKL',
        'TM' => 'TKM',
        'TL' => 'TLS',
        'TO' => 'TON',
        'TT' => 'TTO',
        'TN' => 'TUN',
        'TR' => 'TUR',
        'TV' => 'TUV',
        'TW' => 'TWN',
        'TZ' => 'TZA',
        'UG' => 'UGA',
        'UA' => 'UKR',
        'UM' => 'UMI',
        'UY' => 'URY',
        'US' => 'USA',
        'UZ' => 'UZB',
        'VA' => 'VAT',
        'VC' => 'VCT',
        'VE' => 'VEN',
        'VG' => 'VGB',
        'VI' => 'VIR',
        'VN' => 'VNM',
        'VU' => 'VUT',
        'WF' => 'WLF',
        'WS' => 'WSM',
        'YE' => 'YEM',
        'ZA' => 'ZAF',
        'ZM' => 'ZMB',
        'ZW' => 'ZWE',
    ];
    return $countries_ISO3[$iso2] ?? null;
}

function idn_to_utf8_prevent_lowercase( string $string ): string
{
    $utf8 = idn_to_utf8($string);

    if( $utf8 === false || $utf8 === strtolower($string) ){
        return $string;
    }

    // Use the converted $idn
    return $utf8;
}

function get_class_name( $object ): string
{
    static $cached = [];

    $full_name = get_class($object);

    if( !empty($cached[$full_name]) ){
        return $cached[$full_name];
    }

    $split = explode('\\',$full_name);
    $cached[$full_name] = end($split);

    return $cached[$full_name];
}

function send_telegram_message( $telegram_api, $chat_id, string|array|object $message ): void
{
    if( !is_string($message) ){
        ob_start();
        print_r($message);

        $message = '```'. str_replace(
            ['_','*','[',']','(',')','~','`','>','#','+','-','=','|','{','}','.','!','    \\['],
            ['\\_','\\*','\\[','\\]','\\(','\\)','\\~','\\`','\\>','\\#','\\+','\\-','\\=','\\|','\\{','\\}','\\.','\\!','  \\['],
            ob_get_clean()
        ) .'```';
    }

    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL => "https://api.telegram.org/bot{$telegram_api}/sendMessage?". http_build_query([
            'chat_id' => $chat_id,
            'parse_mode' => 'MarkdownV2',
            'text' => $message
        ]),
        CURLOPT_RETURNTRANSFER => true
    ]);
    curl_exec($ch);
    curl_close($ch);
}
