<?php
require_once 'mathplacement-config.php';
$template = new Template('Login - Math Placement', null);
$loginError = false;
$loginErrMsg ="";
if ((!empty($_POST['email'])) && (!empty($_POST['email2']))) {
	if (trim($_POST['email']) == trim($_POST['email2'])) 
	{
	// TODO Implement actual login system.
		$getPIDSQL = "SELECT MAX(PID) AS MaxPID FROM PlacementData
						WHERE email  = " . $conn->qStr($_POST['email']) ."
						GROUP BY email";
		$getPIDResults = $conn->Execute($getPIDSQL);
		if (!$getPIDResults->EOF)
		{
			$_SESSION['mathplacement-pid'] =$getPIDResults->fields("MaxPID");
			header("Location: questionnaire.php");
			$conn->close();
			$connPortal->close();
			die();
		}
		else 
		{
			$loginError = true;
			$loginErrMsg = "<strong>Oops!</strong> The email address you enterend was not found.";
		}
	}
	else 
	{
		$loginError = true;
		$loginErrMsg = "<strong>Oops!</strong> The email addresses you entered do not match.";
	}
}

$template->appendToHead('<link rel="stylesheet" href="mathplacement.css" type="text/css">');
$template->printHeader();
?>
<h3>Math Placement</h3>
<form action="" method="post">
	<?php
	if ($loginError) {
	?>
	<div class="alert alert-danger centered" role="alert">
		<?=$loginErrMsg?>
	</div>
	<?php
	}
	?>
	<div class="form-group">
		<label for="mathplacement-login-email" class="col-sm-2 col-md-offset-4 col-md-1 control-label">Email:</label>
		<div class="col-sm-10 col-md-3">
			<input type="input" class="form-control" id="mathplacement-login-email" name="email"
				placeholder="Email" required="required" autofocus="autofocus"
				value="<?php if (!empty($_POST['FirstName'])) echo htmlspecialchars($_POST['FirstName']); ?>">
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="form-group">
		<label for="mathplacement-login-last" class="col-sm-2 col-md-offset-4 col-md-1 control-label">Email confirm:</label>
		<div class="col-sm-10 col-md-3">
			<input type="input" class="form-control" id="mathplacement-login-email-confirm" name="email2"
				placeholder="Confirm Email" required="required">
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="form-group">
		<div class="col-md-offset-4 col-md-4 centered">
			<button type="submit" class="btn btn-primary">Start Math Placement</button>
		</div>
	</div>
</form>
<?php
$template->printFooter();
?>

<?php 
function pkcs7unpad($padded, $blocksize)
{
	$l = strlen($padded);
	if ($l % $blocksize != 0)
	{
		throw new Exception("Padded plaintext cannot be divided by the block size");
	}
	$padsize = ord($padded[$l - 1]);
	if ($padsize === 0)
	{
		throw new Exception("Zero padding found instead of PKCS#7 padding");
	}
	if ($padsize > $blocksize)
	{
		throw new Exception("Incorrect amount of PKCS#7 padding for blocksize");
	}
	// check the correctness of the padding bytes by counting the occurance
	$padding = substr($padded, -1 * $padsize);
	if (substr_count($padding, chr($padsize)) != $padsize)
	{
		throw new Exception("Invalid PKCS#7 padding encountered");
	}
	return substr($padded, 0, $l - $padsize);
}

?>