<?php
#function dbconnect($mysqli){
	$dbname = "perbudget";
	$hostname = "localhost";
	$username = "root";
	$password = "password";
	$mysqli = new mysqli($hostname, $username, $password, $dbname);

	if ( $mysqli->connect_errno ) {
		$err=("Error: " . $mysqli->connect_errno . ": " . $mysqli->connect_error);
		errorhandler($err);
	} else {
		debughandler("dbconnect success");
	}
#}
#function dbclose() {
#	$result->free();
#	$mysqli->close();
#}
?>
