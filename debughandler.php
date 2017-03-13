<?php
#error handler
function debughandler($dh)
{
	if (DEBUGHANDLER == 1) {
		echo ("<br><font face=monospace>[DEBUG]: $dh</font><br/>");
	}
}
?>
