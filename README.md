# api-auto-renew
Sierra API Autorenewal

Created by James Matias at Middle Country Public Library

Revised April 19, 2017

Version 3 provides an update to the token mechanism, which now uses to info Endpoint to 
determine when the token is expired. It also fixes the way checkout entries were traversed,
relying on the actual array, rather than the count returned by the API.

Version 2 provides a php file to add and remove patrons to/from a database, a
mysql database structure file, and another php file to run as a cron.

It will attempt to renew any items that the patron has checked out which
are due in "$duexdays" (we use 3 days in this example).

Success and Failure branches are located at the end of the function.
Our library sends an email notification on success and does nothing on failure
(the patron will receive a courtesy notice the following day). 


To use: place code on a server with PHP5 and cURL installed.
You can use the autorenew.sql file to create an appropriate mySQL schema. 

Three files MUST be updated with information specific to your system
apiconstants.php - information about how you'll connect to your Sierra system's REST API
connect.php - information about how you'll connect to your MySQL database to store patron data (opt-in model)
mailvars.php - information about how you'll connect to your email server to send notifications, as well
as the email body.

Please update the include statements to make sure these files are accessible by the calling files:
autorenewdb.php - performs the renewal process (cron)
autorenewCheckExists.php - checks to see if patron records in the database still exist (cron)
autorenewal.php - adds patrons to the mysql database (web)
modify_pdb.php - modify existing patrons in the mysql database (web)
