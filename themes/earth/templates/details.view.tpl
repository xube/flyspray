<h1 class="summary severity{$task['task_severity']}">
	{$task['item_summary']}
</h1>

<!-- Task Details/Description with attatchments if needed -->
<p id="taskdetailstext">{!$task_text}</p>

<?php
	$attachments = $proj->listTaskAttachments( $task['task_id'] );
	$this->display( 'common.attachments.tpl', 'attachments', $attachments );
?>