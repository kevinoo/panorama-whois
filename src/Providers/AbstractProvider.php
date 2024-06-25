<?php

namespace kevinoo\PanoramaWhois\Providers;

use Exception;
use JsonException;
use kevinoo\PanoramaWhois\Models\CachedOcrRequests;


abstract class AbstractProvider
{
    /**
     * @param array $domain_name_info
     * @return array
     */
    abstract public static function getWhoIs( array $domain_name_info ): array;

    /**
     * Using who_is response split into an array, extract all info available
     * @param array  $raw_data_text
     * @param string $tld
     * @param string $whois_domain
     * @return array
     */
    protected static function handleWhoIsText( array $raw_data_text, string $tld, string $whois_domain='' ): array
    {
        $isLineToSkip = static function( $line ){
            return empty($line) || str_starts_with($line,'*') || str_starts_with($line,'>>>') || str_starts_with($line,'%%') || str_starts_with($line,'NOTICE: ') || str_starts_with($line,'TERMS OF USE: ');
        };
        $getKeyValueByLine = static function( $line, &$previous_key ) use ($tld){

            if(
                // TODO: use a "Handler" to check "when there a new line of the WhoIS"
                /* For other TLD */  (($tld !== 'pl') && str_contains($line,':')) ||
                /* Check only for PL domains */(($tld === 'pl') && (substr_count($line,':')===1 || str_starts_with($line,'created:') || str_starts_with($line,'last modified:') || (str_contains($line,'nameservers:') && str_contains($line,'dns.pl.'))))
            ){
                $arr = explode(':', $line, 2);

                // Reset the $previous_key (section)
                $previous_key = null;

            }else if( $previous_key !== null ){
                $arr = [$previous_key,$line];
            } else {
                $arr = [$line,''];
            }

            return [
                strtolower(trim($arr[0])), // key
                trim($arr[1]), // value
            ];
        };

        $section = '';
        $who_is_info = [];
        $previous_key = null;
        $is_nic_section = false;    // Used for FR domains :-(

        $whois_section_names = [
            // Example in WhoIS text:
            // holder-c: CCDS71-FRNIC
            // admin-c: CCED209-FRNIC
            // tech-c: OVH5-FRNIC

            // This will be in the "admin" section
//            nic-hdl: CCED209-FRNIC
//            type: ORGANIZATION
//            ...

            // This will be in the "holder" section
//            nic-hdl: CCDS71-FRNIC
//            type: ORGANIZATION
//            ...

            //  'holder' => 'CCDS71-FRNIC',
            //  'admin' => 'CCED209-FRNIC',
            //  'tech' => 'OVH5-FRNIC',
            //  other...? Maybe... :-)
        ];

        // Retrieve "$whois_section_names" by "NIC Handle" ("nic-hdl" key), used by ".fr" domains
        foreach( $raw_data_text as $line ){

            if( $isLineToSkip($line) ){
                continue;
            }

            [$key,$value] = $getKeyValueByLine($line,$previous_key);

            if( str_ends_with($key,'-c') ){
                // holder-c | admin-c | tech-c | zone-c
                $key_name = substr($key,0,-2);
                if( empty($whois_section_names[$key_name]) ){
                    $whois_section_names[$key_name] = $value;
                }
            }
        }

        // Retrive all info parsing all rows in $raw_data_text
        foreach( $raw_data_text as $line ){
            $line = trim(str_replace(['&gt;','&lt;'],'',$line));

            if( $line === "" ){
                // New section info
                $section = '';
                $is_nic_section = false;
            }

            if( $isLineToSkip($line) ){
                continue;
            }

            $lower_line = strtolower($line);

            if( $section === 'technical ' && str_starts_with($lower_line,'nserver:') ){
                // Case for WhoIS "servizi.garr.it"
                $section = '';
                $previous_key = $lower_line;
            }else if( !$is_nic_section && (($start_with_contact = str_starts_with($lower_line,'contact:')) || in_array($lower_line,['registrant','admin contact','technical contacts','registrar','nameservers'])) ){
                $section = ($start_with_contact ? trim(str_replace('contact:','',$lower_line)) : $lower_line) .' ';
                $previous_key = $lower_line === 'nameservers' ? $lower_line : null; // Caso particolare
                continue;
            }

            [$key,$value] = $getKeyValueByLine($line,$previous_key);

            if(
                str_starts_with($value,'Please query') || str_starts_with($value,'Whois protection') ||
                in_array(strtolower($value),['redacted for privacy','data protected','expired expired','redacted'],true)
            ){
                continue;
            }

            if( str_starts_with($value,'<img src') ){
                try {
                    preg_match('/<img src="(?<image_url>\/eimg\/[\w\/]+\/(?<image_hash>\w+)\.png)"[ \w="]+>(?<email>@[\w.-]+)/',$value,$matches);
                    $value = static::parseImageContent($whois_domain.trim($matches['image_url'],'/')) . $matches['email'];
                }catch( Exception $e ){
                    $value = '['. $whois_domain.trim($matches['image_url'],'/') .']' . $matches['email'];
                }
            }

            // Restore the correct section when in the WhoIS response used the "nic-hdl" header section
            // ITA: a volte nella risposta non vengono divise le sezioni con delle parole, ma viene usata la chiave "nic-hdl"
            // per determinare che cosa indica quella sezione di testo. Usata spesso dai (maledetti) registri francesi.
            if( $key === 'nic-hdl' ){
                $is_nic_section = true;
                $section = (array_search($value,$whois_section_names,true) ?: $value) .'_';
                $key = 'code';
            }

            if( $previous_key === null && str_contains($line, ':') ){
                $previous_key = $key;
            }

            $info_key = str_replace(' ', '_', (($previous_key === 'nameservers') ? $previous_key : $section . $key) );

            if( isset($who_is_info[$info_key]) && ($who_is_info[$info_key] !== $value) ){
                if( !is_array($who_is_info[$info_key]) ){
                    $who_is_info[$info_key] = [$who_is_info[$info_key]];
                }
                if( !in_array($value,$who_is_info[$info_key],true) ){
                    $who_is_info[$info_key][] = $value;
                }
            } else {
                $who_is_info[$info_key] = $value;
            }
        }

        unset(
            $who_is_info['url_of_the_icann_whois_inaccuracy_complaint_form'],
            $who_is_info['for_more_information_on_whois_status_codes,_please_visit_https'],
            $who_is_info['notice'],
            $who_is_info['terms_of_use'],
            $who_is_info['by_the_following_terms_of_use'],
            $who_is_info['to'],
            $who_is_info['https'],
        );

        return $who_is_info;
    }


    protected static function parseImageContent( string $image_url ): string
    {
        if( empty(env('PANORAMA_WHOIS_OCR_APIKEY')) ){
            return '';
        }

        /** @var $cached_content CachedOcrRequests */
        $cached_content = CachedOcrRequests::find($image_url);
        if( $cached_content !== null ){
            return $cached_content->content;
        }

        $ch = curl_init();
        curl_setopt_array($ch,[
            CURLOPT_URL => 'https://api.ocr.space/parse/image',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: '. env('PANORAMA_WHOIS_OCR_APIKEY')
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'base64Image' => 'data:image/png;base64,'. base64_encode(file_get_contents($image_url)),
                'filetype' => 'PNG',
            ]
        ]);
        $response = curl_exec($ch);

        if( !empty($response) ){
            try{
                $response = json_decode( $response, true, 512, JSON_THROW_ON_ERROR );
            }catch( JsonException $e ){
                $response = [];
            }
        }
        curl_close($ch);

        $parsed_string = trim($response['ParsedResults'][0]['ParsedText'] ?? null);

        if( empty($parsed_string) || (!empty($response) && ($response['IsErroredOnProcessing'] === true)) ){
            $parsed_string = "[$image_url]";
        }

        try {
            CachedOcrRequests::insert([
                'image_url' => $image_url,
                'content' => $parsed_string,
            ]);
        }catch( Exception $e ){ }

        return $parsed_string;
    }
}
