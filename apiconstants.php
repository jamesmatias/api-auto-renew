<?php
// System Specific variables
$hosturl = "sandbox.iii.com"; // Sierra application server
$apiurl = "https://".$hosturl."/iii/sierra-api/v2/"; // Sierra API address
$webserver = "192.168.0.10"; // Webserver hosting this PHP App
$appname = "RenewalApp"; // App name
$duexdays = '3 days';

// Authorization section
$auth = "clientkey:secret"; // Authentication client key and secret as "key:secret"
$encauth = base64_encode($auth);
?>