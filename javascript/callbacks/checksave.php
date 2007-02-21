<?php
/*
    Checks if a task can be saved without danger or not.
*/

define('IN_FS', true);

require_once('../../header.php');

$last_edit = $db->GetOne('SELECT last_edited_time FROM {tasks} WHERE task_id = ?', array(Get::val('taskid')));

if (Get::val('time') >= $last_edit) {
    echo 'ok';
}
?>
