<?php
   /**********************************************************\
   | This script moves data from {dbprefix)tasks.assigned_to   |
   | to {dbprefix}assigned.  This is to implement multiple     |
   | assignees per task as described in FS#329.  It only needs |
   | to be run once to do the conversion, but will be included |
   | in the index page until the 0.9.9 release.                |
   \***********************************************************/
   
define('IN_FS', true);

require_once '../includes/functions.inc.php';
require_once '../includes/constants.inc.php';
require_once BASEDIR . '/includes/db.inc.php';

$db = new Database;
$db->dbOpenFast($conf['database']);

$db->Query("ALTER TABLE `flyspray_assigned` DROP `user_or_group`");
$db->Query("ALTER TABLE `flyspray_assigned` CHANGE `assignee_id` `user_id` MEDIUMINT( 5 ) DEFAULT '0' NOT NULL");
$db->Query("ALTER TABLE `flyspray_assigned` ADD INDEX ( `task_id` , `user_id` )");
                          
$check_sql = $db->Query("SELECT task_id, assigned_to
                           FROM {tasks}
                          WHERE assigned_to > '0'");

while ($row = $db->FetchArray($check_sql))
{
   $db->Query("INSERT INTO {assigned}
                           (task_id, user_id)
                    VALUES (?,?)",
                           array($row['task_id'], $row['assigned_to']));

   $db->Query("UPDATE {tasks}
                  SET assigned_to = 0
                WHERE task_id = ?",
                      array($row['task_id']));
}

?>