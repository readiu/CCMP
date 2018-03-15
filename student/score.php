<?php
function postSign($x) {
	if ($x > 0) {
		return $x . '+';
	} elseif ($x < 0) {
		return abs($x) . '-';
	} else {
		return $x;
	}
}

$answers = $conn->getRow(
		'SELECT * FROM [Answers] WHERE [Answers].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']) . ';');
if (!empty($answers)) {
	// Score correct answers.
	$correct = 0;
	foreach (EXAMS[$answers['Exam']]['answers'] as $n => $a) {
		if ($answers['Q' . $n] == $a) {
			++$correct;
		}
	}
	
	// Determine placement and how close they were to the previous/next level.
	$placement = null;
	$dist = null;
	$borderline = false;
	switch ($answers['Exam']) {
		case 'A':
			if ($correct <= 17) {
				$placement = '103';
			} else {
				$placement = '109';
			}
			$d = $correct - 18;
			$dist = postSign($d);
			$borderline = (abs($d) <= 3);
			break;
		case 'CR':
			// Get the student's math ACT score.
			$actMath = $conn->getOne(
					'SELECT [PlacementData].[MathACT] ' . 'FROM [PlacementData] ' .
							 'WHERE [PlacementData].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']) . ';');
			
			if ($actMath <= 20) {
				if ($correct <= 6) {
					$placement = '103';
				} elseif ($correct <= 14) {
					$placement = '109';
				} else {
					$placement = '131';
				}
				$d1 = $correct - 7;
				$d2 = $correct - 15;
				$dist = postSign($d1) . ':' . postSign($d2);
				$borderline = ((abs($d1) <= 3) || (abs($d2) <= 3));
			} elseif ($actMath <= 24) {
				if ($correct <= 14) {
					$placement = '109';
				} else {
					$placement = '131';
				}
				$d = $correct - 15;
				$dist = postSign($d);
				$borderline = (abs($d) <= 3);
			} elseif ($actMath <= 27) {
				if ($correct <= 8) {
					$placement = '109';
				} else {
					$placement = '131';
				}
				$d = $correct - 9;
				$dist = postSign($d);
				$borderline = (abs($d) <= 3);
			} else {
				if ($correct <= 4) {
					$placement = '109';
				} else {
					$placement = '131';
				}
				$d = $correct - 5;
				$dist = postSign($d);
				$borderline = (abs($d) <= 3);
			}
			break;
		case 'C':
			if ($correct <= 10) {
				$placement = '131';
			} else {
				$placement = '132';
			}
			$d = $correct - 11;
			$dist = postSign($d);
			$borderline = (abs($d) <= 3);
			break;
	}
	
	$now = new DateTime('now', new DateTimeZone('Z')); // UTC time
	$conn->autoExecute('[PlacementData]', 
			[
					'CompletedYN' => 'Y',
					'Score' => $correct,
					'MinutesSpent' => min(EXAMS[$answers['Exam']]['timeLimit'] + LEEWAY_MINS, 
							floor(
									($now->format('U') - (new DateTime($answers['DateTimeExamStart'], new DateTimeZone('Z')))->format(
											'U')) / 60)),
					'PrelimPlacement' => $placement,
					'Placement' => $placement,
					'Distance' => $dist,
					'borderlineQ' => (($borderline) ? 'T' : 'F'),
					'DateEdited' => $now
			], DB_AUTOQUERY_UPDATE, 'PID = ' . $conn->qStr($_SESSION['mathplacement-pid']));
}
?>