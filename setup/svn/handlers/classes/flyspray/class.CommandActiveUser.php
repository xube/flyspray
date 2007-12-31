<?php
/**
 * Sets user active
 *
 */
class CommandActiveUser extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param string $user_name
	 */
	public function __construct($user_name) {
		parent::__construct($user_name);
	}
}
?>