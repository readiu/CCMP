<!DOCTYPE html>
<html>
<head>
	<!-- This is for Sweet Alert Box -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert-dev.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
</head>


<?php
require_once 'config.php';
$template = new Template('Results - Math Placement Test', null);
$template->appendToHead('<link rel="stylesheet" href="mathplacement.css" type="text/css">');
$template->printHeader();
//howeh - 8/11/17 - CUI - start - format dates and only display the pop-up on the date the questionnaire was filled out
//Convert completed date from questionnarieData to a date - format it - format today's date - then compare them below
$dateCompleted = date_format(date_create($questionnaireData['Completed']),'Y-m-d');
$todaysDate = date('Y-m-d');

//howeh - 8/14/17 - CUI - start - pop up box if no exam is required
if($placementData['Exam'] == 'N' && $placementData['CompletedYN'] == 'Y' && ($dateCompleted == $todaysDate)){
	echo '<script>swal("No Test Required","Based on the information you provided in the questionnaire you do NOT need to take a placement test.\n\nClick OK to view your placement results.","success");</script>';
}
//howeh - 8/14/17 - CUI - end
?>

<!-- howeh - 8/10/17 - CUI - start - add CSS style section -->
<style>
und { 
    text-decoration: underline;
}
h1{
	text-align: center;
}
</style>
<!-- howeh - 8/10/17 - CUI - end -->

<h1><und>Math Placement Results</und></h1>
<div id="math-placement-results">
	<?php
	switch ($placementData['Placement']) {
		case '103':
			$level = 'A';
			$appropriateCourses = [
					'102',
					'105',
					'109',
					'115'
			];
			break;
		case '109':
			$level = 'B';
			$appropriateCourses = [
					'102',
					'105',
					'109',
					'115'
			];
			break;
		case '131':
			$level = 'C';
			$appropriateCourses = [
					'131',
					'215',
					'115'
			];
			break;
		case '132':
			$level = 'D';
			$appropriateCourses = [
					'132',
					'215',
					'115'
			];
			break;
		case '231':
			$level = 'E';
			$appropriateCourses = [
					'231',
					'215',
					'115'
			];
			break;
		default:
			$level = null;
			$appropriateCourses = [];
	}
	$courses = $conn->getAll(
			'SELECT TOP 1000 [CourseNumber], [CourseName], [CourseComment] ' .
					 'FROM [CourseComments];');
	if (($placementData['Placement'] == '131') || ($placementData['Placement'] == '132') ||
			 ($placementData['Placement'] == '231')) {
		$course = $courses[array_search($placementData['Placement'], 
				array_column($courses, 'CourseNumber'))];
		echo '<p><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> ' .
				 'Congratulations! ' .
				 'Based on your mathematics aptitude and background, you are an excellent fit for <strong>' .
				 $course['CourseName'] . ' (MATH-' . $course['CourseNumber'] . ')</strong>. ' .
				 'You are encouraged to enroll in this course to capitalize on your mathematical strengths. ' .
				 'However, you should consult with your advisor to determine the best course(s) for you.</p>';
	}
	if ($level != null) {
		$coursesForMajors = array_map(function ($r) {
			return array_map('trim', $r);
		}, $conn->getAll('SELECT TOP 100 * FROM [Courses_For_Majors]'.' WHERE [Enabled] = 1;'));
		// Generate array of all user-supplied majors which are also majors in the database (case-insensitive).
		$chosenMajors = array_filter(
				array_map('trim', 
						explode(',', 
								$questionnaireData['Major'] . ',' . $questionnaireData['OtherMajors'])), 
				function ($e) {
					return (!empty($e));
				});
		$potentialMajors = array_uintersect($coursesForMajors, 
				array_map(function ($m) {
					return [
							'Major' => $m
					];
				}, $chosenMajors), 
				function ($a, $b) {
					return strcasecmp($a['Major'], $b['Major']);
				});
		$maeInd = array_search('(Anything else)', array_column($coursesForMajors, 'Major'));
		if ($maeInd !== false) {
			global $majorAnythingElse;
			$majorAnythingElse = $coursesForMajors[$maeInd];
			$potentialMajors = array_merge($potentialMajors, 
					array_udiff(
							array_map(
									function ($m) {
										global $majorAnythingElse;
										return [
												'Major' => $m
										] + $majorAnythingElse;
									}, $chosenMajors), $potentialMajors, 
							function ($a, $b) {
								return strcasecmp($a['Major'], $b['Major']);
							}));
		}
		usort($potentialMajors, function($a, $b){
			return strcasecmp($a['Major'], $b['Major']);
		});
	}
	if (!empty($potentialMajors)) {
		//howeh - 8/14/17 - CUI - start - change this section because we changed Comments
		//to CommentA, CommentB, CommentC, CommentD, and CommentE in te Courses_For_Majors table
		/*
		$showComments = (count(
				array_filter(array_column($potentialMajors, 'Comments'), 
						function ($c) {
							return (!empty($c));
						})) > 0);
		*/
		$showComments = false;
		foreach ($potentialMajors as $m) {
			switch($GLOBALS['level']){
				case "A" && $m['CommentA']:
					$showComments = true;
					break;
				case "B" && $m['CommentB']:
					$showComments = true;
					break;
				case "C" && $m['CommentC']:
					$showComments = true;
					break;
				case "D" && $m['CommentD']:
					$showComments = true;
					break;
				case "E" && $m['CommentE']:
					$showComments = true;
					break;
			}
		}
		//howeh - 8/14/17 - CUI - end

		
	?>
	<div class="row">
		<div class="col-xs-12">
			<?php 
				if($placementData['Exam'] == 'N'){
					echo '<h3><p>Congratulations!  Based on the information you provided in the questionnaire you do <strong><und>NOT</und></strong> need to take a placement test.</p></h3><br/>';
				}
			?>
			<!-- howeh - 8/9/17 - CUI - start - moved to top, added <h1>, and updated text per Mark Mills -->
			<?php
			if ($level == 'A') {
				echo '<h3><p><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ' .
						 '<strong>Please note:</strong> ' . 'If you enroll in MATH-109 (<em>Precalculus</em>), ' .
						 'your placement results indicate that you will likely need to review algebra to successfully complete MATH-109. ' .
						 'However, Central College does <und><strong>NOT</strong></und> currently offer a college algebra course.  ' .
						 'Talk to an academic advisor and your MATH-109 instructor about this.</p></h3><br/>';
			}
			?>
			<!-- howeh - 8/9/17 - CUI - end -->
			<h5>
				<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> Based on the potential majors
				that you specified, here are specific course recommendations:
			</h5>
			<div class="table-responsive">
				<table class="table table-bordered">
					<thead>
						<tr>
							<th>Potential Major</th>
							<th>Recommended Course</th>
							<?php
							if ($showComments) {
							?>
							<th>Comment</th>
							<?php
							}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($potentialMajors as $m) {
							$courseInd = array_search($m[$level], array_column($courses, 'CourseNumber'));
							$course = (($courseInd !== false) ? $courses[$courseInd] : null);
							echo '<tr>';
							echo '<td>' . htmlspecialchars($m['Major']) . '</td>';
							echo '<td>' . (($course !== null) ? htmlspecialchars(
									'MATH-' . $course['CourseNumber'] . ': ' . $course['CourseName']) : '') . '</td>';
							//howeh - 8/14/17 - CUI - start - change this section because we changed Comments
							//to CommentA, CommentB, CommentC, CommentD, and CommentE in te Courses_For_Majors table
							/*
							if($showComments){
								echo '<td>' . htmlspecialchars($m['Comments']) . '</td>';
							}
							*/
							if($showComments){
								switch($GLOBALS['level']){
									case "A":
										echo '<td>' . htmlspecialchars($m['CommentA']) . '</td>';
										break;
									case "B":
										echo '<td>' . htmlspecialchars($m['CommentB']) . '</td>';
										break;
									case "C":
										echo '<td>' . htmlspecialchars($m['CommentC']) . '</td>';
										break;
									case "D":
										echo '<td>' . htmlspecialchars($m['CommentD']) . '</td>';
										break;
									case "E":
										echo '<td>' . htmlspecialchars($m['CommentE']) . '</td>';
										break;
									default:
										echo '<td></td>';
										break;
								}
							}
							//howeh - 8/14/17 - CUI - end
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
	}
	?>
	<h5>
		<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> Any of the courses shown
		below are appropriate for your placement level:
	</h5>
	<div class="table-responsive">
		<table class="table table-bordered">
			<thead>
				<tr>
					<th>Course</th>
					<th>Comment</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($appropriateCourses as $c) {
					$course = $courses[array_search($c, array_column($courses, 'CourseNumber'))];
					echo '<tr>';
					echo '<td>' . htmlspecialchars(
							'MATH-' . $course['CourseNumber'] . ': ' . $course['CourseName']) .
							 '</td>';
					echo '<td>' . htmlspecialchars($course['CourseComment']) . '</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	</div>
	<!-- howeh - 8/9/17 - CUI - start - move this up to the top of the page -->
	<?php
	//if ($level == 'A') {
	//	// TODO Replace this with text to be supplied by Dr. Mills.
	//	echo '<p><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> ' .
	//			 '<strong>Please note:</strong> ' . 'If you enroll in MATH-109 (<em>Precalculus</em>), ' .
	//			 'your placement results indicate that you will likely need to review algebra to successfully complete MATH-109. ' .
	//			 'Talk to your advisor and your MATH-109 instructor about this.</p>';
	//}
	?>
	<!-- howeh - 8/9/17 - CUI - end -->
	<h5>
		<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> Notes:
	</h5>
	<ul>
		<li>Because various majors have different requirements, students should consult the course catalog
		or their academic advisor for requirements and recommendations of mathematics (or MR) classes
		that should be taken for their intended major.</li>
		<li>If you place at a level where a calculus course is an appropriate course, then we encourage
		you to take this course as soon as you can. Your math skills will be fresher and this gives you
		the best chance for success.</li>
		<li>See the <em>Credit by Proficiency</em> section in the course catalog for information about
		additional course credit that may be awarded for placement in MATH-132 (<em>Calculus II</em>) or
		MATH-231 (<em>Multivariable Calculus</em>).
		</li>
	</ul>
	<p>
		<span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span> If you have any
		questions about this advice or about mathematics courses in general, please contact <a
			href="https://www.central.edu/faculty/mark-mills" target="_blank">Dr. Mark Mills</a> in the math
		department.
	</p>
	<div class="table-responsive">
		<table class="table table-bordered centered">
			<thead>
				<tr>
					<th>Name</th>
					<th>Student ID</th>
					<th>Test</th>
					<th>Score</th>
					<th>Level</th>
					<th>ACT Math</th>
					<th>ACT Comp.</th>
					<th>GPA</th>
					<th><em>Ignore</em></th>
					<th>Xfer</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo htmlspecialchars($placementData['FirstName'] . ' ' . $placementData['LastName']); ?></td>
					<td><?php echo htmlspecialchars(str_pad($placementData['CentralID'], 7, '0', STR_PAD_LEFT)); ?></td>
					<td><?php echo htmlspecialchars($placementData['Exam']); ?></td>
					<td><?php
					echo (((!empty($placementData['Score'])) || ($placementData['Score'] === 0.0)) ? $placementData['Score'] .
							 '&nbsp;/&nbsp;' . count(EXAMS[$placementData['Exam']]['answers']) : 'N/A');
					?></td>
					<td><?php echo htmlspecialchars($level); ?></td>
					<td><?php echo htmlspecialchars($placementData['MathACT']); ?></td>
					<td><?php echo htmlspecialchars($placementData['CompositeACT']); ?></td>
					<td><?php echo htmlspecialchars(round($placementData['HSGPA'], 2)); ?></td>
					<td><?php
					echo (($placementData['Distance'] !== null) ? htmlspecialchars(
							$placementData['Distance']) . ' ' : '') . '[' .
							 (($placementData['MinutesSpent'] !== null) ? $placementData['MinutesSpent'] : 'N/A') .
							 ']';
					?></td>
					<td><?php echo htmlspecialchars((($questionnaireData['CollegeCredit'] == '1')?'Y':'N')); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<?php
$template->printFooter();
?>
</html>