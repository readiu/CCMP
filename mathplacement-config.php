<?php
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC; // Return rows as associative arrays (instead of numeric arrays).
$ADODB_QUOTE_FIELDNAMES = true;

$username = "MUser";
$username2 = "WebUser";
$password = "MuserPassword";
$password2 = "webuserPassword";
$db = "MathCS";
$db2 = "CentralWeb";
$db3 = "AdmittedStudentPortal";
$host = "Host1";

$conn = NewADOConnection('mssqlnative');
$conn->Connect($host, $username, $password, $db);
if ($conn->isConnected() !== true) {
	die("Database connection failed");
}
$connCentral = NewADOConnection('mssqlnative');
$connCentral->Connect($host, $username2, $password2, $db2);
if ($connCentral->isConnected() !== true) {
	die("Database connection failed");
}
$connPortal = NewADOConnection('mssqlnative');
$connPortal->Connect($host, $username, $password, $db3);
if ($connPortal->isConnected() !== true) {
	die("Database connection failed");
}
?>
