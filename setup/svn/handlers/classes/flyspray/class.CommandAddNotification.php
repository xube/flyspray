<?php
/**
 * remove_notification command
 * Removes a user $user_id from the notifications list of $tasks
 *
 */
class CommandRemoveNotification extends FlySprayCommand {
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