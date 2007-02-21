<?php
/*
    Checks if a related tasks belongs to a different project.
*/

define('IN_FS', true);

require_once('../../header.php');

$relatedproject = $db->GetOne('SELECT  project_id
                                 FROM  {tasks}
                                WHERE  task_id = ?',
                               array(Get::val('related_task')));

if (Get::val('project') == $relatedproject || !$relatedproject) {
    echo 'ok';
}
?>
