<?php
//Class defined in /manager/include/extenders/geoip
$o_geoip = new CGeoIP;

$o_res = $o_geoip->lookupLocation('91.193.129.35');

echo $o_res->countryCode;
?>