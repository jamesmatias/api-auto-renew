<?php
/* Sierra API Autorenewal
// Created by James Matias at Middle Country Public Library
// Revised March 23, 2016

// PHP Function renewItems($bc) is passed a patron barcode
// It will attempt to renew any items that the patron has checked out which
// are due in "$duexdays" (we use 3 days in this example).

// Success and Failure branches are located at the end of the function.
// Our library sends an email notification on success and does nothing on failure
// (the patron will receive a courtesy notice the following day)

// The function is set up to be called repeatedly on a list of barcodes. It could
// be easily modified to take a list of patron record numbers (in the case that
// barcodes are converted to or stored with record numbers when they are initially 
// stored), reducing a call to the API on each iteration. 

// To use: place code on a server with PHP5 and cURL installed.
// Change $hosturl variable to your Sierra application server
// Change $webserver to your webserver
// Change $auth to your Client Key and secret in clientkey:secret format.

// Remove or comment the echo statements if you don't need them.

*/

// Sample barcode on which to renew items
$sample = "123456789012";

// Function call to renew items for this barcode
renewItems($sample);


function renewItems($bc){
// System Specific variables
$auth = "clientkey:secret"; // **Authentication client key and secret as "key:secret"
$hosturl = "sandbox.iii.com"; // **Sierra application server
$webserver = "192.168.0.10"; // **Webserver hosting this PHP App
$duexdays = '3 days'; // **Days before due date to try renewing
$appname = "RenewalApp"; // App name

$apiurl = "https://".$hosturl."/iii/sierra-api/v2/"; // Sierra API address

// Authorization section
$encauth = base64_encode($auth);

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
$getPatronURL = $apiurl."patrons/find?barcode=".$barcode;

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
if(is_null($patronData)){
	echo "Patron response is null.<br>";
	return false;
}
$pid = $patronData["id"];
echo "Patron ID: {$pid}<br>";
// End of patron id call

// Get Checkouts from API
$getCheckoutsURL = $apiurl."patrons/{$pid}/checkouts";

$ch = curl_init($getCheckoutsURL);
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

$coData = json_decode($response, true);
if(is_null($coData)){
	echo "Check out response is null.<br>";
	return false;
}
// Parse Checkout entries
echo "Number of Checkouts: ".$coData["total"]."<br><br>";
$numCheckouts = (int)$coData["total"];
$entries = (array)$coData["entries"];

// Iterate and parse checkouts and attempt renewals
$success = 0;
$failed = 0;
for($i=0;$i<$numCheckouts;$i++)
{
	$temp = (array)$entries[$i];	
	echo "ID {$i}: ".$temp["id"]."<br>";
	
	// Attempt renewal for this checkout
	$dueDate = $temp["dueDate"];
	$dueDT = date_create($dueDate);
	$rnwDate = date_create($dueDate);
	$rnwDate = date_sub($rnwDate, date_interval_create_from_date_string($duexdays));
	
	// Echoes for debugging
	echo "Due Date: ".date_format($dueDT, 'Y-m-d')."<br>";
	echo "Renew on: ".date_format($rnwDate, 'Y-m-d')."<br>";
	echo "Today is: ".date('Y-m-d')."<br>";
	
	// Only try to renew it exactly $duexdays ahead of time,
	// otherwise skip it and move to the next one.
	if(date('Y-m-d') == date_format($rnwDate,'Y-m-d'))
	{
		// API returns a URL as the ID, append /renewal
		$renewCOURL = $temp["id"]."/renewal";
		
		$ch = curl_init($renewCOURL);
		curl_setopt_array($ch,array(
				CURLOPT_POST => TRUE,
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
		
		$renewData = json_decode($response, true);
		if(is_null($renewData)){
				echo "Renewal response is null.<br>";
				return false;
		}
		
		// Parse response - echoed for debugging
		if(curl_getinfo($ch,CURLINFO_HTTP_CODE) == 200){
			// Success Response -- Replace with notification, if needed.
			echo "Success! New Due Date is: ".$renewData["dueDate"]."<br>";
			$success = $success + 1;
		}
		else {
			// Failure Response -- Replace with notification, if needed.
			echo "Code: ".$renewData["code"].".".$renewData["specificCode"];
			echo "<br>HTTP Status: ".$renewData["httpStatus"];
			echo "<br>Description: ".$renewData["description"]."<br>";
			$failed = $failed + 1;
		}
		echo "<br>";
	}
}
// Print results for this patron to the screen (for debugging)
echo "{$success} items renewed, {$failed} items unable to be renewed for {$pid}<br><br><hr><br>";
}
?>

