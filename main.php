<?php
/* 
 * MAIN ROUTINE
*/
$self = $_SERVER['PHP_SELF'];
$today = date('Y-m-d');
$thismonth=date('Y-m');
$friendlythismonth=date('M Y');

if (isset($_POST['savedebit'])) {
	$transactiontype="debit";
	$item=$_POST['item'];
	$amount=$_POST['amount'];
	$date=$_POST['date'];
	if (isset($_POST['notes'])) {
		$notes=$_POST['notes'];
	} else { $notes=""; }
	save($transactiontype, $item,$amount,$date,$notes);
}
if (isset($_POST['savecredit'])){
	$transactiontype="credit";
	$item=$_POST['item'];
	$amount=$_POST['amount'];
	$date=$_POST['date'];
	if (isset($_POST['notes'])) {
		$notes=$_POST['notes'];
	} else { $notes=""; }
	save($transactiontype,$item,$amount,$date,$notes);
}
if (isset($_POST['category'])){
	echo ("<h1>Breakdown by category</h1>
	<h2>Month of $friendlythismonth</h2>
	<hr/>
	<h3>Credits</h3>
	<table>
	<tr>
	<td>Type</td><td>Total Spend</td>
	</tr>");
	$type = "credit";
	category($thismonth,$friendlythismonth,$type);
	
	echo ("
	<h3>Debits</h3>
	<table>
	<tr>
	<td>Type</td><td>Total Spend</td>
	</tr>");
	$type = "debit";
	category($thismonth,$friendlythismonth,$type);
}
if (isset($_POST['view'])) {
	$running_credit_total=0;
	$running_debit_total=0;
	$balance=0;

	echo ("<h1>Budget for $friendlythismonth<h1>");
	
	$txntype="credits";
	$sql = "SELECT * FROM budget WHERE date LIKE '$thismonth-%' AND credits_id !=0 ORDER BY date,credits_id";
	echo ("<h2>Credits</h2>");
	$running_credit_total=view($sql,$txntype,"",$running_credit_total);
	
	$txntype="debits";
	$sql = "SELECT * FROM budget WHERE date LIKE '$thismonth-%' AND debits_id !=0 ORDER BY date,debits_id";
	echo ("<h2>Debits</h2>");
	$running_debit_total=view($sql,$txntype,$running_debit_total,"");

	//figure out the balance and show the totals table
	$balance = $running_credit_total - $running_debit_total;
	echo ("
		<h2>Totals</h2>
		<table>
		<tr>
			<td>Credits</td>
			<td>$ $running_credit_total</td>
		</tr>
		<tr>
			<td>Debits</td>
			<td>$ $running_debit_total</td>
		</tr>
		<tr>
			<td>Balance as at $today</td>
			<td><u>$ $balance</u></td>
		</tr>
		</table>
		");


}

if (isset($_POST['add'])) {
	//add an item
	
	//ADD A DEBIT
	echo ("
		<h2>Add a debit (eg bills)</h2>
		<form method=post action=\"$self\" >
		Debit item:
		<select name=item>");
	include ("dbconnect.php");
	$sql = "SELECT * FROM debits ORDER BY category";
	debughandler ($sql);
	if ( !$result =  $mysqli->query($sql) ){
		fatalerror( $mysqli->errno . $mysqli->error );
	} else {
		if ($result->num_rows === 0){
			fatalerror ("$sql returned no rows");
		} else {
			while ($debits = $result->fetch_assoc()){
				$id = $debits['id'];
				$item = $debits['item'];
				$category = $debits['category'];
				echo ("<option value=\"$id\">$category / $item</option>");
			}
		}
	}
	echo ("
		</select>
		<br/>
		Debit Amount: $<input type=number step=\"any\" size=6 name=amount><br/>
		Date: <input type=text name=date value=\"$today\"><br/>
		Notes (if any): <input type=text name=notes><br/>
		<br/>
		<button type=submit name=savedebit>
			<img src=\"save.png\" width=30 height=30>
		</button>
		</form>");

	//ADD A CREDIT
	echo ("
		<h2>Add a credit value (eg salary)</h2>
		<form method=post action=$self>
		Credit item:
		<select name=item>");
		$sql = "SELECT * FROM credits ORDER BY id";

		debughandler ($sql);
		if ( !$result =  $mysqli->query($sql) ){
			fatalerror( $mysqli->errno . $mysqli->error );
		} else {
			if ($result->num_rows === 0){
				fatalerror ("$sql returned no rows");
			} else {
				while ($credits = $result->fetch_assoc()){
					$id = $credits['id'];
					$name = $credits['name'];
					echo ("<option value=\"$id\">$name</option>");
				}
			}
		}
		echo ("
		</select><br/>
		Debit Amount: $<input type=number step=\"any\" size=6 name=amount><br/>
		Date: <input type=text name=date value=\"$today\"><br/>
		Notes (if any): <input type=text name=notes><br/>
		<button type=submit name=savecredit>
			<img src=\"save.png\" width=30 height=30>
		</button>
		</form>

		<h2>Cancel?</h2>

		<a href=\"$self\"><img src=\"cancel.png\" width=60 height=60 alt=\"Cancel\"></a>
	");
} else {
	echo (" <h1>PerBudget</h1>
		<h3>The Personal Budget</h3>
		<form method=post action=$self>
		<input type=\"image\" src=\"add-2.png\" name=\"add\" value=\"add\" width=180 height=180>
		<input type=image src=\"category.png\" name=\"category\" value=\"category\" width=180 height=180>
		<input type=image src=\"calendar-6.png\" name=\"view\" value=\"view\" width=180 height=180>
		</form>");
}

function save ($transactiontype, $item,$amount,$date,$notes){
	include("dbconnect.php");

	debughandler ("Transaction type: $transactiontype. $item, $date, $notes");
	//get last budget.id number and increment it
	$sql = "SELECT id FROM budget ORDER BY id DESC LIMIT 1";
	if ( !$result =  $mysqli->query($sql) ){
		fatalerror( $mysqli->errno . $mysqli->error );
	} else {
		if ($result->num_rows === 0){
			debughandler ("Performing first run! $sql returned no id rows, assuming this is first entry. id=1");
			$id="1";
		} else {
			while ($budget = $result->fetch_assoc()){
				$id = $budget['id'];
				$id++;
				debughandler ("ID number $id saved to db");
			}
		}
	}
// DEBIT
	if ($transactiontype == "debit") {
		$creditsid="0";
		if ($amount <= 0) {
			fatalerror ("Sorry, the amount must be a positive value above 0");
		}
		$sql="INSERT INTO budget VALUES ('$id','$item','$creditsid','$date','$amount','$notes')";
		debughandler("$sql");
		if ( !$result =  $mysqli->query($sql) ){
			errorhandler( $mysqli->errno . $mysqli->error );
		} else {
			echo ("<h2>Item saved to budget</h2>");
		}
	}
// CREDIT
	if ($transactiontype == "credit") {
		$debitsid="0";
		if ($amount <= 0) {
			fatalerror ("Sorry, the amount must be a positive value above 0");
		}
		$sql="INSERT INTO budget VALUES ('$id','$debitsid','$item','$date','$amount','$notes')";
		if ( !$result =  $mysqli->query($sql) ){
			errorhandler( $mysqli->errno . $mysqli->error );
		} else {
			echo ("<h2>Item saved to budget</h2>");
		}
	}
}
function view($sql,$txntype,$running_debit_total,$running_credit_total){
	//init vars
	$today = date("d-m-Y");
	$running_credit_total=0;
	$running_debit_total=0;

	//grab the data from the budget table.
	include("dbconnect.php");
	if ( !$result = $mysqli->query($sql) ){
			fatalerror( $mysqli->errno . $mysqli->error );
	} else {
		if ($result->num_rows === 0){
			echo ("Budget appears empty for this month!");
		} else {
			echo ("
				<table>
				<tr>
				<td><b>Type</b></td>
				<td><b>Date</b></td>
				<td><b>Amount</b></td>
				<td><b>Notes</b></td>
				</tr>
				<tr>");

			while ($budget = $result->fetch_assoc()){
				$id = $budget['id'];
				$debits_id = $budget['debits_id'];
				$credits_id = $budget['credits_id'];
				$txndate = $budget['date'];
				$amount = $budget['amount'];
				$notes = $budget['notes'];
				if ($txntype == "credits") {
					lookup_id_type($credits_id,"credit");
					$running_credit_total = $running_credit_total + $amount;
				}
				if ($txntype == "debits") {
					lookup_id_type($debits_id,"debit");
					$running_debit_total = $running_debit_total + $amount;
				}
					echo ("<td>$txndate</td>
					<td>$ $amount</td>
					<td>$notes</td>
					</tr>");
			}
		}

		echo ("
		</table>");
		if ($txntype == "credits") {
			return ($running_credit_total);
		}
		if ($txntype == "debits") {
			return ($running_debit_total);
		}
	}
}
function lookup_id_type($id,$type){
	$name="";
	include("dbconnect.php");
	if ($type == "credit") {
		$sql="SELECT name FROM credits WHERE id=$id";
	}
	if ($type == "debit") {
		$sql="SELECT item, category FROM debits WHERE id=$id";
	}
	if ( !$result =  $mysqli->query($sql) ){
			fatalerror( $mysqli->errno . $mysqli->error );
	} else {
		if ($result->num_rows === 0){
			fatalerror ("No item name returned!");
		} else {
			while ($items = $result->fetch_assoc()){
				if ($type=="credit"){
					$name = $items['name'];
				}
				if ($type=="debit"){
					$name = $name . $items['category'];
					$name = $name . ": ";
					$name = $name . $items['item'];
				}
			}
			echo ("<td>$name</td>");
		}
	}
}
function category($thismonth,$friendlythismonth,$type){
	include("dbconnect.php");
	//find the last id number from the credits type table
	$runningtotal = "0";
	$grandtotal = "0";

	if ($type == "credit") {
		$sql = "SELECT MAX(id) FROM credits";
	}
	if ($type =="debit") {
		$sql = "SELECT MAX(id) FROM debits";
	}
	//find out what the last category id is, so we can loop through each category id
	if ( !$result =  $mysqli->query($sql) ){
			fatalerror( $mysqli->errno . $mysqli->error );
	} else {
		if ($result->num_rows === 0){
			fatalerror ("No id number returned from credits/debits table!");
		} else {
			while ($getval = $result->fetch_assoc()){
				//the last id number from the table is stored in $id
				$lookup_id = $getval['MAX(id)'];
				debughandler ("Last ID from credits/debits table = $lookup_id");
			}
		}
		if ($lookup_id != 0) {
			//go through each id in the credits/debits one at a time so that
			//we can see the running total for each category
			for ($i = 1; $i <= $lookup_id; $i++) {
				//clear the running total for the next category id
				$runningtotal = 0;
				debughandler ("<tr><td>iteration $i</td></tr>");
				if ( $type == "credit") {
					$sql = "SELECT * FROM budget WHERE credits_id='$i' AND date LIKE '$thismonth-%'";
				}
				if ( $type == "debit") {
					$sql = "SELECT * FROM budget WHERE debits_id='$i' AND date LIKE '$thismonth-%'";
				}

				if ( !$result =  $mysqli->query($sql) ){
					fatalerror( $mysqli->errno . $mysqli->error );
				}
				if ($result->num_rows === 0){
					debughandler ("There were no credits to display for id $i");
				} else {
					echo ("<tr>");
					lookup_id_type($i,$type);
					while ($items = $result->fetch_assoc()){
						$amount = $items['amount'];
						$runningtotal = $runningtotal + $amount;
					}
					echo ("<td>$runningtotal</td>
						</tr>");
					$grandtotal = $grandtotal + $runningtotal;
				}
			}
			echo ("<tr>
				<td class=\"tablehead\">Grand Total</td>
				<td class=\"tablehead\">$grandtotal</td>
			       </tr>
			</table>");

		} else { fatalerror ("Couldn't get the last credit_type_id!"); }
	}
}
?>
