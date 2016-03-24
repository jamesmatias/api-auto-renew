# api-auto-renew
Sierra API Autorenewal

Created by James Matias at Middle Country Public Library

Revised March 23, 2016

PHP Function renewItems($bc) is passed a patron barcode

It will attempt to renew any items that the patron has checked out which
are due in "$duexdays" (we use 3 days in this example).

Success and Failure branches are located at the end of the function.
Our library sends an email notification on success and does nothing on failure
(the patron will receive a courtesy notice the following day). Code for the
email notification is not included in this version.

The function is set up to be called repeatedly on a list of barcodes. It could
be easily modified to take a list of patron record numbers (in the case that
barcodes are converted to or stored with record numbers when they are initially 
stored), reducing a call to the API on each iteration. 

To use: place code on a server with PHP5 and cURL installed.
Change $hosturl variable to your Sierra application server
Change $webserver to your webserver
Change $auth to your Client Key and secret in clientkey:secret format.
Remove or comment the echo statements if you don't need them.
