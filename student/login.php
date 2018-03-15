<?php
require_once 'mathplacement-config.php';
$template = new Template('Login - Math Placement', null);
$studentID = (isset($_POST['studentID']))? $_POST['studentID']: "";
$email = (isset($_POST['email']))? $_POST['email']: "";
$HSGPA = (isset($_POST['HSGPA']))? $_POST['HSGPA']: "";
$mathACT = (isset($_POST['mathACT']))? $_POST['mathACT']: "";

if ($studentID)
{
	$pqGetKey = $connCentral->prepare(
			implode(' ', 
					[
							'SELECT metaValue',
							'FROM Website_meta',
							'WHERE metaKey = ?'
					]));
	$rsKey = $connCentral->getOne($pqGetKey, [
			'MathPlacement'
	]);
	$uncyptedStudentID = trim(openssl_decrypt($studentID, 'DES-ECB', $rsKey));
	$uncyptedEmail = trim(openssl_decrypt($email, 'DES-ECB', $rsKey));
	$uncyptedHSGPA = trim(openssl_decrypt($HSGPA, 'DES-ECB', $rsKey));
	$uncyptedmathACT = trim(openssl_decrypt($mathACT, 'DES-ECB', $rsKey));
	if ($uncyptedStudentID)
	{
		$getStudentSQL = "SELECT PreferredName, LastName FROM Students 
				WHERE (StudentID  = " . $connPortal->qStr($uncyptedStudentID) . ")";
		$getStudentResults = $connPortal->Execute($getStudentSQL);
		if (!$getStudentResults->EOF)
		{
			$firstName = $getStudentResults->fields("PreferredName");
			$lastName = $getStudentResults->fields("LastName");
			$getPIDSQL = "SELECT PID FROM PlacementData 
					WHERE CentralID = " . $conn->qStr($uncyptedStudentID);
			$getPIDResults = $conn->Execute($getPIDSQL);
			if (!$getPIDResults->EOF)
			{
				$_SESSION['mathplacement-pid'] =$getPIDResults->fields("PID");
				header("Location: questionnaire.php");
				$conn->close();
				$connPortal->close();
				die();
			}
			else
			{
				$insertStudentSQL ="INSERT INTO  PlacementData (LastName, FirstName, MathACT, YearTaken, CentralID, HSGPA) 
						VALUES (
						" . $conn->qStr($lastName) . ", 
						" . $conn->qStr($firstName) . ", 
						" . $conn->addQ($uncyptedmathACT) . ", 
						" . $conn->addQ(date('Y')) . ", 
						" . $conn->qStr($uncyptedStudentID) . ", 
						" . $conn->addQ($uncyptedHSGPA) . "
						)";	
				$conn->Execute($insertStudentSQL);
				$getPIDSQL = "SELECT PID FROM PlacementData
					WHERE CentralID = " . $conn->qStr($uncyptedStudentID);
				$getPIDResults = $conn->Execute($getPIDSQL);
				if (!$getPIDResults->EOF)
				{
					$_SESSION['mathplacement-pid'] =$getPIDResults->fields("PID");
					header("Location: questionnaire.php");
					$conn->close();
					$connPortal->close();
					die();
				}
			}
		}
		else 
		 header("Location: http://go.central.edu/");
		 $conn->close();
		 $connPortal->close();
		 die();
		 
	}
	header("Location: http://go.central.edu/");
	$conn->close();
	$connPortal->close();
	die();
}
header("Location: http://go.central.edu/");
$conn->close();
$connPortal->close();
die();
?>