<?php
$json = file_get_contents("https://api.greynoise.io/datashots/ukraine/manifest.json");
include_once "whois.php";

$ipsets = json_decode($json, true);

function addInfo($ip, $country, $netname, $abusePhone, $abuseMail, $address, $orgPhone){
    file_put_contents("ip_country_info.csv","{$ip},{$country},{$netname},{$abusePhone},{$abuseMail},{$address},{$orgPhone}\r\n", FILE_APPEND);
    echo "{$ip},{$country},{$netname},{$abusePhone},{$abuseMail},{$address},{$orgPhone}\r\n";
}

function ipExists($ip)
{
    $fp = fopen("ip_country_info.csv", "r");
    if($fp){
        while($line = fgetcsv($fp,"512")){
            if($ip == $line[0]){
                fclose($fp);
                return true;
            }
        }

        fclose($fp);
    }
    return false;
}

foreach($ipsets["files"] as $ip_set){

    $list = file($ip_set["url"]);

    $csv = array_map('str_getcsv', $list);

    for($i = 0; $i<count($csv); $i+=2){
        $record = $csv[$i];
        if(isset($record[1]) && ValidateIP($record[1])){
            try
            {
                if(!ipExists($record[1])) {

                    $info = LookupIP($record[1]);
                    $country = getCountry($info);
                    if (trim($country[0]) !== 'RU') {

                        $orgPhone = getOrgPhone($info);
                        $orgAddress = getAddressInfo($info);

                        $abuseInfo = getAbuseInfo($info);
                        $netName = getNetName($info);

                        addInfo(
                            $record[1],
                            $country,
                            $netName,
                            $abuseInfo["AbusePhone"],
                            $abuseInfo["AbuseMail"],
                            $orgAddress,
                            $orgPhone
                        );
                    }
                    sleep(rand(1,4));
                }
            }
            catch(\Exception $err)
            {
                //
            }
        }
    }
}
function getOrgPhone($info)
{
    if(preg_match_all("#phone:.*?(.*)#", $info, $matches)){
        return join("|", array_map("trim", $matches[1]));
    }
    return "";
}

function getNetName($info)
{
    if(preg_match_all("#org-name:.*?(.*)#", $info, $matches)){
        return join("|", array_map("trim", $matches[1]));
    }
    return "";
}
function getAddressInfo($info)
{
    if(preg_match_all("#address:.*?(.*)#", $info, $matches)){
        return join("|", array_map("trim", $matches[1]));
    }
    return "";

}
function getCountry($info)
{
    if(preg_match_all("#country:.*?([A-Z][A-Z])#", $info, $matches)){
        return join("|", array_map("trim", $matches[1]));
    }
    return "";
}
function getAbuseInfo($info)
{
    $result = [
        "AbusePhone" => "n/a",
        "OrgAbuseEmail" => "n/a"
    ];
    if(preg_match_all("#OrgAbusePhone:.*?(.*)#", $info, $matches)){
        $result["AbusePhone"] = current($matches[1]);
    }
    if(preg_match_all("#OrgAbuseEmail:.*?(.*)#", $info, $matches)){
        $result["AbuseMail"] = current($matches[1]);
    }
    return $result;
}
