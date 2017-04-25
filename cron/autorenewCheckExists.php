<?php

/* Sierra API Autorenewal
// PHP Function checkExists() is passed a patron record number, token, timestamp, and email
// It will check to see if each patron record exists in Sierra. If a record no longer exists, 
// it marks it as inactive in the database. Otherwise it does nothing. It sends an email with
// a list of changes, as well as totals for active and inactive patrons, to the specified address.

*/

include "connect.php";

// Create an API token and gets its timestamp
$token_stamp = new DateTime();
$token = getToken(null, $token_stamp);

// Retrieve pid from database and call renewItems() function
$query = "SELECT * FROM autorenew.patrons WHERE isActive > 0";
$result = $mysqli->query($query);

$removed = "";
$removedh = "<table><tr><th>Patron#</th><th>Patron Name</th><th>Patron Email</th></tr>";
$active = 0;
$inactive = 0;
while($row = $result->fetch_assoc())
{
	if (checkExists($row['recordnum'], $token, $token_stamp, $row['email']))
	{
		$active++;
	}
	else
	{
		$inactive++;
		$removed = "{$removed} Patron#: {$row['recordnum']}\tPatron Name: {$row['name']}\tPatron Email: {$row['email']}\n";
		$removedh = "{$removedh}<tr><td>{$row['recordnum']}</td><td>{$row['name']}</td><td>{$row['email']}</td></tr>";
	}
}
	$removeh = $removedh."</table>";
	
	$email = "webmaster@mylibrary.org"; // Comma separated list of emails to notify
	
	require_once 'Mail.php';
		require_once 'Mail/mime.php';
		
		include 'mailvars.php';
		include 'apiconstants.php';
		
		// create email headers
		$xheaders = array('From' => $from,
				'Reply-To' => $replyto,
				'To' => $email,
				'Subject' => "Autorenewal: Deleted Patron Removal");
		$mime = new Mail_mime();
				
		$html = "Removed the following patrons:\n\n<br><br>".$removedh;				
		$text = "Removed the following patrons:\n\n".$removed;
				
		$mime->setTXTBody($text);
		$mime->setHTMLBody($html);
		
		$now = new DateTime('NOW', new DateTimeZone($timezone));
		
		$message = $mime->get();
		$headers = $mime->headers($xheaders);
		
		$smtp = Mail::factory('smtp',
				array('host' => $host,
						'port' => $port,
						'auth' => true,
						'username' => $username,
						'password' => $password));
		
		$mail = $smtp->send($email, $headers, $message);
		
		if(PEAR::isError($mail)) {
			$errdata = $mail->getMessage();
			echo date("Y-m-d H:i:s")." ".$errdata."\n";
		}
		else {
			echo date("Y-m-d H:i:s")." Successfully sent notification to {$email}\n";
		}

echo date("Y-m-d H:i:s")." Active patrons: {$active}  Removed patrons: {$inactive}";

/*
//	getToken($token, &$tstamp)
//  Input: String $token (can be null), DateTime $tstamp: timestamp of token (cannot be null)
//			$tstamp is passed by reference and will be changed if a new token is created.
//	Output: If $token is still valid (within $token_expire_interval seconds of expiration), returns existing $token. 
//		Else returns new $token and updates $tstamp to current time
*/
function getToken($token, &$tstamp){
	include "apiconstants.php";
	
	// If token is expired, get new token
	if ($tstamp <= (time() - $token_expire_interval) || is_null($token))
	{
		echo date("Y-m-d H:i:s")." Token expired or null. Requesting new token.\n";
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
			echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
			return false;
		}

		$tokenData = json_decode($response, true);
		if(is_null($tokenData)){
			echo date("Y-m-d H:i:s")." Could not retrieve token from server.\n";
			return false;
		}
		$token = $tokenData["access_token"];
		
		// Get time remaining for token and update tstamp
		$tokenurl = $apiurl."info/token";
		$ch = curl_init($tokenurl);
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
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
	}

	$tiData = json_decode($response, true);
	if(is_null($tiData)){
		echo date("Y-m-d H:i:s")." Token info response is null.\n";
		return false;
	}
	$tstamp = $tiData["expiresIn"] + time();
	
	
	return $token;
		
	}
	else // do nothing, return original token
		return $token;
	
}

/*
//	checkExists($pid, &$token, &$tstamp, $email)
//  Input: Int $pid- patron record number, String $token (can be null), 
//		DateTime $tstamp: timestamp of token (cannot be null). $email- current patrons emails
//		$token and $tstamp are passed by reference and will be changed if needed
//	Output: returns false (and sets activity to -1) if patron record not found, otherwise returns true
//
//	Attempts to renew any items that are due in $duexdays
*/
function checkExists($pid, &$token, &$tstamp, $email){
	include "apiconstants.php";

	// Check the life of the token and renew if necessary.
	$token = getToken($token, $tstamp);

	// Get Checkouts from API
	$getPatronURL = $apiurl."patrons/{$pid}";

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
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
	}

	$data = json_decode($response, true);
	if(is_null($data)){
		echo date("Y-m-d H:i:s")." Response is null.\n";
		return false;
	}
	
	// Parse response - echoed for debugging
	if(curl_getinfo($ch,CURLINFO_HTTP_CODE) == 404){
		// Set isActive to -1
		include "connect.php";

		// Set isActive to -1 for current patron id
		$query = "UPDATE autorenew.patrons SET isActive = -1 WHERE recordnum={$pid} > 0";
		$query = $mysqli->real_escape_string($query);
		$result = $mysqli->query($query);
		return false;
	}
	else
	{
		return true;
	}
}
?>

