<?php
#error handler
function errorhandler($err)
{
	echo ("<font face=monospace><br>(ERROR): $err</font>");
}
function fatalerror($err)
{
	echo ("<font face=monospace><br>(FATAL ERROR): $err</font>");
	include("footer.php");
	exit();
}
?>
