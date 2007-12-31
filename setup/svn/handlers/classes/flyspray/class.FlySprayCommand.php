<?php
/**
 * FlySpray abstract command
 *
 */
class FlySprayCommand {
	/**
	 * FlySpray backend function
	 *
	 * @var string
	 */
	public $action;
	/**
	 * Single action ID
	 *
	 * @var mixed
	 */
	public $id;
	/**
	 * IDs
	 *
	 * @var array
	 */
	public $ids;

	/**
	 * Constuctor
	 *
	 * @param mixed $user
	 * @param array $ids
	 */
	public function __construct($id = "", array $ids = array()) {
		$this->id = $id;
		$this->ids = $ids;

		// CommandSomeAction -> some_action
		// CommandSomeAction
		$this->action = get_class($this);
		// SomeAction
		$this->action = str_replace("Command", "", $this->action);
		// _some_action
		$this->action = preg_replace_callback("#([[:upper:]])#U", create_function('$matches', 'return "_" . strtolower($matches[0]);'), $this->action);
		// some_action
		$this->action = substr($this->action, 1);
	}

	/**
	 * Fills object properties from stdClass representation of the object
	 *
	 * @param stdClass $cmd
	 * @return bool
	 */
	public function cast_from_stdclass(stdClass $cmd) {
		$fscmd_props = get_class_vars(get_class($this));
		foreach ($fscmd_props as $prop => $value) {
			if (!property_exists($cmd, $prop)) return false;
				
			if (is_object($cmd->$prop)) $this->$prop = get_object_vars($cmd->$prop);
			else $this->$prop = $cmd->$prop;
		}
		return true;
	}
}
?>