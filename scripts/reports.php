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

    function show()
    {
        global $page, $db, $user, $fs, $proj;

        $page->setTitle($fs->prefs['page_title'] . L('reports'));

        $events = array(1 => L('opened'),
                        13 => L('reopened'),
                        2 => L('closed'),
                        3 => L('edited'),
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

        if ( ($fromdate = Get::val('fromdate')) || Req::val('todate')) {
                $where .= ' AND ';
                $ufromdate = Flyspray::strtotime($fromdate) + 0;
                $todate = Get::val('todate');
                $utodate   = Flyspray::strtotime($todate) + 86400;

                if ($fromdate) {
                    $where .= ' h.event_date > ?';
                    $params[] = $ufromdate;
                }
                if ($todate && $fromdate) {
                    $where .= ' AND h.event_date < ?';
                    $params[] = $utodate;
                } else if ($todate) {
                    $where .= ' h.event_date < ?';
                    $params[] = $utodate;
                }
        }

        if (count(Get::val('events'))) {
            $histories = $db->SelectLimit("SELECT h.*, t.*
                                             FROM {history} h
                                        LEFT JOIN {tasks} t ON h.task_id = t.task_id
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
