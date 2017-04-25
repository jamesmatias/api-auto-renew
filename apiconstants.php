<?php
// System Specific variables
$hosturl = "sierra.mylibrary.org"; // Sierra application server
$apiurl = "https://".$hosturl."/iii/sierra-api/v3/"; // Sierra API address
$token_expire_interval = 300; // number of seconds before a token expires to request a new one
$webserver = "192.168.0.100"; // Webserver hosting this PHP App
$appname = "RenewalApp"; // App name
$duexdays = '3 days'; // Days before due to attempt renewal

// Authorization section
$auth = "somelongkey:secret"; // Authentication client key and secret as "key:secret" from Sierra Admin App
$encauth = base64_encode($auth);
?>
