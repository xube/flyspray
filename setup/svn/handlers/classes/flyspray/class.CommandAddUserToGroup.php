<?php
/**
 * add_user_to_group command
 * Adds one or more users to a group
 *
 */
class CommandAddUserToGroup extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param int $group_id
	 * @param array $users
	 */
	public function __construct($group_id, array $users) {
		parent::__construct($group_id, $users);
	}
}
?>