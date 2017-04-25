<?php

/* Sierra API Autorenewal
// PHP Function renewItems($bc) is passed a patron barcode
// It will attempt to renew any items that patron has checked out which
// are due in "$duexdays" (we use 3 days in this example).

// Success and Failure branches are located at the end of the function.
// Our library sends an email notification on success and does nothing on failure
// (the patron will receive a courtesy notice the following day)

// The function is set up to be called repeatedly on a list of barcodes. It could
// be easily modified to take a list of patron record numbers (in the case that
// barcodes are converted to or stored with record numbers when they are initially
// stored), reducing a call to the API on each iteration.

*/


function getPatron($bc){
include "apiconstants.php";

// Address for token request
$tokenurl = $apiurl."token";

$postBody="grant_type=client_credentials";

$ch = curl_init($tokenurl);
curl_setopt_array($ch,array(
		CURLOPT_POST => TRUE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HTTPHEADER => array(
				'Host: '.$hosturl,
				'Authorization: Basic '.$encauth,
				'Content-Type: application/x-www-form-urlencoded'
		),
		CURLOPT_POSTFIELDS => $postBody
));

$response = curl_exec($ch);

if($response === FALSE){
	echo curl_error($ch);
	return false;
}

$tokenData = json_decode($response, true);
if(is_null($tokenData)){
	echo "Could not retrieve token from server.<br>";
	return false;
}
$token = $tokenData["access_token"];
//echo "Token: {$token}<br>";

// Get Patron ID from API -- this can be stored so that it doesn't need to be called each time
$barcode = $bc;
$getPatronURL = $apiurl."patrons/find?barcode=".$barcode."&fields=default%2Cnames%2Cemails";

$ch = curl_init($getPatronURL);
curl_setopt_array($ch,array(
		CURLOPT_HTTPGET => TRUE,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_HTTPHEADER => array(
				'Host: '.$hosturl,
				'Authorization: Bearer '.$token,
				'User-Agent: '.$appname,
				'X-Forwarded-For: '.$webserver
		)
));

$response = curl_exec($ch);

if($response === FALSE){
	echo curl_error($ch);
	return false;
}

$patronData = json_decode($response, true);

return $patronData;
// End of patron id call

}
?>

