<?php
/**
 * add_vote command
 * Adds a vote from $user_id to the task $task_id
 *
 */
class CommandCommandAddVote extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param int $user_id
	 * @param int $tasks_id
	 */
	public function __construct($user_id, $task_id) {
		parent::__construct($user_id, array($task_id));
	}
}
?>