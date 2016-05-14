<?php 
// Email server settings for notifications
$host="ssl://smtp.gmail.com";
$port="465";
$username="mylibrary@gmail.com";
$password="mypassword";
$from = 'My Library <mylibrary@gmail.com>';
$replyto = 'info@mylibrary.org';
$subject = 'Autorenewal System Notice';

// Change the variables below to change the look of your notification emails
// This HTML appears before the list of items that were renewed.
$htmlmsgtop = "<h2>Public Library Autorenewal System Notice</h2><br><p>
			The following items were successfully renewed: <br><br>";
			
// This HTML appears after the list of items that were renewed, but before the list of items that failed.
// It only appears if items failed.
$htmlmsgmid = "The following items are due soon but were unable to be renewed:<br><br>";

// This HTML appears at the end of the email.
$htmlmsgbot = "Please check your library record at <a href=\"https://sandbox.iii.com/patroninfo\">https://sandbox.iii.com/patroninfo</a> for more information.<br></p>";

// This HTML appears before the list of items that were renewed.
$txtmsgtop = "Public Library Autorenewal System Notice\n\n
			The following items were successfully renewed: \n\n";
			
// This HTML appears after the list of items that were renewed, but before the list of items that failed.
// It only appears if items failed.
$txtmsgmid = "The following items are due soon but were unable to be renewed:\n\n";

// This HTML appears at the end of the email.
$txtmsgbot = "Please check your library record at https://sandbox.iii.com/patroninfo for more information.\n";
?>