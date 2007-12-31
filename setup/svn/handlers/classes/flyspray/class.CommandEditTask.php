<?php
/**
 * edit_task command
 * Edits the task in $task using the parameters in $args
 *
 */
class CommandEditTask extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param int $task_id
	 * @param array $args
	 */
	public function __construct($task_id, array $args) {
		parent::__construct($task_id, $args);
	}
}
?>