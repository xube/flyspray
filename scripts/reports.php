<?php

  /********************************************************\
  | Show various reports on tasks                          |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                               |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once(BASEDIR . '/includes/events.inc.php');

class FlysprayDoReports extends FlysprayDo
{
    function is_accessible()
    {
        global $user;
        return $user->perms('view_reports');
    }

    function is_projectlevel() {
        return true;
    }

    function show()
    {
        global $page, $db, $user, $fs, $proj;

        $page->setTitle($fs->prefs['page_title'] . L('reports'));

        $events = array(1 => L('taskopened'),
                        13 => L('taskreopened'),
                        2 => L('taskclosed'),
                        3 => L('taskedited'),
                        14 => L('assignmentchanged'),
                        29 => L('events.useraddedtoassignees'),
                        4 => L('commentadded'),
                        5 => L('commentedited'),
                        6 => L('commentdeleted'),
                        7 => L('attachmentadded'),
                        8 => L('attachmentdeleted'),
                        11 => L('relatedadded'),
                        11 => L('relateddeleted'),
                        9 => L('notificationadded'),
                        10 => L('notificationdeleted'),
                        17 => L('reminderadded'),
                        18 => L('reminderdeleted'));

        $user_events = array(30 => L('created'),
                             31 => L('deleted'));

        $page->assign('events', $events);
        $page->assign('user_events', $user_events);

        $sort = strtoupper(Get::enum('sort', array('desc', 'asc')));

        $where = array();
        $params = array();
        $orderby = '';

        switch (Get::val('order')) {
            case 'type':
                $orderby = "h.event_type {$sort}, h.event_date {$sort}";
                break;
            case 'user':
                $orderby = "user_id {$sort}, h.event_date {$sort}";
                break;
            case 'date': default:
                $orderby = "h.event_date {$sort}, h.event_type {$sort}";
        }

        foreach (Get::val('events', array()) as $eventtype) {
            $where[] = 'h.event_type = ?';
            $params[] = $eventtype;
        }
        $where = '(' . implode(' OR ', $where) . ')';

        if ($proj->id) {
            $where = $where . 'AND (t.project_id = ?  OR h.event_type > 29) ';
            $params[] = $proj->id;
        }

        if ( ($fromdate = Req::val('fromdate')) || Req::val('todate')) {
                $where .= ' AND ';
                $todate = Req::val('todate');

                if ($fromdate) {
                    $where .= ' h.event_date > ?';
                    $params[] = Flyspray::strtotime($fromdate) + 0;
                }
                if ($todate && $fromdate) {
                    $where .= ' AND h.event_date < ?';
                    $params[] = Flyspray::strtotime($todate) + 86400;
                } else if ($todate) {
                    $where .= ' h.event_date < ?';
                    $params[] = Flyspray::strtotime($todate) + 86400;
                }
        }

        $histories = array();
        if (count(Get::val('events'))) {
            $histories = $db->SelectLimit("SELECT h.*, t.*, p.project_prefix
                                             FROM {history} h
                                        LEFT JOIN {tasks} t ON h.task_id = t.task_id
                                        LEFT JOIN {projects} p ON t.project_id = p.project_id
                                            WHERE $where
                                         ORDER BY $orderby",
                                          Get::num('event_number', -1), 0, $params);

            $histories = $histories->GetArray();
        }

        $page->assign('histories', $histories);
        $page->assign('sort', $sort);

        $page->pushTpl('reports.tpl');
    }
}

?>
