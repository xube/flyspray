<?php

$sql = $db->Execute('SELECT * FROM {projects} WHERE project_prefix = ?', array(''));

$prcount = 1;
while ($row = $sql->FetchRow()) {
    $count = 1;
    $db->Execute('UPDATE {projects} SET project_prefix = ? WHERE project_id = ?', array('PR' . $prcount, $row['project_id']));
    $prcount++;

    $tasks = $db->Execute('SELECT * FROM {tasks} WHERE project_id = ? ORDER BY task_id ASC', array($row['project_id']));
    while ($task = $tasks->FetchRow()) {
        $db->Query('UPDATE {tasks} SET prefix_id = ? WHERE task_id = ?', array($count, $task['task_id']));
        $count++;
    }
}

?>