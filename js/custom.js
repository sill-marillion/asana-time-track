$(document).ready(function(){
	
	$('#hide-completed').ready(function(){
		hideCompletedTasks(this.checked);
	});
	
	$('#hide-completed').change(function(){
	  hideCompletedTasks(this.checked);
	});
	
	function hideCompletedTasks(is_checked) {
		var c = is_checked ? 'none' : 'table-row';
	  $('tr.completed-task').css('display', c);
	}
	
});
