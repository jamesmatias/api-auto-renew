# api-auto-renew
Sierra API Autorenewal

Created by James Matias at Middle Country Public Library

Revised May 13, 2016

Version 2 provides a php file to add and remove patrons to/from a database, a
mysql database structure file, and another php file to run as a cron.

It will attempt to renew any items that the patron has checked out which
are due in "$duexdays" (we use 3 days in this example).

Success and Failure branches are located at the end of the function.
Our library sends an email notification on success and does nothing on failure
(the patron will receive a courtesy notice the following day). 


To use: place code on a server with PHP5 and cURL installed.
Three files must be updated with information specific to your system
apiconstants.php - information about how you'll connect to your Sierra system's REST API
connect.php - information about how you'll connect to your MySQL database to store patron data (opt-in model)
mailvars.php - information about how you'll connect to your email server to send notifications, as well
as the email body.
