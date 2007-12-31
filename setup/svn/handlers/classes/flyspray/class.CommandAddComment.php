<?php
/**
 * add_comment command
 * Adds a comment to $task
 *
 */
class CommandAddComment extends FlySprayCommand {
	/**
	 * Constructor
	 *
	 * @param int $task_id
	 * @param string $comment
	 */
	public function __construct($task_id, $comment) {
		parent::__construct($task_id, array($comment));
	}
}
?>