<?php

  /*********************************************************\
  | User Profile Edition                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if ($user->isAnon()) {
    Flyspray::show_error(13);
}

$page->pushTpl('myprofile.menu.tpl');

$areas = array('prefs', 'notes', 'notifs');

switch ($area = Get::enum('area', $areas, 'prefs'))
{
    case 'prefs':
        $page->assign('groups', Flyspray::ListGroups());
        $page->assign('all_groups', Flyspray::listallGroups());

        $page->assign('theuser', $user);

        $page->setTitle($fs->prefs['page_title'] . L('editmydetails'));
        break;

    case 'notifs':
        require_once(BASEDIR . '/includes/events.inc.php');
        $events_since = strtotime(Get::val('events_since', '-1 week'));
        $sql = $db->Execute('SELECT h.task_id
                             FROM {history} h
                        LEFT JOIN {tasks} t ON h.task_id = t.task_id
                        LEFT JOIN {notifications} n ON t.task_id = n.task_id
                            WHERE h.event_date > ? AND h.task_id > 0 AND n.user_id = ?
                                  AND event_type NOT IN (9,10,5,6,8,17,18)
                         GROUP BY h.task_id
                         ORDER BY h.event_date DESC',
                        array($events_since, $user->id));
        $tasks = $sql->GetArray();

        $task_events = array();
        foreach ($tasks as $task) {
            $sql = get_events($task['task_id'], 'AND event_type NOT IN (9,10,5,6,8,17,18) AND h.event_date > ' . $events_since, 'DESC');
            $task_events[$task['task_id']] = $sql->GetArray();
        }

        $page->uses('task_events', 'tasks');
        $page->setTitle($fs->prefs['page_title'] . L('mynotifications'));
        break;

    case 'notes':
        $sql = $db->Execute('SELECT * FROM {notes} WHERE user_id = ?', array($user->id));
        $page->assign('saved_notes', $sql->GetArray());

        if (Get::num('note_id') && Get::val('action') != 'deletenote') {
            $sql = $db->Execute('SELECT note_id, message_subject, message_body, n.last_updated, content
                                 FROM {notes} n
                            LEFT JOIN {cache} c ON note_id = topic AND type = ? AND n.last_updated < c.last_updated
                                WHERE user_id = ? AND note_id = ?',
                                array('note', $user->id, Get::num('note_id')));
            $page->assign('show_note', $sql->FetchRow());
        }
        break;
}

$page->pushTpl('myprofile.'. $area .'.tpl');
?>
