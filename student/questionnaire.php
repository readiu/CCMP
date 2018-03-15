<?php
require_once 'config.php';

/**
 * Check whether a value matches an array of acceptable values.
 * @param string $value The value to be checked.
 * @param array $acceptableValues Optional. The acceptable values for $value. If unset, any value is accepted.
 * @param boolean $multiple Optional. Whether mutiple values can be chosen at once.
 * @return boolean True if $value is in $acceptableValues, false otherwise.
 */
function validate(&$value, $acceptableValues = null, $multiple = false) {
	// Return false if the value hasn't even been set.
	if ((!isset($value)) || ($value == "")){
		return false;
	}
	
	// Return true if any value should be accepted.
	if ($acceptableValues === null) {
		return true;
	}
	
	// Dereference $value to new variable to avoid changing existing value.
	$derefVal = $value;
	
	// Ensure that $value and $acceptableValues are always arrays for easier processing.
	if (!is_array($derefVal)) {
		$derefVal = [$derefVal];
	}
	if (!is_array($acceptableValues)) {
		$acceptableValues = [$acceptableValues];
	}
	
	// Check if $value exists in $acceptableValues, and return result.
	return (count(array_diff($derefVal, $acceptableValues)) == 0);
}

/**
 *
 * @return $var if $var is set; null otherwise
 */
function getOrNull(&$var) {
	return ((isset($var)) ? $var : null);
}

/**
 * Same as PHP empty(), but returns false for 0.
 *
 * @return boolean true if $var is empty (but not some form of 0); false otherwise
 * @see empty()
 */
function emptyNot0(&$var) {
	// Let's face it: a string that just contains whitespace is empty.
	if (gettype($var) == 'string') {
		$var = trim($var);
	}
	
	return (($var !== 0) && ($var !== 0.0) && ($var !== "0") && (empty($var)));
}

/**
 * Builds the <option>s for a <select> field.
 *
 * @param array $valuesAssoc
 *        	Associative array of options, where keys are HTML `value` attributes and
 *        	values are HTML description text. (A little confusing, I know.)
 * @param mixed $selectedVal
 *        	The default value to display (e.g. if the user is correcting a previous
 *        	attempt.
 * @return string HTML string of the <option>s.
 */
function buildOptions($valuesAssoc, $selectedVal) {
	$str = '<option value="" ' . ((emptyNot0($selectedVal)) ? 'selected="selected"' : '') .
			 '></option>';
	foreach ($valuesAssoc as $k => $v) {
		$str .= '<option value="' . htmlspecialchars($k) . '" ' .
				 (((!emptyNot0($selectedVal)) && ($k == $selectedVal)) ? 'selected="selected"' : '') .
				 '>' . htmlspecialchars($v) . '</option>';
	}
	return $str;
}
//$_POST['advanced'] = (isset($_POST['advanced']))? $_POST['advanced'] : "";
//$_POST['majors'] = (isset($_POST['majors']))? $_POST['majors'] : "";
//$_POST['majors-other'] = (isset($_POST['majors-other']))? $_POST['majors-other'] : "";

$grades = [
		'A',
		'A-',
		'B+',
		'B',
		'B-',
		'C+',
		'C',
		'C-',
		'D+',
		'D',
		'D-',
		'F'
];

$majors = $conn->getCol(
		'SELECT TOP 100 [Major] FROM [Courses_For_Majors] ' . 'WHERE [Enabled] = 1 ' .
				 'AND [Major] != ' . $conn->qStr('(Anything else)') . ';');

if (count($_POST) > 0) {
	// Validate the data.
	$errors = [];
	// "Years of math" does not need to be checked; it is always either set (with value(s)) or unset.
	//howeh - 8/15/17 - CUI - start - add logic to ensure the 'Years of math' if answered - per Mark Mills
	if (!validate($_POST['years'], ['senior', 'junior', 'sophomore', 'freshman', 'beforehighschool']))
		$errors[] = 'years';
	//howeh - 8/15/17 - CUI - end
	if (!validate($_POST['topics']['polynomials'], ['N', 'B', 'S']))
		$errors[] = 'polynomials';
	if (!validate($_POST['topics']['exponentials'], ['N', 'B', 'S']))
		$errors[] = 'exponentials';
	if (!validate($_POST['topics']['trig'], ['N', 'B', 'S']))
		$errors[] = 'trig';
	if (!validate($_POST['topics']['logarithms'], ['N', 'B', 'S']))
		$errors[] = 'logarithms';
	if (!validate($_POST['calc'], ['0', '1', '2']))
		$errors[] = 'calculus course taken';
	if (!validate($_POST['calc-ap'], ['No', 'AB', 'BC', 'Not Sure']))
		$errors[] = 'Advanced Placement Calculus';
	$advanced = ((!empty($_POST['advanced'])) ? array_keys($_POST['advanced']) : []);
	if ((isset($_POST['advanced'])) && (!validate($advanced, 
			[
					'integrationbyparts',
					'improperintegrals',
					'sequencesandseries',
					'taylorpolynomials'
			])))
		$errors[] = 'advanced topics';
	if (validate($_POST['collegecredit-boolean'], ['0', '1'])) {
		if ($_POST['collegecredit-boolean'] == '1') {
			if ((!validate($_POST['collegecredit-coursenames'])) ||
					 (strlen($_POST['collegecredit-coursenames']) > 150))
				$errors[] = 'college credit course names';
			if ((!validate($_POST['collegecredit-collegename'])) ||
					 (strlen($_POST['collegecredit-collegename']) > 150))
				$errors[] = 'college credit college/university';
		}
	}
	else {
		$errors[] = 'college credit yes/no';
	}
	if (((!validate($_POST['majors'], $majors, true)) && (empty(trim($_POST['majors-other'])))) ||
			 (strlen($_POST['majors-other']) > 200))
		$errors[] = 'major(s)';
	
	// Only process the data if all fields are valid.
	if (count($errors) == 0) {
		// Determine the correct placement test.
		
		// Get the student's math ACT score.
		$actMath = $conn->getOne(
				'SELECT [PlacementData].[MathACT] ' . 'FROM [PlacementData] ' .
						 'WHERE [PlacementData].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']) . ';');
		
		// Process "topics" responses. "Have not seen" = 0 points (implicitly), "seen briefly" = 1,
		// "seen sufficiently" = 2. A total of 7 or higher will set the variable to true; otherwise,
		// false.

		$preCalc = (array_reduce($_POST['topics'], 
				function ($carry, $item) {
					switch ($item) {
						case 'S':
							$carry += 2;
							break;
						case 'B':
							$carry += 1;
							break;
					}
					return $carry;
				}, 0) > 6);
		
		//howeh - 8/24/17 - CUI - start - did this student say they have taken at least one
		//semester of calculus?  If so, then they $calc is set to true
		$calc = false;
		if ($_POST['calc'] == '2' || $_POST['calc'] == '1'){
			$calc = true;
		}
		//howeh - 8/24/17 - CUI - end
		
		$testNecessary = true;
		//This is the first line in the math matrix ACT 0 - 17
		if ($actMath < 18) {
			if ($calc){
				if ($preCalc){
					//Did they say 2 semesters of calc?
					if($_POST['calc'] == '2'){
						//have they seen 3 or more advanced calculus topics?
						if(count($advanced) > 2){
							$placement = 'C';
						}else{
							$placement = '131';
							$testNecessary = false;
						}
					}else{
						//this is 1 semester of calc
						$placement = '131';
						$testNecessary = false;
					}
				}else{
					//this is calc BUT no precalc
					//this should not occur - any student
					//who says they have calc should also have had
					//preCalc - this is probably when a student
					//did not understand the questionnaire
					$placement = 'CR';
				}
			}elseif($preCalc){//no calc - check for preCalc
				$placement = 'CR';
			}else{
				$placement = '103';
				$testNecessary = false;
			}	
		//ACT > 17
		} elseif ($calc){
			if($_POST['calc'] == '2'){
				if(count($advanced) > 2){
					switch (true) {
						case ($actMath > 24):
							if(count($advanced) == 3){
								$placement = '132';
								$testNecessary = false;
							}else{
								$placement = '231';
								$testNecessary = false;
							}
							break;
						case ($actMath > 20):
							if(count($advanced) == 3){
								$placement = 'C';
							}else{
								$placement = '132';
								$testNecessary = false;
							}
							break;
						default:
							$placement = 'C';
					}
				}else{
					if($actMath > 24){
						$placement = 'C';
					}else{
						$placement = '131';
						$testNecessary = false;
					}
				}
			}else{
				$placement = '131';
				$testNecessary = false;
			}
		}elseif($preCalc){
			if($actMath > 27){
				$placement = '131';
				$testNecessary = false;
			}else{
				$placement = 'CR';
			}
		}else{
			switch (true) {
				case ($actMath > 24):
					$placement = '109';
					$testNecessary = false;
					break;
				case ($actMath > 20):
					$placement = 'A';
					break;
				default:
					if (count($_POST['years']) > 3) {
						$placement = 'A';
					} else {
						$placement = '103';
						$testNecessary = false;
					}
			}
		}
		
		// Insert questionnaire responses into database.
		$questionnaireInsert = $conn->autoExecute('[PlacementQuestionnaire]', 
				[
						'PID' => $_SESSION['mathplacement-pid'],
						'YearsMath' => ((isset($_POST['years'])) ? count($_POST['years']) : 0),
						'Polynomials' => $_POST['topics']['polynomials'],
						'Exponential' => $_POST['topics']['exponentials'],
						'Trig' => $_POST['topics']['trig'],
						'Logarithms' => $_POST['topics']['logarithms'],
						'Calc' => $_POST['calc'],
						'CalcAP' => $_POST['calc-ap'],
						'IntegrationByParts' => ((in_array('integrationbyparts', $advanced)) ? 1 : 0),
						'ImproperIntegrals' => ((in_array('improperintegrals', $advanced)) ? 1 : 0),
						'SequencesAndSeries' => ((in_array('sequencesandseries', $advanced)) ? 1 : 0),
						'TaylorPolynomials' => ((in_array('taylorpolynomials', $advanced)) ? 1 : 0),
						'CalcTopics' => ((($_POST['calc'] > 0) || ($_POST['calc-ap'] != 'No')) ? count(
								$advanced) : -1),
						'CollegeCredit' => $_POST['collegecredit-boolean'],
						'CollegeCourses' => $_POST['collegecredit-coursenames'],
						'CollegeName' => $_POST['collegecredit-collegename'],
						'Major' => ((!empty($_POST['majors'])) ? implode(', ', $_POST['majors']) : null),
						'OtherMajors' => $_POST['majors-other'],
						'Completed' => time()
				], DB_AUTOQUERY_INSERT);
		
		if ($questionnaireInsert) {
			if ($testNecessary) {
				$dataUpdate = $conn->autoExecute('[PlacementData]', [
						'Exam' => $placement,
						'DateEdited' => time()
				], DB_AUTOQUERY_UPDATE, '[PlacementData].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']));
			} else {
				$dataUpdate = $conn->autoExecute('[PlacementData]', [
						'Exam' => 'N',
						'CompletedYN' => 'Y',
						'PrelimPlacement' => $placement,
						'Placement' => $placement,
						'DateEdited' => time()
				], DB_AUTOQUERY_UPDATE, '[PlacementData].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']));
			}
			
			if ((!$dataUpdate) && ($conn->affected_rows() > 0)) {
				throw new Error(
						'A database error occurred. Please try again. ' .
								 'If the problem persists, contact the ITS department.');
			}
		} else {
			throw new Error(
					'A database error occurred. Please try again. ' .
							 'If the problem persists, contact the ITS department.');
		}
		
		if (empty($errors)) {
			if ($testNecessary) {
				header('Location: pre-test.php');
			} else {
				header('Location: results.php');
			}
			die();
		}
	}
}

$template = new Template('Questionnaire - Math Placement', null);
$template->appendToHead('<link rel="stylesheet" href="mathplacement.css" type="text/css">');
$template->printHeader();

$name = $conn->getRow(
		'SELECT [PlacementData].[FirstName], [PlacementData].[LastName]' . 'FROM [PlacementData] ' .
				 'WHERE [PlacementData].[PID] = ' . $conn->qStr($_SESSION['mathplacement-pid']) . ';');
?>
<h3>Math Placement</h3>
<h4><em>Welcome <?php echo $name['FirstName'] . ' ' . $name['LastName']; ?>!</em></h4>
<div class="row" id="questionnaire-intro">
	<div class="col-xs-12">
		<p>
			<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> This whole process has one
			main goal: to determine which mathematics course(s) may be best for you. You may need to take a
			test. If so, it is in the best interest of both of us for you to approach the test honestly and
			seriously. This doesn't mean that you should have been studying; just do as well as you can, and
			we'll take it from there.
		</p>
		<p>
			<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> No calculator is necessary nor
			permitted for this exam.
		</p>
	</div>
</div>
<?php
if (!empty($errors)) {
?>
<div class="alert alert-warning" id="questionnaire-errors" role="alert">
	<span class="glyphicon glyphicon-alert" aria-hidden="true"></span> Oops! You forgot to fill
	out the following field(s):
	<ul>
	<?php
	echo array_reduce($errors, function ($str, $e) {
		return $str . '<li>' . $e . '</li>';
	});
	?>
	</ul>
</div>
<?php
}
?>
<form class="form-horizontal" id="questionnaire-form" method="post" role="form">
	<section>
		<h5 class="col-sm-3 col-md-2">I <em>did</em> take math during my&hellip;</h5>
		<div class="col-xs-offset-1 col-sm-offset-0 col-sm-9 col-md-4">
			<div class="form-group">
				<em>Check all that apply:</em>
				<?php
				foreach ([
						'senior' => 'senior year',
						'junior' => 'junior year',
						'sophomore' => 'sophomore year',
						'freshman' => 'freshman year',
						'beforehighschool' => 'high-school mathematics taken before high school'
				] as $k => $v) {
				?>
				<div class="checkbox">
					<label class="control-label" for="years-<?php echo htmlspecialchars($k); ?>"> <input
						id="years-<?php echo htmlspecialchars($k); ?>" name="years[]" type="checkbox"
						value="<?php echo htmlspecialchars($k); ?>"
						<?php if ((!empty($_POST['years'])) && (in_array($k, $_POST['years']))) echo 'checked="checked"'; ?> />
						<?php echo htmlspecialchars($v); ?>
					</label>
				</div>
				<?php
				}
				?>
			</div>
		</div>
	</section>
	<div class="clearfix visible-sm visible-xs"></div>
	<section>
		<h5 class="col-sm-3 col-md-2">Which of these topics have you seen in a high-school math course?</h5>
		<div class="col-xs-offset-1 col-sm-offset-0 col-sm-9 col-md-4">
			<div class="form-group">
				<label class="control-label" for="topics-polynomials">
					Polynomials (e.g. <em>5x&#x00b3; + 3x + 2</em>):
				</label>
				<br />
				<select id="topics-polynomials" name="topics[polynomials]" required="required">
					<?php
					echo buildOptions(
							[
									'N' => 'Have not seen',
									'B' => 'Seen briefly',
									'S' => 'Seen sufficiently'
							], getOrNull($_POST['topics']['polynomials']), 'select');
					?>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="topics-exponentials">
					Exponential functions (e.g. <em>e&#x2e3;</em> or <em>10&#x2e3;</em>):
				</label>
				<br />
				<select id="topics-exponentials" name="topics[exponentials]" required="required">
					<?php
					echo buildOptions(
							[
									'N' => 'Have not seen',
									'B' => 'Seen briefly',
									'S' => 'Seen sufficiently'
							], getOrNull($_POST['topics']['exponentials']), 'select');
					?>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="topics-trig">
					Trig functions (e.g. <em>sin(x)</em>, <em>cos(x)</em>, <em>tan(x)</em>):
				</label>
				<br />
				<select id="topics-trig" name="topics[trig]" required="required">
					<?php
					echo buildOptions(
							[
									'N' => 'Have not seen',
									'B' => 'Seen briefly',
									'S' => 'Seen sufficiently'
							], getOrNull($_POST['topics']['trig']), 'select');
					?>
				</select>
			</div>
			<div class="form-group">
				<label class="control-label" for="topics-logarithms">
					Logarithms (e.g. <em>ln(x)</em> or <em>log(x)</em>):
				</label>
				<br />
				<select id="topics-logarithms" name="topics[logarithms]" required="required">
					<?php
					echo buildOptions(
							[
									'N' => 'Have not seen',
									'B' => 'Seen briefly',
									'S' => 'Seen sufficiently'
							], getOrNull($_POST['topics']['logarithms']), 'select');
					?>
				</select>
			</div>
		</div>
	</section>
	<div class="clearfix"></div>
	<section>
		<h5 class="col-sm-3 col-md-2">
			<label for="calc">Have you taken a Calculus course (i.e. derivatives and/or integrals)?</label>
		</h5>
		<div class="col-xs-offset-1 col-sm-offset-0 col-sm-9 col-md-4">
			<div class="form-group">
				<select id="calc" name="calc" required="required">
					<?php
					echo buildOptions(
							[
									'0' => 'No',
									'1' => 'Yes: 1 semester',
									'2' => 'Yes: 2+ semesters'
							], getOrNull($_POST['calc']), 'select');
					?>
				</select>
			</div>
		</div>
	</section>
	<div class="clearfix visible-sm"></div>
	<section>
		<h5 class="col-sm-3 col-md-2">
			<label for="calc-ap">Have you taken the Advanced Placement (AP) Calculus exam or course?</label>
		</h5>
		<div class="col-xs-offset-1 col-sm-offset-0 col-sm-9 col-md-4">
			<div class="form-group">
				<select id="calc-ap" name="calc-ap" required="required">
					<?php
					echo buildOptions(
							[
									'No' => 'No',
									'AB' => 'Yes: AB',
									'BC' => 'Yes: BC',
									'Not Sure' => 'Not sure'
							], getOrNull($_POST['calc-ap']), 'select');
					?>
				</select>
			</div>
		</div>
	</section>
	<div class="clearfix"></div>
	<section>
		<h5 class="col-sm-3 col-md-2">Advanced calculus topics:</h5>
		<div class="col-xs-offset-1 col-sm-offset-0 col-sm-9 col-md-4">
			<div class="form-group">
				<p>
					If your Calculus or AP Calculus course covered topics beyond derivatives or differentiation,
					please mark each advanced topic shown below for which you have had sufficient exposure. Do <em>not</em>
					select these unless you <em>know</em> what these terms mean. If you need clarification on what
					these terms mean,
					<a href="have-you-seen.pdf" target="_blank" title="Link opens in new window">read this <span
						class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a>.
				</p>
				<?php
				foreach ([
						'integrationbyparts' => 'Integration by parts',
						'improperintegrals' => 'Improper integrals',
						'sequencesandseries' => 'Sequences and series',
						'taylorpolynomials' => 'Taylor polynomials'
				] as $k => $v) {
				?>
				<div class="checkbox">
					<label class="control-label" for="advanced-<?php echo htmlspecialchars($k); ?>"> <input
						id="advanced-<?php echo htmlspecialchars($k); ?>"
						name="advanced[<?php echo htmlspecialchars($k); ?>]" type="checkbox" value="yes"
						<?php if ((!empty($_POST['advanced'])) && (array_key_exists($k, $_POST['advanced']))) echo 'checked="checked"'; ?> />
						<?php echo htmlspecialchars($v); ?>
					</label>
				</div>
				<?php
				}
				?>
			</div>
		</div>
	</section>
	<div class="clearfix visible-sm"></div>
	<section>
		<h5 class="col-sm-3 col-md-2">
			<label for="collegecredit-boolean">Were any of your mathematics courses taken for college credit?</label>
		</h5>
		<div class="col-xs-offset-1 col-sm-offset-0 col-sm-9 col-md-4">
			<div class="form-group">
				<select id="collegecredit-boolean" name="collegecredit-boolean" required="required">
					<?php
					echo buildOptions(
							[
									'0' => 'No',
									'1' => 'Yes'
							], getOrNull($_POST['collegecredit-boolean']), 'select');
					?>
				</select>
			</div>
			<div class="form-group">
			<label class="control-label" for="collegecredit-coursenames">Course name(s):</label>
			<br />
			<input id="collegecredit-coursenames" name="collegecredit-coursenames" type="text"
				value="<?php echo getOrNull($_POST['collegecredit-coursenames']); ?>" maxlength="150" />
			</div>
			<div class="form-group">
			<label class="control-label" for="collegecredit-collegename">College/university:</label>
			<br />
			<input id="collegecredit-collegename" name="collegecredit-collegename" type="text"
				value="<?php echo getOrNull($_POST['collegecredit-collegename']); ?>" maxlength="150" />
			</div>
		</div>
	</section>
	<div class="clearfix"></div>
	<section>
		<h5 class="col-sm-3 col-md-2">
			Which of the following majors or programs are you considering? <em>(Check all that apply.)</em>
		</h5>
		<div class="col-xs-offset-1 col-sm-offset-0 col-sm-9 col-md-10" id="majors">
			<?php
				$groupedMajors = [];
				foreach ($majors as $m) {
					// The first regular expression extracts the group name from the major if present;
					// otherwise, it returns an empty string. The second regex removes any group name,
					// leaving purely the name of the major/program.
					$groupedMajors[preg_replace('/^\s*\[\s*([^\]]+)\s*\]|.?/', '\\1', $m)][] = [
							'name' => preg_replace('/^\s*\[[^\]]+\]\s*/', '', $m),
							'fullName' => $m
					];
				}
				ksort($groupedMajors);
				$partitions = 3;
				$defaultPartitionSize = ceil(count($groupedMajors['']) / ($partitions - (count($groupedMajors) - 1)));
				foreach ($groupedMajors as $groupName => $groupMs) {
					for ($i = 0; $i < (($groupName == '') ? ($partitions - (count($groupedMajors) - 1)) : 1); $i++) {
						echo '<div class="col-xs-offset-1 col-xs-11 col-sm-offset-0 col-sm-' .
								 floor(12 / ceil($partitions / 2)) . ' col-md-' . floor(12 / $partitions) . '">';
						echo '<div class="form-group">';
						if ($groupName == '') {
							$partitionsize = $defaultPartitionSize;
						} else {
							$partitionsize = count($groupMs);
							echo '<em>' . htmlspecialchars($groupName) . ':</em>';
						}
						for ($j = $i * $partitionsize; $j < min(($i * $partitionsize) + $partitionsize, count($groupMs)); $j++) {
							$majorsHTML = htmlspecialchars($groupMs[$j]['fullName']);
							$canonical = preg_replace('/^_/', '', preg_replace('/[^A-Za-z0-9]+/', '_', $majorsHTML));
							echo '<div class="checkbox">';
							echo '<label for="majors-'.$canonical.'">';
							echo '<input id="majors-' . $canonical .
									 '" name="majors[]" type="checkbox" value="' . $majorsHTML . '" ' .
									 (((!empty($_POST['majors'])) && (in_array($groupMs[$j]['fullName'], $_POST['majors']))) ? 'checked="checked"' : '') .
								 ' /> ' . $groupMs[$j]['name'];
							echo '</label><br />';
							echo '</div>';
						}
						echo '</div>';
						echo '</div>';
					}
				}
			?>
			<div class="col-xs-12">
				<div class="form-group">
					<label for="majors-other">All others (please list, comma-separated):</label>
					<br />
					<input id="majors-other" name="majors-other" type="text"
						value="<?php echo getOrNull($_POST['majors-other']); ?>" maxlength="200" />
				</div>
			</div>
		</div>
	</section>
	<section class="hidden-xs hidden-sm"></section>
	<div class="clearfix"></div>
	<div class="col-xs-12" id="submit-div">
		<input class="btn btn-primary" type="submit" value="Submit" />
	</div>
</form>
<?php
$template->appendtoBody(<<<'HTML'
	<script>
		$('#collegecredit-coursenames').parent().hide();
		$('#collegecredit-collegename').parent().hide();
		$(document).ready(function(){
			$('#collegecredit-boolean').change(requireCollegeCreditDetails);
			requireCollegeCreditDetails();
		});
		function requireCollegeCreditDetails() {
			if ($('#collegecredit-boolean').val() == '1') {
				$('#collegecredit-coursenames').attr('required', 'required');
				$('#collegecredit-collegename').attr('required', 'required');
				$('#collegecredit-coursenames').parent().show();
				$('#collegecredit-collegename').parent().show();
			} else {
				$('#collegecredit-coursenames').removeAttr('required');
				$('#collegecredit-collegename').removeAttr('required');
				$('#collegecredit-coursenames').parent().hide();
				$('#collegecredit-collegename').parent().hide();
			}
		}
	</script>
HTML
);
$template->printFooter();
?>
