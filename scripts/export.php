<?php
  /*********************************************************\
  | Export the tasklist                                     |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

/**
 * FlysprayDoExport 
 * 
 * @uses FlysprayDo
 * @package 
 * @version $Id$
 * @copyright 2007
 * @author Florian Schmitz <floele@flyspray.org> 
 */

class FlysprayDoExport extends FlysprayDo
{
    /**
     * is_accessible 
     * 
     * @access public
     * @return bool
     */
    function is_accessible()
    {
        global $user, $proj;
        if (!$user->can_view_project($proj->id)) {
            $proj = new Project(0);
        }
        return true;
    }

    /**
     * is_projectlevel 
     * 
     * @access public
     * @return void
     */
    function is_projectlevel() {
        return true;
    }

    /**
     * show 
     * 
     * @access public
     * @return void
     */
    function show()
    {
        global $proj, $page, $fs;
        // Get the visibility state of all columns
        $visible = explode(' ', trim($proj->id ? $proj->prefs['visible_columns'] : $fs->prefs['visible_columns']));

        list($tasks, $id_list) = Backend::get_task_list($_GET, $visible, 0);
        $page = new FSTpl;
        $page->assign('tasks', $tasks);
        $page->assign('visible', $visible);
        
        if (Get::val('type') == 'iCal') {
            $datecols = array(
                'dateopened' => 'date_opened',
                'lastedit'   => 'max_date',
                'dateclosed' => 'date_closed',
            );
            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: filename="export.ics"');
            $page->assign('datecols', $datecols);
            $page->finish('icalexport.tpl');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: filename="export.csv"');
            $page->finish('csvexport.tpl');
        }
    }
}

// Data for a cell
function tpl_csv_cell($task, $colname) {
    global $fs, $proj;

    $indexes = array (
            'id'         => 'task_id',
            'project'    => 'project_title',
            'summary'    => 'item_summary',
            'dateopened' => 'date_opened',
            'openedby'   => 'opened_by_real_name',
            'closedby'   => 'closed_by_real_name',
            'changedby'   => 'last_changed_by_real_name',
            'assignedto' => 'assigned_to_name',
            'lastedit'   => 'last_changed_time',
            'comments'   => 'comment_count',
            'votes'      => 'vote_count',
            'attachments'=> 'attachment_count',
            'dateclosed' => 'date_closed',
            'projectlevelid' => 'prefix_id',
            'progress'   => '',
            'state'      => '',
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