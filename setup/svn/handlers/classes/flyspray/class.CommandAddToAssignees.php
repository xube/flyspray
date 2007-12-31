<?php
/**
 * add_to_assignees command
 * Adds a user $user_name to the assignees of one or more $tasks
 *
 */
class CommandAddToAssignees extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param string $user_name
	 * @param array $tasks
	 */
	public function __construct($user_name, array $tasks) {
		parent::__construct($user_name, $tasks);
	}
}
?>