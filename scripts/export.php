<?php
  /*********************************************************\
  | Export the tasklist                                     |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoExport extends FlysprayDo
{
    function is_accessible()
    {
        global $user, $proj;
        if (!$user->can_view_project($proj->id)) {
            $proj = new Project(0);
        }
        return true;
    }

    function show()
    {
        global $proj, $page, $fs;
        // Get the visibility state of all columns
        $visible = explode(' ', trim($proj->id ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']));

        list($tasks, $id_list) = Backend::get_task_list($_GET, $visible, 0);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: filename="export.csv"');
        $page = new FSTpl;
        $page->assign('tasks', $tasks);
        $page->assign('visible', $visible);
        $page->display('csvexport.tpl');
        exit(); // no footer please
    }
}

// Data for a cell
function tpl_csv_cell($task, $colname) {
    global $fs, $proj;

    $indexes = array (
            'id'         => 'task_id',
            'project'    => 'project_title',
            'severity'   => '',
            'summary'    => 'item_summary',
            'dateopened' => 'date_opened',
            'openedby'   => 'opened_by_name',
            'assignedto' => 'assigned_to_name',
            'lastedit'   => 'max_date',
            'comments'   => 'num_comments',
            'votes'      => 'num_votes',
            'attachments'=> 'num_attachments',
            'dateclosed' => 'date_closed',
            'projectlevelid' => 'prefix_id',
            'progress'   => '',
    );

    switch ($colname) {
        case 'id':
            $value = $task['task_id'];
            break;
        case 'projectlevelid':
            $value = $task['project_prefix'] . '#' . $task['prefix_id'];
            break;
        case 'summary':
            $value = $task['item_summary'];
            break;

        case 'severity':
            $value = $fs->severities[$task['task_severity']];
            break;

        case 'dateopened':
        case 'dateclosed':
        case 'lastedit':
            $value = formatDate($task[$indexes[$colname]]);
            break;

        case 'progress':
            $value = $task['percent_complete'] . '%';
            break;

        case 'assignedto':
            $value = $task[$indexes[$colname]];
            if ($task['num_assigned'] > 1) {
                $value .= ', +' . ($task['num_assigned'] - 1);
            }
            break;

        case 'state':
            if ($task['is_closed']) {
                $value = L('closed');
            } elseif ($task['closed_by']) {
                $value = L('reopened');
            } else {
                $value = L('open');
            }
            break;

        default:
            if (isset($indexes[$colname])) {
                $value = $task[$indexes[$colname]];
            } elseif (isset($task[$colname . '_name'])) {
                $value = $task[$colname . '_name'];
            } else if (isset($proj->fields[$colname])) {
                $value = $proj->fields[$colname]->view($task, array(), true);
            } else {
                $value = $task[$colname];
            }
            break;
    }

    return str_replace(array(';', '"'), array('\;', '\"'), $value);
}

?>