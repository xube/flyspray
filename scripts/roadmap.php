<?php
  /*********************************************************\
  | Show the roadmap                                        |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$proj->id) {
    Flyspray::show_error(25);
}

$page->setTitle($fs->prefs['page_title'] . L('roadmap'));

// Get milestones
$list_id = $db->GetOne('SELECT list_id FROM {fields} WHERE field_id = ?',
                        array($proj->prefs['roadmap_field']));

$milestones = $db->Execute('SELECT list_item_id AS version_id, item_name AS version_name
                            FROM {list_items} li
                           WHERE list_id = ? AND version_tense = 3
                        ORDER BY list_position ASC',
                          array($list_id));

$data = array();

while ($row = $milestones->FetchRow()) {
    // Get all tasks related to a milestone
    $all_tasks = $db->Execute('SELECT  percent_complete, is_closed
                               FROM  {tasks} t
                          LEFT JOIN  {field_values} fv ON (fv.task_id = t.task_id AND field_id = ?)
                              WHERE  field_value = ? AND project_id = ?',
                             array($proj->prefs['roadmap_field'], $row['version_id'], $proj->id));
    $all_tasks = $all_tasks->GetArray();

    $percent_complete = 0;
    foreach($all_tasks as $task) {
        if($task['is_closed']) {
            $percent_complete += 100;
        } else {
            $percent_complete += $task['percent_complete'];
        }
    }
    $percent_complete = round($percent_complete/max(count($all_tasks), 1));

    $tasks = $db->Execute('SELECT task_id, item_summary, detailed_desc, task_severity, mark_private, opened_by, content, task_token, t.project_id
                           FROM {tasks} t
                      LEFT JOIN {cache} ca ON (t.task_id = ca.topic AND ca.type = ? AND t.last_edited_time <= ca.last_updated)
                          WHERE closedby_version = ? AND t.project_id = ? AND is_closed = 0',
                         array('rota', $row['version_id'], $proj->id));
    $tasks = $tasks->GetArray();

    $data[] = array('id' => $row['version_id'], 'open_tasks' => $tasks, 'percent_complete' => $percent_complete,
                    'all_tasks' => $all_tasks, 'name' => $row['version_name']);
}

if (Get::val('txt')) {
    $page = new FSTpl;
    header('Content-Type: text/plain; charset=UTF-8');
    $page->uses('data', 'page');
    $page->display('roadmap.text.tpl');
    exit();
} else {
    $page->uses('data', 'page');
    $page->pushTpl('roadmap.tpl');
}
?>
