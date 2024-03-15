<?php

namespace kevinoo\PanoramaWhois;

use Exception;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Capsule\Manager as DB;


class PanoramaWhoIs
{
    /**
     * Return the WhoIs info
     * @param string    $domain_name
     * @return array
     * @throws Exception
     */
    public static function getWhoIS( string $domain_name ): array
    {
//        if( is_null($cached) ){
//            $cached = request()?->header('x-cached','true') === 'true';
//        }

        $domain_name_info = Helpers::getUrlInfo($domain_name);
//        $website = $domain_name_info['website'];
//        $who_is_data = Domain::find($website ?? $domain_name)?->who_is_data;

//        if( $cached && !empty($who_is_data) ){
//            return $who_is_data;
//        }

        $propriety_info = [];
        // registrar - con chi è stato registrato (Aruba, TopHost, ecc...)
        $registrar_info = [];
        $domain_info = [];
        $registrant_info = [];
        $admin_info = [];
        $technical_info = [];

        // Too little data in the text, I try with other WHOIS
        $who_is_info = static::getWhoIsFromWhoIsCom($domain_name_info);

        // Too little data, I try with other WHOIS
        if( count($who_is_info) < 15 ){

            $whois = new \phpWhois\Whois();
            $whois->deepWhois = true;
            $who_is_result = $whois->lookup(Helpers::idn_to_utf8_prevent_lowercase($domain_name));

            // proprietario - persona fisica o azienda che ha comprato il dominio
            $propriety_info = $who_is_result['regrinfo'] ?? [];
            // registrar - con chi è stato registrato (Aruba, TopHost, ecc...)
            $registrar_info = $who_is_result['regyinfo'] ?? [];
            $domain_info = $propriety_info['domain'] ?? [];
            $registrant_info = $propriety_info['owner'] ?? [];
            $admin_info = $propriety_info['admin'] ?? [];
            $technical_info = $propriety_info['tech'] ?? [];
            $who_is_info = static::handleWhoIsText( (array) ($who_is_result['rawdata'] ?? []), $domain_name_info['tld'] );

            // Too little data, I try with other WHOIS
            if( count($who_is_info) < 15 ){
                $who_is_info = static::getWhoIsFromGARRServices($domain_name_info);
            }
        }

        $domain_data = static::handleDomainInfo($domain_info,$who_is_info,$propriety_info);

        /** @noinspection PhpUnhandledExceptionInspection */
        $who_is_data = [
            'last_update' => (new DateTime('now',new DateTimeZone('UTC')))->format('c'), // 2022-09-21T09:44:43+00:00
            'registrar' => static::handleRegistrarInfo($registrar_info,$who_is_info),
            'domain' => $domain_data,
            'registrant' => static::handleRegistrantInfo($registrant_info,$who_is_info),
            'admin' => static::handleAdminInfo($admin_info,$who_is_info),
            'technical' => static::handleTechnicalInfo($technical_info,$who_is_info,$domain_data),
        ];

//        Domain::updateOrCreate([
//            'domain' => $domain_name,
//        ],[
//            'who_is_data' => $who_is_data,
//        ]);

        return $who_is_data;
    }

    /**
     * https://www.whois.com/
     */
    protected static function getWhoIsFromWhoIsCom( array $domain_name_info ): array
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

    /**
     * https://www.servizi.garr.it/
    */
    protected static function getWhoIsFromGARRServices( $domain_name_info ): array
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
                preg_match('/<img src="(?<image_path>\/eimg\/[\w\/]+\/(?<image_hash>\w+)\.png)"[ \w="]+>(?<email>@[\w.-]+)/',$value,$matches);
//                $value = static::parseImageContent($whois_domain.trim($matches['image_path'],'/')) . $matches['email'];
                $value = '['. $whois_domain.trim($matches['image_path'],'/') .']' . $matches['email'];
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

    protected static function parseImageContent( $image_path ): string
    {
        // OCR necessary
        return $image_path;

//        $REDIS_KEY = 'ABP:WhoIS:ImagesContents';
//
//        if( ($content = Redis::hget($REDIS_KEY,$image_path)) !== false ){
//            return $content;
//        }
//
//        $ch = curl_init();
//        curl_setopt_array($ch,[
//            CURLOPT_URL => 'https://ocr-hhah37ze7a-ey.a.run.app?url='. $image_path,
//            CURLOPT_RETURNTRANSFER => true,
//        ]);
//        $content = curl_exec($ch);
//        curl_close($ch);
//
//        if( empty($content) ){
//            $content = "*******[$image_path]";
//        }
//
//        Redis::hset($REDIS_KEY,$image_path,$content);
//
//        return $content;
    }

    /**
     * Returns statuses of the domain
    */
    protected static function getDomainStatus( array $raw_status ): array
    {
        $domain_status = array_unique(array_filter($raw_status));
        sort($domain_status);
        return array_filter(explode(' ',
            trim(str_replace('  ',' ', mb_ereg_replace('https?:\/\/(www\.)?icann\.org\/epp#[a-zA-Z]+','',implode(' ',$domain_status))))
        ));
    }

    protected static function getRegistrarNameByIANACode( string $iana_id ): ?string
    {
        if( $iana_id === 'not applicable' ){
            return null;
        }

        if( !is_numeric($iana_id) ){
            return null;
        }

        return current(DB::connection()->select("
            SELECT name FROM iana_registry WHERE id=$iana_id
        "))->name ?? null;
    }

    protected static function retrieveInfoFromRawWhoIs( array $info, array $who_is_info, array $map_info_keys ): array
    {
        foreach( $map_info_keys as $map_key => $raw_who_is_keys ){

            if( !empty($info[$map_key]) ){
                // Already set :-)
                continue;
            }

            $info[$map_key] = null;
            foreach( $raw_who_is_keys as $key ){
                if( !empty($who_is_info[$key]) ){
                    $info[$map_key] = trim(is_array($who_is_info[$key]) ? implode(', ',array_unique($who_is_info[$key])) : $who_is_info[$key]);
                    break;
                }
            }
        }

        return $info;
    }

    /** @noinspection SuspiciousAssignmentsInspection */
    protected static function handleRegistrarInfo(array $registrar_info, array $who_is_info ): array {

        $registrar_info = static::retrieveInfoFromRawWhoIs(
            $registrar_info,
            $who_is_info,
            [
                'code' => ['registrar_iana_id','sponsoring_registrar_iana_id'], // $reg_iana_id
                'name' => ['registrar_name','sponsoring_registrar','registrar'], // $reg_name
                'url' => ['registrar_url','registrar-url','referral_url'],    // $reg_web
                'phone' => ['registrar_phone','registrar_abuse_contact_phone'], // $reg_phone
//                'fax' => ['registrar_fax'], // $reg_fax
                'email' => ['registrar_email','registrar_abuse_contact_email'], // $reg_email
                'address' => [], // $reg_address
                'country' => ['registrar_country'], // $reg_country
                'whois_server' => ['registrar_whois_server'],
                'dns_security' => ['dnssec','registrar_dnssec'],
            ]
        );

        if( empty($registrar_info['name']) && !empty($domain['sponsor']) ){
            $registrar_info['name'] = $domain['sponsor'];
        }

        if( !empty($registrar_info['code']) && ($registrar_info['name'] !== 'not applicable') ){
            // Lo recupero dalla tabella IANA
            $registrar_info['name'] = static::getRegistrarNameByIANACode($registrar_info['code']) ?? $registrar_info['name'] ?? null;
        }

        $registrar_info['name'] = static::handleOrganizationInfo(
            $registrar_info,
            $registrar_info['organization'] ?? $who_is_info['registrar_organization'] ?? null
        );

        if( empty($registrar_info['whois_server']) && !empty($registrar_info['servers'][0]['server']) ){
            $registrar_info['whois_server'] = $registrar_info['servers'][0]['server'];
        }

        if( !empty($registrar_info['dns_security'])  ){
            // DNS Security Extensions
            $registrar_info['dns_security'] = match($registrar_info['dns_security']){
                'yes' => true,
                'no','unsigned' => false,
                default => null
            };
        }

        if( !empty($registrar_info['country']) ){
            $registrar_info['country'] = static::countryToISO3($registrar_info['country']);
        }

        unset(
            $registrar_info['servers'],
            $registrar_info['registrar'],
            $registrar_info['sponsor'],
        );

        return $registrar_info;
    }

    protected static function getDNSList( $who_is_info ): array
    {
        if( !empty($who_is_info['nserver']) ){
            $dns_list = $who_is_info['nserver'];
        }elseif( !empty($who_is_info['nameservers']) ){
            $dns_list = $who_is_info['nameservers'];
        }elseif( !empty($who_is_info['name_server']) ){
            $dns_list = $who_is_info['name_server'];
        } else {
            // Unknown format :-D
            return [];
        }

        if( is_string($dns_list) ){
            $dns_list = [$dns_list => $dns_list];
        }elseif( is_array($dns_list) ){
            $dns_list = array_combine($dns_list,$dns_list);
        } else {
            // Unknown format :-D
            return [];
        }

        $list = [];
        foreach( $dns_list as $dns_name => $dns_ip ){
            if( is_numeric($dns_name) || !filter_var($dns_ip,FILTER_VALIDATE_IP) ){
                // In case the list is [0 => "dns_1_name", 1 => "dns_2_name", ... ]
                $dns_name = $dns_ip;
                $dns_ip = gethostbyname($dns_name);
            }
            $list[] = (IPLookup::lookup($dns_ip) ?? []) + ['url' => $dns_name];
        }

        return $list;
    }

    protected static function handleDomainInfo( array $domain_info, array $who_is_info, array $propriety_info=[] ): array
    {
        if( !empty($domain_info['handle']) ){
            $domain_info['code'] = $domain_info['handle'];
        }

        $domain_info = static::retrieveInfoFromRawWhoIs(
            $domain_info,
            $who_is_info,
            [
                'code' => [],
                'ip' => [],
                'name' => ['domain'],
                'is_registered' => [],
//                'dns' => [], // Use static::getDNSList()
                'created_at' => ['creation_date','domain_name_commencement_date','created','registered_on','created_on'],
                'updated_at' => ['updated_date','last_update','last_updated','last-update'],
                'expiration_date' => ['registrar_registration_expiration_date','expires','expire','expires_on','expiry_date','paid-till','free-date','registry_expiry_date'],
            ]
        );

        if( !empty($domain_info['created']) && empty($domain_info['created_at']) ){
            $domain_info['created_at'] = $domain_info['created'];
        }
        if( !empty($domain_info['changed']) && empty($domain_info['updated_at']) ){
            $domain_info['updated_at'] = $domain_info['changed'];
        }
        if( !empty($domain_info['expires']) && empty($domain_info['expiration_date']) ){
            $domain_info['expiration_date'] = $domain_info['expires'];
        }

        if( !empty($domain_info['name']) ){
            $domain_info['ip'] = static::retrieveIPDomain($domain_info['name']);
        }

        $domain_info['dns'] = static::getDNSList($who_is_info);

        // We need it?
        $domain_info['status'] = static::getDomainStatus([
            isset($domain_info['status']) ? implode(' ', (array) $domain_info['status']) : '',
            $propriety_info['status'] ?? '',
            is_array($who_is_info['domain_status'] ?? '') ? implode(' ',$who_is_info['domain_status']) : '',
            is_string($who_is_info['domain_status'] ?? []) ? $who_is_info['domain_status'] : '',
        ]);

        $domain_info['is_registered'] = (!empty($propriety_info['registered']) && $propriety_info['registered'] === 'yes') || !empty($domain_info['status']) || (!empty($domain_info['eppstatus']) && $domain_info['eppstatus']==='active');

        // TODO: check if dates must be formatted?

        return static::cleaningDataStructure($domain_info);
    }

    protected static function handleRegistrantInfo( array $registrant_info, array $who_is_info ): array
    {
        $registrant_info['code'] = $registrant_info['handle'] ?? $who_is_info['registrant_id'] ?? null;

        $registrant_info = static::retrieveInfoFromRawWhoIs(
            $registrant_info,
            $who_is_info,
            [
                'name' => ['registrant_name','registrant','holder_code'], // $rgnt_name
                'address' => ['registrant_street','registrant_address','holder_address'], // $rgnt_street
                'city' => ['registrant_city'],  // $rgnt_city
                'state' => ['registrant_state/province','registrant_state'], // $rgnt_state
                'postal_code' => ['registrant_postal_code'], // $rgnt_postcode
                'country' => ['registrant_country_code','registrant_country','holder_country'], // $rgnt_country
                'phone' => ['registrant_phone','registrant_phone_ext','holder_phone'],
//                'fax' => ['registrant_fax','registrant_fax_ext'],
                'email' => ['registrant_email','holder_e-mail'],
                'site_web' => ['registrant_site'],
                'created_at' => ['registrant_created'],
                'updated_at' => ['registrant_last_update','holder_changed'],
            ]
        );

        $registrant_info['address'] = static::handleAddressInfo($registrant_info);
        $registrant_info['name'] = static::handleOrganizationInfo(
            $registrant_info,
            $registrant_info['organization'] ?? $who_is_info['registrant_organization'] ?? null
        );

        if( !empty($registrant_info['address']) && empty($registrant_info['country']) ){
            $registrant_info['country'] = static::retrieveCountryByAddress($registrant_info);
        }

        if( !empty($registrant_info['country']) ){
            $registrant_info['country'] = static::countryToISO3($registrant_info['country']);
        }

        return static::cleaningDataStructure($registrant_info);
    }

    protected static function handleAdminInfo( array $admin_info, array $who_is_info ): array
    {
        // Collapse address info
        $admin_info = static::retrieveInfoFromRawWhoIs(
            $admin_info,
            $who_is_info,
            [
                'code' => ['registry_admin_id','admin_id','admin_handle','administrative_contact_id','admin_code'],
                'name' => ['admin_name','admin_contact_name','administrative_contact_name','administrative_name'],
                'organization' => ['admin_organization','admin_contact_organization','administrative_contact_organization','administrative_organisation'],
                'phone' => ['admin_phone','admin_phone_number','admin_contact_phone_number','admin_phone_ext','administrative_contact_phone','administrative_contact_phone_number','administrative_phone'],
//                'fax' => ['admin_fax','admin_fax_number','admin_fax_ext','administrative_contact_fax','administrative_contact_facsimile_number','administrative_fax-no'],
                'email' => ['admin_e-mail','admin_email','admin_mail','admin_contact_email','administrative_contact_email','administrative_e-mail'],

                'created_at' => ['admin_created','admin_contact_created'],
                'updated_at' => ['admin_changed','admin_contact_changed'],

                'address' => ['admin_address','admin_contact_address','administrative_contact_address','administrative_contact_address1','administrative_contact_address2','administrative_address'],
                'street' => ['admin_street','admin_contact_street','administrative_contact_street'],
                'city' => ['admin_city','admin_contact_city','administrative_contact_city'],
                'state' => ['admin_state','admin_contact_state','administrative_contact_state','admin_state/province','administrative_contact_state/province'],
                'country' => ['admin_country','admin_country_code','admin_contact_country','admin_contact_country_code','administrative_contact_country','administrative_contact_country_code'],
                'postal_code' => ['admin_postal_code','admin_contact_postal_code','admin_postcode','administrative_contact_postal_code'],
            ]
        );

        $admin_info['code'] = $admin_info['code'] ?? $admin_info['handle'] ?? null;
        $admin_info['name'] = static::handleOrganizationInfo($admin_info);
        $admin_info['address'] = static::handleAddressInfo($admin_info);

        if( !empty($admin_info['address']) && empty($admin_info['country']) ){
            $admin_info['country'] = static::retrieveCountryByAddress($admin_info);
        }

        if( !empty($admin_info['country']) ){
            $admin_info['country'] = static::countryToISO3($admin_info['country']);
        }

        return static::cleaningDataStructure($admin_info);
    }

    protected static function handleTechnicalInfo( array $technical_info, array $who_is_info, array $domain_data ): array
    {
        // Collapse address info
        $technical_info = static::retrieveInfoFromRawWhoIs(
            $technical_info,
            $who_is_info,
            [
                'code' => ['tech_code'],
                'name' => ['tech_name','technical_contacts_name','technical_name','tech_contact'],
                'organization' => ['technical_contacts_organization','technical_organisation'],
                'handle' => ['tech_id','technical_contacts_id'],
                'phone' => ['tech_phone','technical_contacts_phone','technical_contact_phone_number','technical_phone'],
//                'fax' => ['tech_fax','technical_contacts_fax','technical_contact_fax','technical_fax-no'],
                'email' => ['tech_email','technical_contacts_email','technical_contact_email','technical_e-mail','tech_e-mail'],

                'created_at' => ['technical_contacts_created'],
                'updated_at' => ['technical_contacts_last_update'],

                'address' => ['technical_contacts_address','technical_address','tech_address'],
                'street' => ['tech_street'],
                'city' => ['tech_city'],
                'state' => ['tech_state/province','tech_state'],
                'country' => ['tech_country','technical_contact_country_code'],
                'postal_code' => ['tech_postal_code','technical_contact_postal_code'],
            ]
        );

        $technical_info['code'] = $technical_info['handle'] ?? null;
        $technical_info['address'] = static::handleAddressInfo($technical_info);
        $technical_info['name'] = static::handleOrganizationInfo($technical_info);

        if( empty($technical_info['country']) && !empty($domain_data['ip']) ){
            foreach( $domain_data['ip'] as $ip_address ){
                $technical_info['country'] = Helpers::retriveCountryByAddressIP($ip_address);
                if( !empty($technical_info['country']) ){
                    break;
                }
            }
        }

        if( !empty($technical_info['address']) && empty($technical_info['country']) ){
            $technical_info['country'] = static::retrieveCountryByAddress($technical_info);
        }

        if( !empty($technical_info['country']) ){
            $technical_info['country'] = static::countryToISO3($technical_info['country']);
        }

        return static::cleaningDataStructure($technical_info);
    }

    /**
     * From a map of info (retrieved by who is response), returns the address formatted
     * @param array $info
     * @return string|null
    */
    protected static function handleAddressInfo( array $info ): ?string
    {
        if( !empty($info['address']) && is_string($info['address']) ){
            return $info['address'];
        }

        // Sometimes, rows are single-element arrays  :-|
        if( is_array($info['address']) ){
            foreach( $info['address'] as &$row ){
                if( is_array($row) ){
                    $row = implode(' ',$row);
                }
            }
            unset($row);
        }

        $address = implode(', ',array_filter([
            $info['address']['street'] ?? $info['street'] ?? null,
            $info['address']['postcode'] ?? $info['addres']['pcode'] ?? $info['postcode'] ?? $info['postal_code'] ?? null,
            $info['address']['city'] ?? $info['city'] ?? null,
            $info['state'] ?? null,
            $info['address']['country'] ?? $info['country'] ?? null,
        ]));

        return ($address !== '') ? $address : null;
    }

    protected static function handleOrganizationInfo( array $info, ?string $organization=null ): ?string
    {
        if( $organization === null ){
            $organization = $info['organization'] ?? null;
        }

        if( is_array($info['name']) ){
            $info['name'] = implode(', ',$info['name']);
        }

        if( empty($organization) ){
            return $info['name'] ?? null;
        }

        return !empty($info['name']) ? "{$info['name']} ($organization)": $organization;
    }

    protected static function retrieveIPDomain( string $domain ): ?array
    {
        $dns = @dns_get_record($domain,DNS_A);

        if( empty($dns) ){
            return null;
        }

        return array_column($dns,'ip');
    }

    protected static function retrieveCountryByAddress( array $info ): ?string
    {
        preg_match('/, (?<country_code>[a-zA-Z]{2,3})$/',$info['address'],$matches);

        if( empty($matches['country_code']) ){

            // Last change, search by name
            preg_match('/, (?<country_name>[a-zA-Z]+)$/',$info['address'],$matches);

            if( empty($matches['country_name']) ){

                // Last try check if is "United States of America"
                if( str_contains(strtolower($info['address']),'united states of america') ){
                    return 'USA';
                }

                if( str_contains(strtolower($info['name']),'ALIBABA.COM SINGAPORE') ){
                    // Singapore
                    return 'SGP';
                }

                return null;
            }

            return static::countryToISO3($matches['country_name']);
        }

        return static::countryToISO3($matches['country_code']);
    }

    protected static function countryToISO3( string $country_code ): ?string
    {
        $country_code = strtoupper(trim($country_code));

        if( strlen($country_code) === 3 ){
            return $country_code;
        }

        if( strlen($country_code) === 2 ){
            // Old iso code style
            return Helpers::getCountryISO3($country_code);
        }

        // Search code (iso3) by the country's name
        $result = DB::connection()->select("SELECT code FROM countries WHERE upper(name) LIKE '{$country_code}'");

        return !empty($result) ? $result[0]?->code : null;
    }

    /**
     * Remove redundant keys/values
    */
    protected static function cleaningDataStructure( array $info ): array
    {
        unset(
            $info['created'],
            $info['changed'],
            $info['expires'],
            $info['nserver'],
            $info['hold'],
            $info['sponsor'],
            $info['handle'],
            $info['registrant_id'],
            $info['organization'],
            $info['street'],
            $info['city'],
            $info['state'],
            $info['postal_code'],
            $info['anonymous'],
            $info['obsoleted'],
            $info['eligstatus'],
            $info['reachmedia'],
            $info['reachsource'],
            $info['reachstatus'],
            $info['reachdate'],
            $info['type'],
            $info['source'],
            $info['admin-c'],
            $info['tech-c'],
        );

        return $info;
    }

}
