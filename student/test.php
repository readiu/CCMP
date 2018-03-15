<?php
require_once 'config.php';

$answersRow = $conn->getRow(
		'SELECT * ' . 'FROM [Answers] ' . ' WHERE [Answers].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']));

$answers = ['A', 'B', 'C', 'D', 'E'];
$nullAnswer = '';
$exam = $answersRow['Exam'];

$now = new DateTime('now', new DateTimeZone('Z')); // UTC time
$testStart = new DateTime($answersRow['DateTimeExamStart'], new DateTimeZone('Z')); // UTC time
$testEnd = (new DateTime($answersRow['DateTimeExamStart'], new DateTimeZone('Z')))->add(
		new DateInterval('PT' . EXAMS[$exam]['timeLimit'] . 'M')); // UTC time
$testRealEnd = (new DateTime($answersRow['DateTimeExamStart'], new DateTimeZone('Z')))->add(
		new DateInterval('PT' . (EXAMS[$exam]['timeLimit'] + LEEWAY_MINS) . 'M')); // UTC time
$testEndLocal = $testEnd->setTimezone(new DateTimeZone(date_default_timezone_get()));
$testEndEpoch = $testEnd->format('U');
$timeRemain = $testEnd->diff($now);

if ((isset($_POST['saveProgress'])) || (isset($_POST['submitFinal'])) || (isset($_POST['submitFinalAffirm']))) {
	// The user submitted the form, either to save progress or to finish the test for good.
	
	$updateCols = [];
	foreach (array_keys(EXAMS[$exam]['answers']) as $i) {
		if (isset($_POST['ans-' . $i])) {
			if (in_array($_POST['ans-' . $i], $answers)) {
				$updateCols['Q' . $i] = $_POST['ans-' . $i];
			} elseif (empty($_POST['ans-' . $i])) {
				$updateCols['Q' . $i] = null;
			}
		}
	}
	
	if (!empty($updateCols)) {
		$updateCols['DateEdited'] = $now;
		$conn->autoExecute('[Answers]', $updateCols, DB_AUTOQUERY_UPDATE, 'PID = ' . $conn->qStr($_SESSION['mathplacement-pid']));
	}
	
	if ((isset($_POST['submitFinal'])) || (isset($_POST['submitFinalAffirm']))) {
		require_once 'score.php';
		
		if (!isset($_REQUEST['ajax'])) {
			header('Location: results.php');
		} else {
			echo json_encode([
					'location' => 'results.php'
			]);
		}
		die();
	}
	
	// Remove POST data so that they don't accidentally resubmit stale answers.
	if (!isset($_REQUEST['ajax'])) {
		header('Location: ' . $_SERVER['REQUEST_URI']);
	} else {
		echo json_encode([
				'math-placement-test' => [
						'saved' => true,
						'secsRemain' => ($testEnd->format('U') - $now->format('U'))
				]
		]);
	}
	die();
}

$template = new Template('Math Placement', null);
$template->appendToHead('<link rel="stylesheet" href="mathplacement.css" type="text/css">');
$template->printHeader();
?>
<h3><?php echo ucwords(EXAMS[$exam]['name']); ?> Test</h3>
<div class="row" id="math-placement-test-intro">
	<div class="col-xs-12">
		<p>Each question is followed by five suggested answers, labeled (A) through (E). Select the one
			best answer to each question. Do not spend too much time on any one question. Indicate your
			answers by choosing from the dropdown next to each question. Before submitting, you may wish to
			double-check that you entered the intended answer for each problem.</p>
		<p>
			In all graphing problems, assume the usual coordinate system. A calculator is not required for
			any question on this test. For most students, it takes approximately <strong><?php echo EXAMS[$exam]['timeAvg']; ?></strong>
			minutes to complete this test. You may take up to <strong><?php echo EXAMS[$exam]['timeLimit']; ?></strong>
			minutes to complete the test.
		</p>
		<?php
		if (EXAMS[$exam]['copyright']) {
			echo '<p>This test is copyrighted by the Mathematical Association of America.</p>';
		}
		?>
	</div>
</div>
<form id="math-placement-test" method="post">
	<?php
	foreach (array_keys(EXAMS[$exam]['answers']) as $i) {
		// Image names are padded with zeroes. No pun intended.
		$iPad = str_pad($i, ceil(log10(count(EXAMS[$exam]['answers']))), '0', STR_PAD_LEFT);
	?>
	<div class="math-q row">
		<div class="col-sm-10 col-md-11">
			<img alt="algebra test question #<?php echo $i; ?>" class="math-q-img"
				src="images/<?php echo $exam . '_' . $iPad . '.jpg'; ?>" />
		</div>
		<div class="col-sm-2 col-md-1">
			<select class="math-q-answer" name="<?php echo 'ans-' . $i; ?>">
				<?php
					foreach (array_merge([$nullAnswer], $answers) as $a) {
						$ah = htmlspecialchars($a);
						echo '<option value="' . $ah . '" ' .
								 (($a == $answersRow['Q' . $i]) ? 'selected="selected"' : '') . '>' . $ah .
								 '</option>';
					}
				?>
			</select>
		</div>
	</div>
	<?php
	}
	?>
	<div class="row" style="text-align: center;">
		<input class="btn btn-success" name="submitFinal" type="submit" value="Submit answers as final" />
	</div>
	<div class="modal fade" id="math-placement-finish-modal" tabindex="-1" role="dialog"
		aria-labelledby="math-placement-finish-modal-label">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title" id="math-placement-finish-modal-label">Submit answers?</h4>
				</div>
				<div class="modal-body">
					<p id="unanswered">
						You have left <span id="unanswered-question-nums"></span> blank. (Points will not be deducted
						for guessing.)
					</p>
					<p>Are you sure that you're finished with the test and want to submit your current answers as
						final?</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-info" data-dismiss="modal">Keep working</button>
					<input class="btn btn-warning" type="submit" name="submitFinalAffirm"
						value="Submit all answers now" />
				</div>
			</div>
		</div>
	</div>
	<div class="bottom-bar">
		<span id="timeInfo">
			<?php
				echo 'Test ends at <strong>' . $testEndLocal->format('g:i') . '</strong>' .
						 $testEndLocal->format('a') . ' ' .
						 $testEndLocal->getTimezone()->getTransitions($testEndEpoch, $testEndEpoch)[0]['abbr'];
			?>
			<span id="timeRemaining">– <span id="minsRemain"><?php echo min(0, $timeRemain->i); ?></span>
					minutes remaining
			</span>
		</span>
		<input class="btn btn-primary" id="saveProgress" name="saveProgress" type="submit"
			value="Save progress" />
	</div>
</form>
<?php
$template->appendtoBody(
		'<script>var secsRemainAtLoad = ' . ($testEnd->format('U') - $now->format('U')) . ';</script>');
$template->appendtoBody(
		'<script src="mathplacement.js"></script>');
$template->printFooter();
?>