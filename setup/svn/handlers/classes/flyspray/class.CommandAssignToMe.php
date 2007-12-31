<?php
/**
 * assign_to_me command
 * Assigns one or more $tasks only to a user $user_id
 *
 */
class CommandAssignToMe extends FlySprayCommand {
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