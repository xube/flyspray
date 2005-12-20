<?php
  /*********************************************************\
  | Show the roadmap                                        |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

$fs->get_language_pack('details');
$fs->get_language_pack('roadmap');
$page->setTitle('Flyspray :: ' . $roadmap_text['roadmap']);

// Get milestones
$milestones = $db->Query('SELECT   version_id, version_name
                          FROM     {list_version}
                          WHERE    project_id = ? AND version_tense = 3
                          ORDER BY list_position ASC',
                          array($proj->id));
                          
$data = array();

while ($row = $db->FetchArray($milestones)) {
    // Get all tasks related to a milestone
    $all_tasks = $db->Query('SELECT  percent_complete, is_closed
                             FROM    {tasks}
                             WHERE   closedby_version = ? AND attached_to_project = ?',
                             array($row['version_id'], $proj->id));
    $all_tasks = $db->fetchAllArray($all_tasks);
    
    $percent_complete = 0;
    foreach($all_tasks as $task) {
        if($task['is_closed']) {
            $percent_complete += 100;
        } else {
            $percent_complete += $task['percent_complete'];
        }
    }
    $percent_complete = round($percent_complete/max(count($all_tasks),1));
                         
    $tasks = $db->Query('SELECT  task_id, detailed_desc, task_severity
                         FROM    {tasks}
                         WHERE   closedby_version = ? AND attached_to_project = ? AND is_closed = 0',
                         array($row['version_id'], $proj->id));
    $tasks = $db->fetchAllArray($tasks);
    
    $data[] = array('open_tasks' => $tasks, 'percent_complete' => $percent_complete,
                    'all_tasks' => $all_tasks, 'name' => $row['version_name']);
}

$page->uses('data');
$page->uses('details_text');
$page->uses('roadmap_text');
$page->pushTpl('roadmap.tpl');
?>