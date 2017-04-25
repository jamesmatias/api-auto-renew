<!doctype html>
<!--[if lt IE 7 ]> <html lang="en" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="en" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="en" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="en" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="en" class="no-js"><!--<![endif]-->

<?php
include_once "./autorenew/connect.php";
include_once "./autorenew/patronFunctions.php";

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
			

	
	



				<!--<h2>News & Updates</h2>-->
				<h1>Patron Autorenewal System</h1>
<?php 
if($_POST['Submit'] == "Submit")
{
	$msg = "";
	// Get list of all records from SQL, then update them with the new values for active
	$query = "SELECT * FROM autorenew.patrons";
	$result = $mysqli->query($query);
	$count = 0;
	
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
			/*$query1 = "DELETE FROM patrons WHERE idpatrons={$row['idpatrons']}";
			$result1 = $mysqli->query($query1);
			if (!$result1)
				die("Unable to remove row from the database");
			else*/
				$msg = $msg." Record {$row['recordnum']} was not removed.<br>Contact site support to remove a patron.<br>";
		}
		
	}
}
?>
<div id="forms-list">
<div class="flakes-search">

</div>
<p></p>
				<form name="modify_patron_form" action="modify_pdb.php" method="post">
						<fieldset>
							<legend>Modify Database</legend>
								
								<?php
								if(strlen($msg) > 0)
								{
									echo "<span><font color=\"red\">".$msg."</font></span><br/>";
								}
								$query = "SELECT COUNT(1) as count FROM autorenew.patrons WHERE isActive=1";
										$result = $mysqli->query($query);
										$row = $result->fetch_assoc();
										echo "Active Patrons: ".$row['count']."<br>";
								$query = "SELECT COUNT(1) as count FROM autorenew.patrons WHERE isActive=0";
										$result = $mysqli->query($query);
										$row = $result->fetch_assoc();
										echo "Inactive Patrons: ".$row['count']."<br>";		
								?>
								<table class="flakes-table">									
									<thead>
									<tr>
										<td class="name">Name</td><td class="barcode">Barcode</td><td class="recnum">Record#&nbsp;&nbsp;</td><td>Email</td><td>Active?&nbsp;&nbsp;</td>
									</tr>
									</thead>
									<tbody class="list">
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
											echo "<td class=\"name\">{$row['name']}&nbsp;&nbsp;</td>";
											echo "<td class=\"barcode\">{$row['barcode']}&nbsp;&nbsp;</td>";
											echo "<td class=\"recnum\">{$row['recordnum']}</td>";
											echo "<td class=\"recnum\">{$row['email']}</td>";
											if ($row['isActive']==0)
												echo "<td><input type=\"checkbox\" name=\"{$row['idpatrons']}-isActive\" value=\"{$row['idpatrons']}\"></td>";
											else
												echo "<td><input type=\"checkbox\" name=\"{$row['idpatrons']}-isActive\" value=\"{$row['idpatrons']}\" checked></td>";
											//echo "<td><input type=\"checkbox\" name=\"{$row['idpatrons']}-Remove\" value=\"{$row['idpatrons']}\"></td>";
											echo "</tr>";
										}
									?>
									
									</tbody>
								</table>
								<table>
								<tr><td><input type="submit" name="Submit" value="Submit" class="button-green"></td></tr>
								</table>
						</fieldset>
				</form>
	</div>
</div>				





			

				

				
					
			
				<a href="#" class="scrollToTop"></a>
				
				<form action="autorenewal.php">
					&nbsp;<input type="submit" value="Back" class="button-blue"/>
				</form>
				<br/>
				<br/>
			</div>
		</div>
	</div>


	
	
	<script type="text/javascript">
	   var options = {
    valueNames: [ 'name', 'barcode','recnum' ]
};

var formsList = new List('forms-list', options);
</script>
</body>
</html>