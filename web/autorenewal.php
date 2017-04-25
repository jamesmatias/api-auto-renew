<!doctype html>
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="en" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="en" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"><!--<![endif]-->

<?php
include_once "./autorenew/connect.php";
include_once "./autorenew/patronFunctions.php";

// Check Post
$message = "";
if($_POST['Submit'] == "Submit")
{
	// Parse the form to add ISBN to list, then return to detail page
	$barcode = $_POST['barcode'];
	$found = 0;
	// Check if barcode is already in DB
	// Check SQL return result rows
	$query = "SELECT COUNT(1) FROM autorenew.patrons WHERE barcode=?";
	if ($stmt = $mysqli->prepare($query))
	{
		$stmt->bind_param('s', $barcode);
		$stmt->execute();		
		$stmt->bind_result($found);
		$stmt->fetch();
		if ($found > 0)
		{
			// Do not need to add barcode
			$message = "Barcode is already in database";
			$stmt->close();
		}
		else
		{
			$stmt->close();
			
			// Proceed with adding barcode
			// Get Patron Record Number (and email) for Barcode using API
			$patronData = getPatron($barcode);
			
			// Make sure the response is valid
			if(is_null($patronData) || $patronData === false){
				echo "Patron response is null.<br>";
				$pid=false;
			}
			else if (!array_key_exists("emails", $patronData))
			{
				// No email in record
				$email=false;
			}
			else {
				// Assign pid and try to assign email (null if none are present)
				try {
					$pid = $patronData["id"];
				}
				catch (Exception $e){
					$pid = false;
				}
				try {
					$pnamearr = (array)$patronData["names"];
					$pname = $pnamearr[0];
				}
				catch (Exception $e){
					$pname = "";
				}
				try {
					$emailarr = (array)$patronData["emails"];
					$email = $emailarr[0];					
				}
				catch (Exception $e){
					$email = false;
				}				
			}

			if ($pid === false)
			{
				// failure
				$message = "Unable to add barcode to database. Patron record not found.";
			}
			else if ($email === false)
				$message = "Unable to add user. Please add an email to the patron record.";
			else 
			{
				// Check to see if PID is in database
				$query = "SELECT * FROM autorenew.patrons WHERE recordnum={$pid}";
				$query = mysqli_real_escape_string($mysqli, $query);
				$result = mysqli_query($mysqli, $query);
				if (mysqli_num_rows($result) > 0)
				{
					$message = "Patron Record number is in the database with a different barcode. <br/>
					Please contact ILS Support to have the record updated.";
				}
				else
				{
					// Add name, barcode, pid, and email to database
					$query = "INSERT INTO autorenew.patrons(name, barcode, recordnum, email, isActive) VALUES (?,?,?,?,1)";
					if ($stmt = $mysqli->prepare($query))
					{
						$stmt->bind_param('ssss', $pname,$barcode, $pid, $email);
						$stmt->execute();		
						$myresult = $stmt->affected_rows;
						if ($myresult <= 0)
							die ("Unable to add to the database: ".$mysqli->error);
						else
							$message = "Added Patron {$pname} ({$pid}) to the database.";
						$stmt->close();
					}
					else
						die("Database error: ".$mysqli->error);
				}
			}	
		}				
	}
	else
		die("Database error: ".$mysqli->error);
	
}

?>

<head>
	<title>Library Autorenewal System</title>

	
	
</head>

<body>
	<!--[if lt IE 7]>
		<p class="chromeframe" style="background:#eee; padding:10px; width:100%">Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p>
	<![endif]-->

	<div class="flakes-frame">

		<div class="flakes-content">

			<div class="flakes-mobile-top-bar">
				<a href="" class="logo-wrap">
					<img src="logo.jpg" height="45px">
				</a>

				<a href="" class="navigation-expand-target">
					<img src="img/site-wide/navigation-expand-target.png" height="26px">
				</a>
			</div>

			<div class="view-wrap">
			

				<script> // Validate the barcode format
				function validateForm() 
				{
					// Remove this function or update with your barcode rules below. 
					var bc=document.forms["autorenew-form"]["barcode"].value;
					bc = bc.replace(/[^\d]/g,''); // Remove non-number characters and whitespace
					document.forms["autorenew-form"]["barcode"].value = bc;
					
					if (bc.length > 1 )
					{											    
					    return true;
					}
					else
					{
						alert("It looks like you've typed an invalid barcode. Please try again.");
					    return false;
					}
				}				
				</script>
	



				<!--<h2>News & Updates</h2>-->
				<h1>Patron Autorenewal System</h1>
				<form name="autorenew-form" action="autorenewal.php" method="post" onsubmit="return validateForm();">
						<fieldset>
							<legend>Add a Patron</legend>
								<ul>
									<li>
										<label>Patron Barcode:</label>
										<input name="barcode" type="text" required><br>
									</li>	
									<br>
									<li>
										<input name="Submit" type="submit" value="Submit" class="button-green">
									</li>
								</ul>
								<br>
								<span style="color:red;"><?php echo $message;?></span>
						</fieldset>
					</form>
					<fieldset>
						<legend>Modify Patrons</legend>
							<p>To remove or modify a patron in the database, please contact ILS Support. </p>
					</fieldset>





			

				

				
					
			
				<a href="#" class="scrollToTop"></a>

			</div>
		</div>
	</div>


</body>
</html>