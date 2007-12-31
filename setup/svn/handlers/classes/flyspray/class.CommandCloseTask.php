<?php
/**
 * close_task command
 * Closes a task
 *
 */
class CommandCloseTask extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param int $task_id
	 * @param string $comment
	 * @param bool $mark100
	 */
	public function __construct($task_id, $comment, $mark100 = true) {
		$params = array();
		$params['comment'] = $comment;
		$params['mark100'] = $mark100;
		parent::__construct($task_id, $params);
	}
}
?>