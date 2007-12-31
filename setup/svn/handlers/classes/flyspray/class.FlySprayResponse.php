<?php
/**
 * FlySpray response wraper
 *
 */
class FlySprayResponse {
	/**
	 * Response code
	 *
	 * @var int
	 */
	public $code;
	/**
	 * Response result
	 *
	 * @var mixed
	 */
	public $result;

	/**
	 * Constructor
	 *
	 * @param int $code
	 * @param mixed $result
	 */
	public function __construct($code, $result) {
		$this->code = $code;
		$this->result = $result;
	}

	/**
	 * Fills object properties from stdClass representation of the object
	 *
	 * @param stdClass $response
	 * @return bool
	 */
	public function cast_from_stdclass(stdClass $response) {
		$fscmd_props = get_class_vars(get_class($this));
		foreach ($fscmd_props as $prop => $value) {
			if (!property_exists($response, $prop)) return false;

			if (is_object($response->$prop)) $this->$prop = get_object_vars($response->$prop);
			else $this->$prop = $response->$prop;
		}
		return true;
	}
}
?>