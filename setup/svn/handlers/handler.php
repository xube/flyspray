<?php
require_once('classes/class.FSConfig.php');
require_once('classes/class.SVNHooksConfig.php');
require_once('classes/flyspray/class.FlySprayCommand.php');
include_directory(SVNHooksConfig::get_instance()->root_dir . '/classes', true);

if (!isset($argv)) $argv = array();

dbg::get_instance()->write('argv: ' . var_export($argv, true));

$handler = SVNHooksHandlerFactory::get_handler($argv);

dbg::get_instance()->write('handler: ' . var_export($handler, true));

if (!is_null($handler)) {
	if ($handler->prepare()) $handler->send();
}

/**
 * Includes all files in $path
 *
 * @param string $path path to include folder
 * @param bool $include_nested include nested folders files
 */
function include_directory($path, $include_nested = false) {
	$exclude_dirs = array(".", "..", ".svn");
	if ($path[strlen($path) - 1] != '\\' && $path[strlen($path) - 1] != '/') $path .= '/';
	if (($h = opendir($path)) === false) {
		trigger_error("Error including directory: $path", E_USER_ERROR);
		return;
	}
	while ($file = readdir($h)) {
		$include_path = $path . $file;
		if (is_dir($include_path) && $include_nested && !in_array($file, $exclude_dirs)) include_directory($include_path, $include_nested);
		if (is_file($include_path)) require_once($include_path);
	}
	closedir($h);
}
?>