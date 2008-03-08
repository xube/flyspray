<?php
if (!defined('IN_FS')) {
	die('Do not access this file directly.');
}

define('API_ECODE_NONE', 0);
define('API_ECODE_JSON_NOT_INSTALLED', 999);

define('API_ECODE_JSON_DECODE', 1);
define('API_ECODE_COMMANDCAST', 2);
define('API_ECODE_BACKEND_NOT_PORFORM', 3);
define('API_ECODE_UNRECOGNIZED_COMMAND', 4);
define('API_ECODE_SET_ACTIVE_USER', 5);
define('API_ECODE_SELECT_TASK', 6);
define('API_ECODE_ADD_COMMENT', 7);
define('API_ECODE_CLOSE_TASK', 8);
define('API_ECODE_EDIT_TASK', 9);
define('API_ECODE_USERNAME_NOT_FOUND', 10);
define('API_ECODE_RESOLUTION_NOT_FOUND', 11);
define('API_ECODE_FIXED_NOT_FOUND', 12);
define('API_ECODE_FIELD_NOT_FOUND', 13);
define('API_ECODE_FIELD_VALUE_NOT_FOUND', 14);
define('API_ECODE_LIST_NOT_FOUND', 15);
define('API_ECODE_LIST_ITEM_NOT_FOUND', 16);

/**
 * FlySpray Api for backend functions
 *
 * @author Vladimir Garvardt
 *
 */
class FlysprayDoApi extends FlysprayDo {
	/**
	 * Checks module access
	 *
	 * @return int
	 */
	public function is_accessible() {
		global $user;
		return $user->perms('is_admin');
	}

	/**
	 * Processes all commands and outputs result
	 *
	 */
	public function show() {
		if (!class_exists('FlySprayCommand')) {
			$base = dirname(__FILE__);
			require_once($base . '/../includes/class.FlysprayCommand.php');
			require_once($base . '/../includes/class.FlysprayResponse.php');
		}

		$out = array();

		$cmds = $this->decode_commands();

		if (count($cmds)) {
			foreach ($cmds as &$cmd) {
				try {
					$ncmd = $this->normalize_command($cmd);
				} catch (Exception $exc) {
					$out[] = new FlySprayResponse(0, sprintf('%d - %s', $exc->getCode(), $exc->getMessage()));
				}
				$this->perform_command($ncmd, $out);
			}
		} elseif (($ncmd = $this->get_command_from_url()) instanceof FlySprayCommand) {
			$this->perform_command($ncmd, $out);
		} else {
			$out[] = new FlySprayResponse(0, sprintf('%d - No commands found', API_ECODE_NONE));
		}

		die(json_encode($out));
	}

	/**
	 * Decodes JSON encoded commands list
	 *
	 * @return array
	 */
	private function decode_commands() {
		$req = Req::val('commands', '[]');
		if (!function_exists('json_decode')) throw new Exception('JSON seems to be not installed', API_ECODE_JSON_NOT_INSTALLED);
		$out = json_decode($req);
		if ($out === false) throw new Exception('JSON decoding failed', API_ECODE_JSON_DECODE);
		return $out;
	}

	/**
	 * Normalizes command, cast stdClass to FlySprayCommand
	 *
	 * @param stdClass $cmd
	 * @return FlySprayCommand
	 */
	private function &normalize_command(stdClass $cmd) {
		$ncmd = new FlySprayCommand();
		if (!$ncmd->cast_from_stdclass($cmd)) throw new Exception('Failed to cast command', API_ECODE_COMMANDCAST);
		return $ncmd;
	}

	private function perform_command(FlySprayCommand &$ncmd, array &$out) {
		static $backend;
		if (is_null($backend)) $backend = new Backend();

		try {
			$result = $this->process_command($ncmd, $backend);
			if (is_null($result)) $result = sprintf('[%s] %s', $ncmd->id, $ncmd->action);
			$out[] = new FlySprayResponse(1, $result);
		} catch (Exception $exc) {
			$out[] = new FlySprayResponse(0, sprintf('%d - %s', $exc->getCode(), $exc->getMessage()));
		}
	}

	/**
	 * Normalizes task, casts to backend function args
	 *
	 * @param array $task
	 */
	private function normalize_task_to_args(&$task) {
		if (is_array($task['assigned_to'])) {
			$task['assigned_to'] = implode(';', $task['assigned_to']);
		}
	}

	private function &get_command_from_url() {
		$out = new FlySprayCommand();
		if (($action = Req::val('action', false)) === false) {
			$null = null;
			return $null;
		}

		$out->action = $action;
		$out->id = Req::val('id');

		switch ($out->action) {
			case 'close_task':
				$out->ids['comment'] = Req::val('comment', '');
				$out->ids['mark100'] = intval(Req::val('mark100', 1));
				break;
			case 'add_comment':
				$out->ids[0] = Req::val('text');
				break;
			case 'edit_task':
				$out->ids[Req::val('param', '___')] = Req::val('value');
				break;
			case 'assign_to_me':
				$ids_str = Req::val('task_ids', '');
				$ids_arr = explode(',', $ids_str);
				$out->ids = $ids_arr;
				break;
			case 'get_task_details':
				break;
		}

		return $out;
	}

	/**
	 * Processes single command and performs some backend action
	 *
	 * @param FlySprayCommand $cmd
	 * @param Backend $backend
	 * @return mixed
	 */
	private function process_command(FlySprayCommand $cmd, Backend &$backend) {
		$not_cackend_commands = array('active_user', 'get_task_details');

		if (!in_array($cmd->action, $not_cackend_commands) && !method_exists($backend, $cmd->action)) {
			throw new Exception('Needed action is not performed in Backend', API_ECODE_BACKEND_NOT_PORFORM);
		}

		global $db;

		switch ($cmd->action) {
			case 'active_user':
				// CommandActiveUser
				global $user;

				if ($user->infos['user_name'] != $cmd->id) {
					$user_id = $db->x->GetOne('SELECT user_id FROM {users} WHERE user_name = ?', null, $cmd->id);
					if (intval($user_id) > 0) {
						$user = new User($user_id);
					} else {
						throw new Exception('ActiveUser can not be set', API_ECODE_SET_ACTIVE_USER);
					}
				}
				break;
					
			case 'add_comment':
				// CommandAddComment
				if (!($task = Flyspray::GetTaskDetails($cmd->id))) {
					throw new Exception('Cannot select task', API_ECODE_SELECT_TASK);
				}
				if (!($cid = $backend->add_comment($task, $cmd->ids[0]))) {
					throw new Exception('Failed adding comment', API_ECODE_ADD_COMMENT);
				}
				return $cid;

			case 'close_task':
				// CommandCloseTask
				try {
					$list_item_id = $this->get_list_item_id('resolution', 'fixed');
				} catch(Exception $exc) {
					throw new Exception($exc->getMessage(), API_ECODE_CLOSE_TASK);
				}

				if (!$backend->close_task($cmd->id, $list_item_id, $cmd->ids['comment'], $cmd->ids['mark100'])) {
					throw new Exception('Cannot close task', API_ECODE_CLOSE_TASK);
				}
				break;

			case 'edit_task':
				// CommandEditTask

				// default task fields, another fields should be treated as custom fields
				$def_fields = array('item_summary', 'project_id', 'percent_complete', 'assignedto');

				if (!($task = Flyspray::GetTaskDetails($cmd->id))) {
					throw new Exception('Cannot select task', API_ECODE_SELECT_TASK);
				}

				$args = $task;
				$this->normalize_task_to_args($args);
				foreach ($cmd->ids as $param_key => $param_value) {
					if (in_array($param_key, $def_fields)) {
						$args[$param_key] = $param_value;
					} else {
						if (!($field = $db->x->getRow('SELECT * FROM {fields} WHERE field_name = ?', null, $param_key))) {
							throw new Exception('Cannot find custom field row for ' . $param_key, API_ECODE_FIELD_NOT_FOUND);
						}
						if (!($field_val = $db->x->getRow("SELECT * FROM {list_items} WHERE list_id = ? AND item_name LIKE '%{$param_value}%'", null, $field['list_id']))) {
							throw new Exception(sprintf('Cannot find field item row for %s like %s', $param_key, $param_value), API_ECODE_FIELD_VALUE_NOT_FOUND);
						}

						$args['field' . $field['field_id']] = $field_val['list_item_id'];
					}
				}

				list($status_code, $msg) = $backend->edit_task($task, $args);
				if ($status_code != SUBMIT_OK) {
					throw new Exception(sprintf('Failed editing task, status code is "%d", message is "%s"', $status_code, strval($msg)), API_ECODE_EDIT_TASK);
				}
				break;

			case 'assign_to_me':
				// CommandAssignToMe
				$user_to_assign = $db->x->getRow('SELECT * FROM {users} WHERE user_name = ?', null, $cmd->id);
				if (!$user_to_assign) {
					throw new Exception(sprintf('Username "%s" is not found', $cmd->id), API_ECODE_USERNAME_NOT_FOUND);
				}
				$backend->assign_to_me($user_to_assign['user_id'], $cmd->ids);
				break;

			case 'get_task_details':
				if (!($task = Flyspray::GetTaskDetails($cmd->id))) {
					throw new Exception('Cannot select task', API_ECODE_SELECT_TASK);
				}
				return $task;

			default:
				throw new Exception('Unrecognized command ' . $cmd->action, API_ECODE_UNRECOGNIZED_COMMAND);
		}
	}

	/**
	 * Gets list item ID by list name and list item name
	 *
	 * @param string $list_name
	 * @param string $item_name
	 * @return int
	 */
	private function get_list_item_id($list_name, $item_name) {
		global $db;

		if (!($res_row = $db->x->getRow('SELECT * FROM {lists} WHERE list_name = ?', null, $list_name))) {
			throw new Exception('Cannot find list row with name ' . $list_name, API_ECODE_LIST_NOT_FOUND);
		}
		if (!($item_row = $db->x->getRow("SELECT * FROM {list_items} WHERE list_id = ? AND item_name LIKE '%{$item_name}%'", null, $res_row['list_id']))) {
			throw new Exception('Cannot find list item row with name ' . $item_name, API_ECODE_LIST_ITEM_NOT_FOUND);
		}

		return $item_row['list_item_id'];
	}
}
?>
