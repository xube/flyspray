<?php
/**
 * Debug file log writer
 *
 */
class dbg {
	/**
	 * File pointer
	 *
	 * @var resource
	 */
	private $fp;
	/**
	 * Debugging enabled
	 *
	 * @var bool
	 */
	private $enabled;
	/**
	 * Singleton instance
	 *
	 * @var dbg
	 */
	private static $_instance;

	/**
	 * Gets the only class instance
	 *
	 * @return dbg
	 */
	public static function get_instance() {
		if (!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c;
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 */
	private function __construct() {
		$conf = SVNHooksConfig::get_instance();
		
		$this->enabled = $conf->debug;
		
		if ($this->enabled) $this->fp = fopen($conf->root_dir . '/dbg.txt', 'a');
	}
	
	/**
	 * Destructor
	 *
	 */
	public function __destruct() {
		if ($this->enabled) {
			fwrite($this->fp, "===================================\r\n");
			fclose($this->fp);
		}
	}
	
	/**
	 * Write new debug entry
	 *
	 * @param string $str
	 */
	public function write($str) {
		if ($this->enabled && $this->fp !== false) fwrite($this->fp, date("Y-m-d G:i:s\r\n") . strval($str) . "\r\n\r\n");
	}
}
?>