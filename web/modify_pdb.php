<?php
// This is incomplete

include_once "connect.php";
include_once "patronFunctions.php";
?>

<head>
	<title>Autorenewal System</title>
</head>

<body>
			<div class="view-wrap">
				<h1>Patron Autorenewal System</h1>
<?php 
// Check the Post from previous submission
if($_POST['Submit'] == "Submit")
{
	$msg = "";
	// Get list of all records from SQL, then update them with the new values for active
	$query = "SELECT * FROM autorenew.patrons";
	$result = $mysqli->query($query);
	while($row = $result->fetch_assoc())
	{
		// Check if isActive was toggled
		$ind = $row['idpatrons']."-isActive";
		if (isset($_POST[$ind]))
		{			
			// isActive box is checked
			if ($row['isActive'] == 0)
			{
				// Value has changed update it
				$query1 = "UPDATE patrons set isActive=1 WHERE idpatrons={$row['idpatrons']}";
				$result1 = $mysqli->query($query1);
				if (!$result1)
					die("Unable to update the database");
				else
					$msg = $msg."Record {$row['recordnum']} set to Active.<br>";
			}
			// Else do nothing, value has not changed
		}
		else
		{
			// isActive box is not checked
			if ($row['isActive'] == 1)
			{
				// Value has changed update it
				$query1 = "UPDATE patrons set isActive=0 WHERE idpatrons={$row['idpatrons']}";
				$result1 = $mysqli->query($query1);
				if (!$result1)
					die("Unable to update the database");
				else
					$msg = $msg."Record {$row['recordnum']} set to Inactive.<br>";
			}
			// Else do nothing, value has not changed
		}
		
		$ind1 = $row['idpatrons']."-Remove";
		// Check if Remove was checked
		if (isset($_POST[$ind1]))
		{
			// Remove box is checked
			$query1 = "DELETE FROM patrons WHERE idpatrons={$row['idpatrons']}";
			$result1 = $mysqli->query($query1);
			if (!$result1)
				die("Unable to remove row from the database");
			else
				$msg = $msg."Record {$row['recordnum']} was  removed.<br>";
		}
		
	}
}
?>
				<form action="modify_pdb.php" method="post">
						<fieldset>
							<legend>Modify Database</legend>
								<table>
									<tr>
										<td><font color="red"><?php echo $msg; ?></font></td><td></td><td></td><td></td>
									</tr>
									<tr>
										<td>Barcode</td><td>Record#&nbsp;&nbsp;</td><td>Active?&nbsp;&nbsp;</td><td>Remove&nbsp;&nbsp;</td>
									</tr>
									
									<?php 
										$query = "SELECT * FROM autorenew.patrons";
										$result = $mysqli->query($query);
										while($row = $result->fetch_assoc())
										{
											if ($row['isActive']==0)
												$isActive = "False";
											else
												$isActive = "True";
											echo "<tr>";
											echo "<td>{$row['barcode']}&nbsp;&nbsp;</td>";
											echo "<td>{$row['recordnum']}</td>";
											if ($row['isActive']==0)
												echo "<td><input type=\"checkbox\" name=\"{$row['idpatrons']}-isActive\" value=\"{$row['idpatrons']}\"></td>";
											else
												echo "<td><input type=\"checkbox\" name=\"{$row['idpatrons']}-isActive\" value=\"{$row['idpatrons']}\" checked></td>";
											echo "<td><input type=\"checkbox\" name=\"{$row['idpatrons']}-Remove\" value=\"{$row['idpatrons']}\"></td>";
											echo "</tr>";
										}
									?>
									<tr><td><input type="submit" name="Submit" value="Submit"></td><td></td><td></td><td></td></tr>
								</table>
						</fieldset>
				</form>
				<a href="autorenewal.php">Back</a>
			</div>
</body>
</html>