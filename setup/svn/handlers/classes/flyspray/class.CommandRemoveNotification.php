<?php
/**
 * add_notification command
 * Adds the user $user_id to the notifications list of $tasks
 *
 */
class CommandAddNotification extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param int $user_id
	 * @param array $tasks
	 */
	public function __construct($user_id, array $tasks) {
		parent::__construct($user_id, $tasks);
	}
}
?>