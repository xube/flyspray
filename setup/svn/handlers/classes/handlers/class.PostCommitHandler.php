<?php
/**
 * Post-commit hook handler
 *
 */
class PostCommitHandler extends AbstractHandler {
	/**
	 * Constructor
	 *
	 * @param array $params
	 */
	public function __construct(array $params) {
		parent::__construct($params[0], $params[1]);
	}

	/**
	 * Prepares commands list
	 *
	 * @return bool
	 */
	public function prepare() {
		$conf = SVNHooksConfig::get_instance();

		$ex_str = sprintf("%s info %s -r %s 2>&1", $conf->svnlook, $this->repos, $this->rev);
		exec($ex_str, $out, $code);

		dbg::get_instance()->write('PostCommitHandler::prepare exec result: ' . $code);
		dbg::get_instance()->write('PostCommitHandler::prepare exec output: ' . var_export($out, true));

		if ($code) return false;

		$this->revision_wrapper = new SVNRevisionWrapper($this->rev, $out);

		dbg::get_instance()->write('PostCommitHandler::prepare SVNRevisionWrapper: ' . var_export($this->revision_wrapper, true));

		$this->commands[] = new CommandActiveUser($this->revision_wrapper->author);
		if (!$this->parse_comment_into_commands()) return false;

		return true;
	}

	/**
	 * Parses commit comment into list of commands
	 *
	 * @return bool
	 */
	private function parse_comment_into_commands() {
		// command format:
		// [id1[, id2, id3]] command:arguments
		foreach ($this->revision_wrapper->comment_arr as $string) {
			if (preg_match_all("#^\[((\\d+\, )*(\\d+){1})](.*)#", $string, $matches)) {
				preg_match_all("#\\d+#", $matches[1][0], $matches_ids);
				$ids = $matches_ids[0];
				$command = trim($matches[4][0]);

				if (preg_match("#(\\w+):(.*)#i", $command, $cmd_args)) {
					$arg = trim($cmd_args[2]);
					$field = trim($cmd_args[1]);
						
					switch ($field) {
						// close:comment text
						case "close": $this->command_close($ids, $arg); break;
						// progress:percent
						case "progress": $this->command_progress($ids, $arg); break;
						// comment:comment text
						case "comment": $this->command_comment($ids, $arg); break;
						// status:new status (testing, assigned etc.)
						case "status": $this->command_custom_edit($ids, $field, $arg); break;
						// assign:new assignees
						case "assign": $this->command_assign($ids, $arg); break;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Prepares "close task" commands
	 *
	 * @param array $tasks_ids
	 * @param string $close_comment
	 */
	private function command_close(array $tasks_ids, $close_comment) {
		foreach ($tasks_ids as $id) {
			$this->commands[] = new CommandCloseTask($id, $close_comment);
		}
	}

	/**
	 * Prepares "task progress" command
	 *
	 * @param array $tasks_ids
	 * @param mixed $percent
	 */
	private function command_progress(array $tasks_ids, $percent) {
		$progress = intval($percent);
		foreach ($tasks_ids as $task_id) {
			if ($progress >= 0 && $progress <= 100) {
				$this->commands[] = new CommandEditTask($task_id, array('percent_complete' => $progress));
			}
		}
	}

	/**
	 * Prepares "task comment" command
	 *
	 * @param array $tasks_ids
	 * @param string $comment
	 */
	private function command_comment(array $tasks_ids, $comment) {
		if (strlen($comment)) {
			foreach ($tasks_ids as $id) {
				$this->commands[] = new CommandAddComment($id, $comment);
			}
		}
	}

	/**
	 * Prepares "edit custom field" command
	 *
	 * @param array $tasks_ids
	 * @param string $field
	 * @param string $value
	 */
	private function command_custom_edit(array $tasks_ids, $field, $value) {
		if (strlen($field) && strlen($value)) {
			foreach ($tasks_ids as $task_id) {
				$this->commands[] = new CommandEditTask($task_id, array($field => $value));
			}
		}
	}

	/**
	 * Prepares "assign task to user" command
	 *
	 * @param array $tasks_ids
	 * @param string $user_name
	 */
	private function command_assign(array $tasks_ids, $user_name) {
		$user_name = trim($user_name);
		if (strlen($user_name)) {
			$this->commands[] = new CommandAssignToMe($user_name, $tasks_ids);
		}
	}
}
?>