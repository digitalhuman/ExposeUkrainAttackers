<?php
$json = file_get_contents("https://api.greynoise.io/datashots/ukraine/manifest.json");
include_once "whois.php";

$ipsets = json_decode($json, true);

function addInfo($ip, $country, $netname, $abusePhone, $abuseMail){
    file_put_contents("ip_country_info.csv","{$ip},{$country},{$netname},{$abusePhone},{$abuseMail}\r\n", FILE_APPEND);

    echo "{$ip},{$country},{$netname},{$abusePhone},{$abuseMail}\r\n";
}

foreach($ipsets["files"] as $ip_set){

    $list = file($ip_set["url"]);

    $csv = array_map('str_getcsv', $list);

    for($i = 0; $i<count($csv); $i+=2){
        $record = $csv[$i];
        if(isset($record[1]) && ValidateIP($record[1])){
            $info = LookupIP($record[1]);
            $country = getCountry($info);
            if(trim($country) !== 'RU') {
                $abuseInfo = getAbuseInfo($info);
                $netName = getNetName($info);
                if (!empty($abuseInfo)) {
                    addInfo($record[1], $country[0], $netName[0], $abuseInfo["AbusePhone"][0], $abuseInfo["AbuseMail"][0]);
                } else {
                    addInfo($record[1], $country[0], $netName[0], "n/a", "n/a");
                }
            }
        }
    }
}
function getNetName($info)
{
    if(preg_match_all("#netname:.*?(.*)#", $info, $matches)){
        return $matches[1];
    }
    return "";
}
function getCountry($info)
{
    if(preg_match_all("#country:.*?([A-Z][A-Z])#", $info, $matches)){
        return $matches[1];
    }
    return "";
}
function getAbuseInfo($info)
{
    $result = [];
    if(preg_match_all("#OrgAbusePhone:.*?(.*)#", $info, $matches)){
        $result["AbusePhone"] = $matches[1];
    }
    if(preg_match_all("#OrgAbuseEmail:.*?(.*)#", $info, $matches)){
        $result["AbuseMail"] = $matches[1];
    }
    return $result;
}
