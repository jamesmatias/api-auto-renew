<?php
$mysqli = new mysqli("localhost", "username", "password", "autorenew"); // Update username and password
if ($mysqli->connect_errno) {
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
?>