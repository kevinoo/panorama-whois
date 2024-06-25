<?php

namespace kevinoo\PanoramaWhois;

use kevinoo\PanoramaWhois\Models\IanaAddressBlocks;


/**
    return [
        'code' => null,
        'name' => null,
        'address' =>  null,
        'country' => null,
        'link' => null,
        'contact' => null,
        'phone' => null,
        'fax' => null,
        'email' => null,
        'abuseemail' => null,
    ];
*/
class IPLookup
{
    public static function lookup( string $ip_address ): ?array
    {
        if( empty($ip_address) || !filter_var($ip_address,FILTER_VALIDATE_IP) ){
            return null;
        }

        $split = explode('.',$ip_address);

        $whois_server = IanaAddressBlocks::where('prefix',$split[0])->first()?->whois;

        $response = static::queryWhoisServer($whois_server,$ip_address);

        if( !empty($response['data_remarks']) && empty($response['admin_abuse-mailbox']) ){

            if( is_array($response['data_remarks']) ){
                $response['data_remarks'] = implode(' ',$response['data_remarks']);
            }

            preg_match('/(?<abuse_email>[\w.-]+@([\w-]+\.)+[\w-]{2,4})/', $response['data_remarks'], $matches );
            if( !empty($matches['abuse_email']) ){
                $response['admin_abuse-mailbox'] = $matches['abuse_email'];
            }
        }

        $data = static::retrieveInfoFromRawWhoIs( $response, [
            'code' => ['admin_orgid','abuse_orgabusehandle','data_admin-c','data_ownerid'],
            'name' => ['data_netname','data_owner'],
            'address' => ['admin_address','person_address'],
            'country' => ['data_country'],
//            'link' => [''],
//            'contact' => [''],
            'phone' => ['admin_phone','abuse_orgabusephone','tech_orgtechphone','noc_orgnocphone','person_phone'],
//            'fax' => ['admin_fax-no','person_fax-no'],
            'email' => ['abuse_orgabuseemail','admin_abuse-mailbox'],
            'abuse_email' => ['abuse_orgabuseemail','tech_orgtechemail','noc_orgnocemail','admin_abuse-mailbox','person_e-mail'],
            'created_at' => ['data_regdate','data_created','person_created'],
            'updated_at' => ['data_updated','data_last-modified','person_last-modified'],
        ]);

        if( empty(array_filter($data)) ){
            // ARIN - The response is:
//            Alibaba.com LLC AL-3 (NET-47-88-0-0-1) 47.88.0.0 - 47.91.255.255
//            ALICLOUD-US ALICLOUD-US (NET-47-88-0-0-2) 47.88.0.0 - 47.88.127.255
            $data['raw'] = $response;
        }

        if( empty($data['country']) ){
            $data['country'] = Helpers::retriveCountryByAddressIP($ip_address);
        }

        return ['ip' => $ip_address, 'whois_server' => $whois_server] + $data;
    }

    /**
     * Copied as is ¯\_(ツ)_/¯
     */
    protected static function queryWhoisServer($whois_server, $domain): array
    {
        $socket = @fsockopen($whois_server, 43, $errno, $err_str, 10);

        if( empty($socket) ){
            // or die('Socket Error '. $errno .' - '. $err_str);
            return [];
        }

        fwrite($socket, $domain . "\r\n");
        $out = "";
        while(!feof($socket)){
            $out .= @fgets($socket);
        }
        fclose($socket);

        $result = [];
        if( !str_contains(strtolower($out),'error') && !str_contains(strtolower($out), 'not allocated') ) {

            $section = 'data';
            $rows = explode("\n", $out);
            foreach( $rows as $row ){
                $row = trim($row);

                if( empty($row) || str_starts_with($row,'#') || str_starts_with($row,'%') ){
                    continue;
                }

                if( str_contains($row,':') ){
                    [$key,$value] = @explode(':', $row, 2);
                    $key = strtolower(trim($key));
                    $value = trim($value);

                    if( str_starts_with($value,'*') ){
                        continue;
                    }

                    if( in_array($key,['role','orgname','orgnochandle','orgabusehandle','orgtechhandle']) ){
                        // This is the master info to attribute to the ADMIN person
                        $section = match($key){
                            'orgnochandle' => 'noc',
                            'orgabusehandle' => 'abuse',
                            'orgtechhandle' => 'tech',
                            'role','orgname' => 'admin'
                        };
                    }elseif( in_array($key,['person','irt','route']) ){
                        $section = $key;
                    }

                    $info_key = strtolower(str_replace(' ','_',$section .'_'. $key ));

                    if( isset($result[$info_key]) ){
                        if( !is_array($result[$info_key]) ){
                            $result[$info_key] = [$result[$info_key]];
                        }
                        $result[$info_key][] = $value;
                    } else {
                        $result[$info_key] = $value;
                    }

                } else {
                    $result[] = $row;
                }
            }
        }

        return $result;
    }

    protected static function retrieveInfoFromRawWhoIs( array $who_is_info, array $map_info_keys ): array
    {
        $info = [];
        foreach( $map_info_keys as $map_key => $raw_who_is_keys ){
            $info[$map_key] = null;
            foreach( $raw_who_is_keys as $key ){
                if( !empty($who_is_info[$key]) ){
                    $info[$map_key] = trim(is_array($who_is_info[$key]) ? implode(', ',$who_is_info[$key]) : $who_is_info[$key]);
                    break;
                }
            }
        }
        return $info;
    }

}
