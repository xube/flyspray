<?php
  /*********************************************************\
  | Show the roadmap                                        |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoRoadmap extends FlysprayDo
{
    function is_accessible()
    {
        global $proj, $user;

        return (bool) $proj->id && $proj->prefs['roadmap_field'] && $user->can_view_project($proj->id);
    }

    function is_projectlevel() {
        return true;
    }

    function show()
    {
        global $page, $db, $fs, $proj, $user;

        $page->setTitle($fs->prefs['page_title'] . L('roadmap'));

        // Get milestones
        $list_id = $db->x->GetOne('SELECT list_id FROM {fields} WHERE field_id = ?',
                                null, $proj->prefs['roadmap_field']);

        $milestones = $db->x->getAll('SELECT list_item_id AS version_id, item_name AS version_name
                                    FROM {list_items} li
                                   WHERE list_id = ? AND version_tense = 3
                                ORDER BY list_position ASC',
                                  null, $list_id);

        $data = array();

        foreach ($milestones as $row) {
            // Get all tasks related to a milestone
            $all_tasks = $db->x->getAll('SELECT  percent_complete, is_closed, t.*
                                       FROM  {tasks} t
                                  LEFT JOIN  {field_values} fv ON (fv.task_id = t.task_id AND field_id = ?)
                                      WHERE  field_value = ? AND project_id = ?',
                                     array($proj->prefs['roadmap_field'], $row['version_id'], $proj->id));
            $all_tasks = array_filter($all_tasks, array($user, 'can_view_task'));

            $percent_complete = 0;
            foreach($all_tasks as $task) {
                if($task['is_closed']) {
                    $percent_complete += 100;
                } else {
                    $percent_complete += $task['percent_complete'];
                }
            }
            $percent_complete = round($percent_complete/max(count($all_tasks), 1));

            if (count($all_tasks)) {
                $tasks = $db->x->getAll('SELECT t.task_id, item_summary, detailed_desc, mark_private, fs.field_value AS field' . $fs->prefs['color_field'] . ',
                                              opened_by, content, task_token, t.project_id, prefix_id
                                       FROM {tasks} t
                                  LEFT JOIN {cache} ca ON (t.task_id = ca.topic AND ca.type = ? AND t.last_edited_time <= ca.last_updated)
                                  LEFT JOIN {field_values} f ON f.task_id = t.task_id
                                  LEFT JOIN {field_values} fs ON (fs.task_id = t.task_id AND fs.field_id = ?)
                                      WHERE f.field_value = ? AND f.field_id = ? AND t.project_id = ? AND is_closed = 0', null,
                                     array('rota', $fs->prefs['color_field'], $row['version_id'], $proj->prefs['roadmap_field'], $proj->id));

                $data[] = array('id' => $row['version_id'], 'open_tasks' => $tasks, 'percent_complete' => $percent_complete,
                                'all_tasks' => $all_tasks, 'name' => $row['version_name']);
            }
        }

        if (Get::val('txt')) {
            $page = new FSTpl;
            header('Content-Type: text/plain; charset=UTF-8');
            $page->assign('data', $data);
            $page->display('roadmap.text.tpl');
            exit();
        } else {
            $page->assign('data', $data);
            $page->pushTpl('roadmap.tpl');
        }
    }
}


?>
