<?php

$sql = $db->query("SELECT * FROM {projects} WHERE project_prefix = '' OR project_prefix IS NULL");

$prcount = 1;
while ($row = $sql->FetchRow()) {
    $count = 1;
    $db->x->execParam('UPDATE {projects} SET project_prefix = ? WHERE project_id = ?', array('PR' . $prcount, $row['project_id']));
    $prcount++;

    $tasks = $db->x->getAll('SELECT * FROM {tasks} WHERE project_id = ? ORDER BY task_id ASC', null, $row['project_id']);
    foreach ($tasks as $task) {
        $db->x->execParam('UPDATE {tasks} SET prefix_id = ? WHERE task_id = ?', array($count, $task['task_id']));
        $count++;
    }
}

?>
