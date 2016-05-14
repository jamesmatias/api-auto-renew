<?php
include_once "connect.php";
include_once "patronFunctions.php";

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
				echo date("Y-m-d H:i:s")." Patron response is null.<br>";
				$pid=false;
			}
			else {
				// Assign pid and try to assign email (null if none are present)
				$pid = $patronData["id"];
				try{
					$emailarr = (array)$patronData["emails"];
					$email = $emailarr[0];
				}
				catch (Exception $e){
					$email = null;
				}				
			}

			if ($pid === false)
			{
				// failure
				$message = "Unable to add barcode to database";
			}
			else 
			{
				// Add barcode & pid to database
				$query = "INSERT INTO autorenew.patrons(barcode, recordnum, email, isActive) VALUES (?,?,?,1)";
				if ($stmt = $mysqli->prepare($query))
				{
					$stmt->bind_param('sss', $barcode, $pid, $email);
					$stmt->execute();		
					$myresult = $stmt->affected_rows;
					if ($myresult <= 0)
						die ("Unable to add to the database: ".$mysqli->error);
					else
						$message = "Added Patron# {$pid} to the database.";
					$stmt->close();
				}
				else
					die("Database error: ".$mysqli->error);
			}	
		}				
	}
	else
		die("Database error: ".$mysqli->error);
	
}

?>

<head>
	<title>Autorenewal System</title>
</head>

<body>

			<div class="view-wrap">
			

				<script> // Validate the barcode format
				function validateForm() 
				{
					var bc=document.forms["autorenew-form"]["barcode"].value;
					bc = bc.replace(/[^\d]/g,''); // Remove non-number characters and whitespace
					document.forms["autorenew-form"]["barcode"].value = bc;
					
					if (bc.length == 12 && (bc.substring(0,2)=="07" || bc.substring(0,3)=="067"))
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
										<input name="Submit" type="submit" value="Submit">
									</li>
								</ul>
								<br>
								<span style="color:red;"><?php echo $message;?></span>
						</fieldset>
					</form>
					<fieldset>
						<legend>Modify Patrons</legend>
							<a href="modify_pdb.php">Modify</a>
					</fieldset>
			</div>
</body>
</html>