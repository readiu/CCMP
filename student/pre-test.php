<?php
require_once 'config.php';

$exam = $conn->getOne(
		'SELECT [PlacementData].[Exam] FROM [PlacementData] ' . 'WHERE [PlacementData].[PID] = ' .
				 $conn->qStr($_SESSION['mathplacement-pid']) . ';');

if ($exam !== null) {
	if (isset($_GET['start-test'])) {
		$now = new DateTime('now', new DateTimeZone('Z')); // UTC time
		$now->setTime($now->format('H'), $now->format('i'), 0); // Floor seconds for MSSQL smalldatetime.
		$pqAnswerRecordExists = $conn->prepare('SELECT 1 FROM [Answers] WHERE [PID] = ?');
		$iAnswerRecordExists = $conn->getOne($pqAnswerRecordExists,
				[
						$_SESSION['mathplacement-pid']
				]);
		if ($iAnswerRecordExists === 1) {
			$conn->autoExecute('[Answers]',
					[
							'exam' => $exam,
							'DateEdited' => $now,
							'DateTimeExamStart' => $now
					], DB_AUTOQUERY_UPDATE, 'PID = ' . $conn->qStr($_SESSION['mathplacement-pid']));
		} else {
			$conn->autoExecute('[Answers]',
					[
							'PID' => $_SESSION['mathplacement-pid'],
							'exam' => $exam,
							'DateEdited' => $now,
							'DateTimeExamStart' => $now
					], DB_AUTOQUERY_INSERT);
		}
		header('Location: test.php');
		die();
	} else {
		$template = new Template('Begin Test - Math Placement Test', null);
		$template->appendToHead('<link rel="stylesheet" href="mathplacement.css" type="text/css">');
		$template->printHeader();
		
		echo '<h3>' . ucwords(EXAMS[$exam]['name']) . '</strong> Test</h3>';
		echo '<p>You will need to take the <strong>' . ucwords(EXAMS[$exam]['name']) .
				 '</strong> test. ' . 'It will take approximately <strong>' . EXAMS[$exam]['timeAvg'] .
				 '</strong> minutes to complete the test. ' . 'You are allowed up to <strong>' .
				 EXAMS[$exam]['timeLimit'] . '</strong> minutes to complete it. ' .
				 'The test will start once you click the button below. ' .
				 'You may begin at your convenience.</p>';
		echo '<div class="centered">';
		echo '<a class="btn btn-primary btn-lg" href="?start-test">Start Test Now</a>';
		echo '</div>';
		echo '<br />';
		
		$template->printFooter();
	}
} else {
	$template = new Template('Begin Test - Math Placement Test', null);
	$template->appendToHead('<link rel="stylesheet" href="mathplacement.css" type="text/css">');
	$template->printHeader();
	throw new Error(
			'A database error occurred. Please try again. ' .
					 'If the problem persists, contact the ITS department.');
	$template->printFooter();
}
?>