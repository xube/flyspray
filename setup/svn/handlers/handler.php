<?php
require_once('classes/class.FSConfig.php');
require_once('classes/class.SVNHooksConfig.php');
require_once('classes/flyspray/class.FlySprayCommand.php');
require_once('classes/class.SVNRevisionWrapper.php');
require_once('classes/class.dbg.php');
require_once('classes/class.SVNHooksHandlerFactory.php');

require_once('classes/handlers/class.AbstractHandler.php');
require_once('classes/handlers/class.PostCommitHandler.php');

require_once('classes/flyspray/class.CommandAddVote.php');
require_once('classes/flyspray/class.CommandAddUserToGroup.php');
require_once('classes/flyspray/class.CommandCloseTask.php');
require_once('classes/flyspray/class.CommandEditTask.php');
require_once('classes/flyspray/class.CommandAssignToMe.php');
require_once('classes/flyspray/class.FlySprayResponse.php');
require_once('classes/flyspray/class.CommandActiveUser.php');
require_once('classes/flyspray/class.CommandAddComment.php');
require_once('classes/flyspray/class.CommandAddToAssignees.php');
require_once('classes/flyspray/class.CommandAddNotification.php');
require_once('classes/flyspray/class.CommandRemoveNotification.php');



if (!isset($argv)) $argv = array();

dbg::get_instance()->write('argv: ' . var_export($argv, true));

$handler = SVNHooksHandlerFactory::get_handler($argv);

dbg::get_instance()->write('handler: ' . var_export($handler, true));

if (!is_null($handler)) {
	if ($handler->prepare()) 
    $handler->send();
}

?>
