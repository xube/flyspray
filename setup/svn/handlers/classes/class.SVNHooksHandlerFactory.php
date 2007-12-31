<?php
class SVNHooksHandlerFactory {
	/**
	 * Gets hook handler class instance
	 *
	 * @param array $argv
	 * @return AbstractHandler
	 */
	public static function &get_handler(array $argv) {
		$handler_name = self::hook_name_to_handler_class(isset($argv[1]) ? $argv[1] : '');
		
		dbg::get_instance()->write('handler name: ' . $handler_name);
		
		if (class_exists($handler_name)) {
			unset($argv[0]);
			unset($argv[1]);
			$out = new $handler_name(array_values($argv));
		} else {
			$out = null;
		}
		
		return $out;
	}
	
	/**
	 * Converts hook name to hook handler class name
	 *
	 * @example post-commit -> PostCommitHandler
	 * @param string $hook_name
	 * @return string
	 */
	private static function hook_name_to_handler_class($hook_name) {
		$handler_name = preg_replace_callback("#-([[:alpha:]])#U", create_function('$matches', 'return strtoupper($matches[0]);'), $hook_name);
		$handler_name = ucfirst(str_replace("-", "", $handler_name));
		$handler_name .= 'Handler';
		
		return $handler_name;
	}
}
?>