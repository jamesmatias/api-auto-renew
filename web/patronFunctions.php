<?php
/* Sierra API Autorenewal
// PHP Function getPatron($bc) is passed a patron barcode
// It will return the Sierra API response array decoded from JSON format
*/

/*
//	getPatron($bc)
//  Input: String $bc - patron barcode
//	Output: array containing the decoded JSON response from the Sierra API
//			Includes default fields and emails
*/
function getPatron($bc){
include "apiconstants.php";

// Request auth token
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

// Request patron information from API using barcode
$getPatronURL = $apiurl."patrons/find?barcode=".$bc."&fields=default%2Cemails";

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

// return the response from the API to be used in calling function
return $patronData;
}
?>

