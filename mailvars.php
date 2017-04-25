<?php 
$host="ssl://smtp.gmail.com"; // Update
$port="465"; // Update
$username="ouraddress@gmail.com"; // Update
$password="ourpassword"; // Update
$timezone = 'America/New_York'; // Update
$from = 'Our Library Notices <ouraddress@gmail.com>'; // Update
$replyto = 'webmaster@mylibrary.org'; // Update
$toemail = $email;
$subject = 'Autorenewal System Notice'; // Update


// Update the messages below with what you'd like to appear in the patron email message.

$htmlmsgtop = "<h2>[Our] Library Autorenewal System Notice</h2><br><p>
			The following item(s) were successfully renewed: <br><br>";
$htmlmsgmid = "The following item(s) are due soon but were unable to be renewed:<br><br>";
$htmlmsgbot = "Please check your library record at <a href=\"https://sierra.mylibrary.org/patroninfo\">https://sierra.mylibrary.org/patroninfo</a> for more information.<br></p>";

$txtmsgtop = "[Our] Library Autorenewal System Notice\n\n
			The following item(s) were successfully renewed: \n\n";
$txtmsgmid = "The following item(s) are due soon but were unable to be renewed:\n\n";
$txtmsgbot = "Please check your library record at https://sierra.mylibrary.org/patroninfo for more information.\n";
?>