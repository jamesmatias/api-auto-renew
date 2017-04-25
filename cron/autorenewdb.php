<?php

/* Sierra API Autorenewal
// PHP Function renewItems() is passed a patron record number, token, timestamp, and email
// It will attempt to renew any items that patron has checked out which
// are due in "$duexdays" (we use 3 days in this example).

// Success and Failure branches are located at the end of the function.
// Our library sends an email notification on success and does nothing on failure
// (the patron will receive a courtesy notice the following day)

// Update 2017-04-18: Changed token mechanism to Utilize the "Info" endpoint.
//					  No longer relying on API's "total" entry to traverse array of checkouts.


*/

include "connect.php";

// Create an API token and gets its timestamp
$token_stamp = 0;
$token = getToken(null, $token_stamp);

// Retrieve pid from database and call renewItems() function
$query = "SELECT * FROM autorenew.patrons WHERE isActive > 0";
$result = $mysqli->query($query);

while($row = $result->fetch_assoc())
{
	renewItems($row['recordnum'], $token, $token_stamp, $row['email']);
}

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
//	renewItems($pid, &$token, &$tstamp, $email)
//  Input: Int $pid- patron record number, String $token (can be null), 
//		DateTime $tstamp: timestamp of token (cannot be null). $email- current patrons emails
//		$token and $tstamp are passed by reference and will be changed if needed
//	Output: returns false on failure, otherwise returns true
//
//	Attempts to renew any items that are due in $duexdays
*/
function renewItems($pid, &$token, &$tstamp, $email){
	include "apiconstants.php";

	// Check the life of the token and renew if necessary.
	$token = getToken($token, $tstamp);

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
		echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
		return false;
	}

	$coData = json_decode($response, true);
	if(is_null($coData)){
		echo date("Y-m-d H:i:s")." Checkout response is null.\n";
		return false;
	}
	
	// Parse Checkout entries
	$numCheckouts = (int)$coData["total"];
	$entries = (array)$coData["entries"];

	// Iterate and parse checkouts and attempt renewals
	$success = 0;
	$failed = 0;
	
	// Text for items renewed and items that failed to renew
	$msghtml = "";
	$failhtml = "";
	$msgtxt = "";
	$failtxt = "";
	
	
	foreach($entries as $temp)
	{
		// reset variables
		$dueDate = null;
		$dueDT = null;
		$rnwDate = null;
		$renewCOURL = null;
		$ch = null;
		$response = null;
		$renewData = null;
		$title = null;
		$newDue = null;
		$derror = null;
				
		// Attempt renewal for this checkout, find due date and renewal date
		$dueDate = $temp["dueDate"];
		$dueDT = date_create($dueDate);
		$rnwDate = date_create($dueDate);
		$rnwDate = date_sub($rnwDate, date_interval_create_from_date_string($duexdays));
		
		// Only try to renew it exactly $duexdays ahead of time,
		// otherwise skip it and move to the next one.
		if(date('Y-m-d') == date_format($rnwDate,'Y-m-d'))
		{
			// Check the life of the token and renew if necessary.
			$token = getToken($token, $tstamp);

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
			echo date("Y-m-d H:i:s")." ".curl_error($ch)."\n";
			return false;
			}
			
			$renewData = json_decode($response, true);
			if(is_null($renewData)){
					echo date("Y-m-d H:i:s")." Renewal response is null.\n";
					return false;
			}
			
			// Parse response - echoed for debugging
			if(curl_getinfo($ch,CURLINFO_HTTP_CODE) == 200){
				
				
				// Query API for title to include in notification
				$title = getTitle($temp["item"], $token, $tstamp);
				$newDue = $renewData["dueDate"];
				
				if(strcmp($newDue,$dueDate)== 0)
				{
					// Due date didn't change, switch to failure (copied from below)
					// Failure Response -- Replace with notification, if needed.
					echo date("Y-m-d H:i:s")." Code: ".$renewData["code"].".".$renewData["specificCode"]."\n";
					echo date("Y-m-d H:i:s")." HTTP Status: ".$renewData["httpStatus"]."\n";
					echo date("Y-m-d H:i:s")." Description: ".$renewData["description"]."\n";
					echo "Renewal URL: ".$renewCOURL."\n";
					echo "Server Response: ".$response."\n";
					echo "JSON Response: \n";
					echo var_dump($renewData)."\n";
					$failed = $failed + 1;
					
					// Query API for title to include in notification
					$title = getTitle($temp["item"], $token, $tstamp);	

					// Update notification message body
					$failhtml = $failhtml."Title: {$title}<br>Due Date: {$dueDate}<br>Reason: Unknown error - please try renewing online at <a href=\"http://mcpac.mcpl.lib.ny.us\">http://mcpac.mcpl.lib.ny.us</a> or by calling the library.<br><br>";
					$failtxt = $failtxt."Title: {$title}\nDue Date: {$dueDate}\nReason: Unknown error - please try renewing online at http://mcpac.mcpl.lib.ny.us or by calling the library.\n\n";
				}
				else
				{
					// Success Response				
					$success = $success + 1;
					
					// Update notification message body
					$msghtml = $msghtml."Title: {$title}<br>New Due Date: {$newDue}<br><br>";
					$msgtxt = $msgtxt."Title: {$title}\nNew Due Date: {$newDue}\n\n";
				}
			}
			else {
				// Failure Response -- Replace with notification, if needed.
				echo date("Y-m-d H:i:s")." Code: ".$renewData["code"].".".$renewData["specificCode"]."\n";
				echo date("Y-m-d H:i:s")." HTTP Status: ".$renewData["httpStatus"]."\n";
				echo date("Y-m-d H:i:s")." Description: ".$renewData["description"]."\n";
				echo "Renewal URL: ".$renewCOURL."\n";
				echo "Server Response: ".$response."\n";
				echo "JSON Response: \n";
				echo var_dump($renewData)."\n";
				$failed = $failed + 1;
				
				// Query API for title to include in notification
				$title = getTitle($temp["item"], $token, $tstamp);	

				// Get the error returned by the API
				// Remove portion before :, ex. Webpac Error : TOO MANY RENEWALS
				$derror = $renewData["description"];
				$derror = trim(substr($derror, strpos($derror, ":")+1));
				// Fix case
				$derror = ucfirst(strtolower($derror));
				
				// Update notification message body
				$failhtml = $failhtml."Title: {$title}<br>Due Date: {$dueDate}<br>Reason: {$derror}<br><br>";
				$failtxt = $failtxt."Title: {$title}\nDue Date: {$dueDate}\nReason: {$derror}\n\n";
			}			
		} // end of renewal date check
	} // end of for loop
	
	// Print results for this patron to the screen or log (for debugging)	
	echo date("Y-m-d H:i:s")." {$success} items renewed, {$failed} items unable to be renewed for {$pid}\n\n";
	
	// Call function to send an email notification
	sendNotification($email, $msghtml, $failhtml, $msgtxt, $failtxt, $success, $failed);
	return true;
}

/*
//	getTitle($item, &$token, &$tstamp)
//  Input: String Item record# API URL, String $token (can be null), 
//		DateTime $tstamp: timestamp of token (cannot be null). 
//		$token and $tstamp are passed by reference and will be changed if needed
//	Output: returns false on failure, otherwise returns title of item from bib
//
*/
function getTitle($item, &$token, &$tstamp)
{
	include "apiconstants.php";
	
	// Check the life of the token and renew if necessary.
	$token = getToken($token, $tstamp);
	
	// Get Bib ID using Item API
	$ch = curl_init($item);
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
				
	$itemData = json_decode($response, true);
	if(is_null($itemData)){
		echo date("Y-m-d H:i:s")." Item response is null.\n";
		echo date("Y-m-d H:i:s")." ".$response."\n";
		return false;
	}
	
	// Get Title using Bib API, checks first bib only (if multiple are listed)
	$bibIDs = (array)$itemData["bibIds"];
	$bibURL = $apiurl."bibs/".$bibIDs[0];
		
	$ch = curl_init($bibURL);
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
				
	$bibData = json_decode($response, true);
	if(is_null($bibData)){
		echo date("Y-m-d H:i:s")." Bib response is null.\n";
		echo date("Y-m-d H:i:s")." ".$response."\n";
		return false;
	}
	
	// Return title from bib
	return $bibData["title"];
}

/*
//	sendNotification($email, $itemsmsghtml, $failhtml, $itemsmsgtxt, $failtxt, $success, $failed)
//  Input: $email - comma separated list of emails to send notification
//			$itemsmsghtml, $itemsmsgtxt - list of items that were renewed in html and txt format
//			$failhtml, $failtxt - list of items that were not renewed in html and txt format
//			$success, $failed - Number of successful renewals, number of failed renewals
//	Output: No return value
//
*/
function sendNotification($email, $itemsmsghtml, $failhtml, $itemsmsgtxt, $failtxt, $success, $failed)
{
	//Email results
	if (($success > 0 || $failed > 0) && !is_null($email))
	{
		require_once 'Mail.php';
		require_once 'Mail/mime.php';
		
		include 'mailvars.php';
		include 'apiconstants.php';
		
		// create email headers
		$xheaders = array('From' => $from,
				'Reply-To' => $replyto,
				'To' => $email,
				'Subject' => $subject);
		$mime = new Mail_mime();
		
		
		if ($success == 0)
		{
			$itemsmsghtml = "No items due in ".$duexdays." were renewed.<br><br>";
			$itemsmsgtxt = "No items due in ".$duexdays." were renewed.<br><br>";
		}
		
		if ($failed == 0)
		{
			$failhtml = "No items due in ".$duexdays." were unable to be renewed.<br><br>";
			$failtxt = "No items due in ".$duexdays." were unable to be renewed.<br><br>";
		}
		
		$html = $htmlmsgtop.$itemsmsghtml."<br><br>".$htmlmsgmid.$failhtml."<br><br>".$htmlmsgbot;				
		$text = $txtmsgtop.$itemsmsgtxt."\n\n".$txtmsgmid.$failtxt."\n\n".$txtmsgbot;
				
		
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
	}
}
?>

