var pageLoadEpoch;

$(document).ready(function(){
	pageLoadEpoch = Math.floor((new Date()).getTime() / 1000);
	
	// Detect submit button: http://stackoverflow.com/a/5721762
	$('#math-placement-test input[type=submit]').click(function(){
		clearClicked();
		$(this).attr('clicked', 'true');
	});
	
	$('#math-placement-test').submit(testSubmit);
	$('.math-q-answer').change(function(){
		saveProgress(true, false);
	});
	
	updateMinsRemain();
	$('#timeRemaining').css('display', 'inline');
});

function clearClicked() {
	$('#math-placement-test input[type=submit]').removeAttr('clicked')
}

function updateMinsRemain(fromTimeout) {
	var secsElapsed = Math.floor((new Date()).getTime() / 1000) - pageLoadEpoch;
	var secsRemain = secsRemainAtLoad - secsElapsed;
	$('#minsRemain').text(Math.max(0, Math.floor(secsRemain / 60)));
	if (secsRemain <= 300) {
		// Less than 5 mintues left
		$('#timeInfo').css('color', '#ff0000');
	} else if (secsRemain <= 600) {
		// Less than 10 minutes left
		$('#timeInfo').css('color', '#ff8c00');
	}
	var updateInterval;
	if (secsRemain > 60) {
		updateInterval = 60;
	} else {
		updateInterval = 30;
		if ((secsRemain <= 0) && (fromTimeout)) {
			clearClicked();
			$('#math-placement-test').submit();
		}
	}
	var nextUpdate = Math.abs(secsRemain) % updateInterval;
	if (secsRemain < 0) {
		nextUpdate = updateInterval - nextUpdate;
	}
	window.setTimeout(function(){
		updateMinsRemain(true);
	}, (nextUpdate + 1) * 1000);
	saveProgress(false, false);
}

function testSubmit(e) {
	var submitButton = $('#math-placement-test input[type=submit][clicked=true]').attr('name');
	if (submitButton == 'saveProgress') {
		e.preventDefault();
		saveProgress(true, false);
		return false;
	} else if (submitButton == 'submitFinal') {
		e.preventDefault();
		saveProgress(true, false);
		var qs = $('.math-q-answer');
		var unanswered = qs.map(function(q){
			if ($(qs[q]).val() == '') {
				return '#<strong>' + $(qs[q]).attr('name').replace(/[^0-9]/ig, '')  + '</strong>';
			}
		}).toArray();
		if (unanswered.length >= 1) {
			var unansweredStr;
			if (unanswered.length >= 2) {
				unanswered[unanswered.length - 1] = 'and ' + unanswered[unanswered.length - 1];
				if (unanswered.length >= 3) {
					unansweredStr = unanswered.join(', ');
				} else {
					unansweredStr = unanswered.join(' ');
				}
				unansweredStr = 'questions ' + unansweredStr;
			} else {
				unansweredStr = 'question ' + unanswered[0];
			}
			$('#unanswered-question-nums').html(unansweredStr);
			$('#unanswered').show();
		} else {
			$('#unanswered-question-nums').html('');
			$('#unanswered').hide();
		}
		$('#math-placement-finish-modal').modal('show');
		return false;
	}
}

function saveProgress(somethingChanged, doneWithTest) {
	if (somethingChanged) {
		$('#saveProgress').prop('disabled', false);
		$('#saveProgress').val('Save progress');
	}
	var formData = {
			'ajax': true
	};
	$('.math-q-answer').each(function(){
		formData[$(this).attr('name')] = $(this).val();
	});
	if (!doneWithTest) {
		formData['saveProgress'] = true;
	} else {
		formData['submitFinal'] = true;
	}
	$.ajax({
		data: formData,
		method: 'POST',
		timeout: 30000,
		dataType: 'json',
		success: function(data, status, jqXHR){
			if (data['location'] != null) {
				window.location = data['location'];
			} else if (data['math-placement-test']['saved'] == true) {
				$('#saveProgress').prop('disabled', true);
				$('#saveProgress').val('Progress saved');
				if (data['math-placement-test']['secsRemain'] != null) {
					secsRemainAtLoad = data['math-placement-test']['secsRemain'];
					pageLoadEpoch = Math.floor((new Date()).getTime() / 1000);
				}
			}
		}
	});
}
