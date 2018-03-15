<?php
require_once 'mathplacement-config.php';
$page = 'login.php';
const EXAMS = [
		'A' => [
				'name' => 'algebra', // Will be auto-capitalized in some places
				'timeAvg' => 40, // The average duration (mins.) of the test. No functional purpose
				'timeLimit' => 60, // End the test after this many minutes
				'copyright' => true, // Show copyright notice
				'answers' => [
						1 => 'E',
						2 => 'C',
						3 => 'E',
						4 => 'A',
						5 => 'D',
						6 => 'C',
						7 => 'B',
						8 => 'B',
						9 => 'E',
						10 => 'C',
						11 => 'A',
						12 => 'D',
						13 => 'B',
						14 => 'D',
						15 => 'C',
						16 => 'A',
						17 => 'E',
						18 => 'D',
						19 => 'B',
						20 => 'D',
						21 => 'C',
						22 => 'E',
						23 => 'A',
						24 => 'E',
						25 => 'C',
						26 => 'C',
						27 => 'D',
						28 => 'A',
						29 => 'A',
						30 => 'C',
						31 => 'B',
						32 => 'B'
				]
		],
		'CR' => [
				'name' => 'calculus readiness',
				'timeAvg' => 30,
				'timeLimit' => 60,
				'copyright' => true,
				'answers' => [
						1 => 'D',
						2 => 'A',
						3 => 'B',
						4 => 'E',
						5 => 'D',
						6 => 'C',
						7 => 'B',
						8 => 'A',
						9 => 'E',
						10 => 'C',
						11 => 'B',
						12 => 'D',
						13 => 'C',
						14 => 'B',
						15 => 'A',
						16 => 'C',
						17 => 'B',
						18 => 'C',
						19 => 'D',
						20 => 'C',
						21 => 'E',
						22 => 'A',
						23 => 'D',
						24 => 'E',
						25 => 'E'
				]
		],
		'C' => [
				'name' => 'calculus',
				'timeAvg' => 45,
				'timeLimit' => 60,
				'copyright' => false,
				'answers' => [
						1 => 'B',
						2 => 'B',
						3 => 'D',
						4 => 'C',
						5 => 'C',
						6 => 'E',
						7 => 'A',
						8 => 'B',
						9 => 'A',
						10 => 'E',
						11 => 'D',
						12 => 'E',
						13 => 'A',
						14 => 'E',
						15 => 'D',
						16 => 'B',
						17 => 'E',
						18 => 'C',
						19 => 'E',
						20 => 'C',
						21 => 'A'
				]
		]
];

// The test will give a grace period of LEEWAY_MINS minutes at the end of the exam. During this time,
// the timer will be frozen at 0, and the student can still submit answers. Any student who runs into
// this will probably think they are the beneficiary of a software bug.
const LEEWAY_MINS = 2;

// Automatically redirect the student to the correct stage of the placement test.
// For example, if they have taken the questionnaire but not started the test and they attempt to
// access questionnaire.php, this will redirect them to pre-test.php. Likewise, if they attempt to
// access pre-test.php before taking the questionnaire, this will redirect them back to questionnaire.php.

if (!empty($_SESSION['mathplacement-pid'])) {
	$questionnaireData = $conn->getRow(
			'SELECT * FROM [PlacementQuestionnaire] ' . 'WHERE [PlacementQuestionnaire].[PID] = ' .
					 $conn->qStr($_SESSION['mathplacement-pid']) . ';');
	$placementData = $conn->getRow(
			'SELECT * ' . 'FROM [PlacementData] ' . 'WHERE [PlacementData].[PID] = ' .
					 $conn->qStr($_SESSION['mathplacement-pid']) . ';');
	if ((!empty($questionnaireData)) && (!empty($placementData))) {
		if ($placementData['CompletedYN'] != 'Y') {
			$answerData = $conn->getRow(
					'SELECT [Answers].[Exam], [Answers].[DateTimeExamStart] ' . 'FROM [Answers] ' .
							 'WHERE [Answers].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']) . ';');
			if ((!empty($answerData)) && ($answerData['DateTimeExamStart'] != null)) {
				$now = new DateTime('now', new DateTimeZone('Z')); // UTC time
				$testStart = new DateTime($answerData['DateTimeExamStart'], new DateTimeZone('Z')); // UTC time
				$testRealEnd = (new DateTime($answerData['DateTimeExamStart'], new DateTimeZone('Z')))->add(
						new DateInterval(
								'PT' . (EXAMS[$answerData['Exam']]['timeLimit'] + LEEWAY_MINS) . 'M')); // UTC time
				//var_dump($now, $testStart, $testRealEnd);
				if ($now < $testStart) {
					// Test start time is in the future.
					
					$page = 'pre-test.php';
				} elseif ($now <= $testRealEnd) {
					// Test start time is in the past; test end time is in the future.
					
					$page = 'test.php';
				} else {
					// Test start and end times are in the past.
					
					$page = 'results.php';
				}
			} else {
				// A test start time could not be found.
				
				$page = 'pre-test.php';
			}
		} else {
			// Student has completed the placement test.
			
			$page = 'results.php';
		}
	} else {
		$page = 'questionnaire.php';
	}
} else {
	$page = 'login.php';
}

if (($page == 'results.php') && ($placementData['Placement'] === null)) {
	// Score test if not already scored.
	
	require_once 'score.php';
}

// If user is not on that page, redirect them to it. ("if" conditional is necessary to avoid
// redirect loop.)
if (basename($_SERVER['SCRIPT_NAME']) != $page) {
	if (!isset($_REQUEST['ajax'])) {
		header('Location: ' . $page);
	} else {
		echo json_encode([
				'location' => $page
		]);
	}
	die();
}
?>