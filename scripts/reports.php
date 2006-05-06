<?php

  /********************************************************\
  | Show various reports on tasks                          |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                               |
  \********************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (!$user->perms['view_reports']) {
    Flyspray::Redirect($baseurl);
}

require_once(BASEDIR . '/includes/events.inc.php');
$page->setTitle('Flyspray:: ' . L('reports'));

/**********************\
*  Event reports       *
\**********************/

$sort = strtoupper(Req::val('sort', 'desc'));

switch (Req::val('order')) {
    case 'id':
        $orderby = "h.task_id {$sort}, h.event_date {$sort}";
        break;
    case 'type':
        $orderby = "h.event_type {$sort}, h.event_date {$sort}";
        break;
    case 'user':
        $orderby = "u.real_name {$sort}, h.event_date {$sort}";
        break;
    case 'date': default:
        $orderby = "h.event_date {$sort}, h.event_type {$sort}";
}
    
$type = array();
if (Req::has('open'))          {array_push($type, 1, 13);          }
if (Req::has('close'))         {array_push($type, 2);              }
if (Req::has('edit'))          {array_push($type, 0, 3);           }
if (Req::has('assign'))        {array_push($type, 14, 29);         }
if (Req::has('comments'))      {array_push($type, 4, 5, 6);        }
if (Req::has('attachments'))   {array_push($type, 7, 8);           }
if (Req::has('related'))       {array_push($type, 11, 12, 15, 16); }
if (Req::has('notifications')) {array_push($type, 9, 10);          }
if (Req::has('reminders'))     {array_push($type, 17, 18);         }

$where = array();
foreach ($type as $eventtype) {
    $where[] = 'h.event_type = ' . $eventtype;
}
$where = '(' . implode(' OR ', $where) . ')';

if ($proj->id) {
    $where = 't.attached_to_project = ' . $proj->id . ' AND ' . $where;
}

$date = $wheredate = $within = '';
switch (Req::val('repdate')) {
    case 'within':
        $date   = 'within';
        $within = Req::val('within');
        if ($within != 'all') {
            $wheredate = 24 * 60 * 60;
            if ($within == 'week') {
                $wheredate *= 7;
            } elseif ($within == 'month') {
                $wheredate *= 30;
            } elseif ($within == 'year') {
                $wheredate *= 365;
            }
            
            $wheredate = time() - $wheredate;
            $wheredate = 'AND h.event_date > ' . $wheredate;
        }
        break;

    case 'from':
        $date      = 'from';
        $fromdate  = Req::val('fromdate', Req::val('from_userdate', date('d-m-Y')));
        $todate    = Req::val('todate', Req::val('to_userdate', date('d-m-Y')));

        $ufromdate = strtotime($fromdate) + 0;
        // Add 24 hours to the end to make it include that date
        $utodate   = strtotime($todate) + 86400;

        $wheredate = "AND h.event_date > {$ufromdate} AND h.event_date < {$utodate}";
        break;

    case 'duein':
        if (is_numeric($duein = Req::val('duein'))) {
            $date      = 'duein';
            $wheredate = "AND t.closedby_version = $duein";
        };
        break;
}
    
if (count($type)) { 
    $histories = $db->Query("SELECT h.*, t.*,
                              tt1.tasktype_name AS task_type1,
                              tt2.tasktype_name AS task_type2,
                              los1.os_name AS operating_system1,
                              los2.os_name AS operating_system2,
                              lc1.category_name AS product_category1,
                              lc2.category_name AS product_category2,
                              p1.project_title AS attached_to_project1,
                              p2.project_title AS attached_to_project2,
                              lv1.version_name AS product_version1,
                              lv2.version_name AS product_version2,
                              ls1.status_name AS item_status1,
                              ls2.status_name AS item_status2,
                              ls3.status_name AS status_name,
                              lr.resolution_name,
                              c.date_added AS c_date_added,
                              c.user_id AS c_user_id,
                              att.orig_name, att.file_desc

                        FROM  {history} h

                    LEFT JOIN {tasks} t ON t.task_id = h.task_id
                    
                    LEFT JOIN {list_tasktype} tt1 ON tt1.tasktype_id = h.old_value AND h.field_changed='task_type'
                    LEFT JOIN {list_tasktype} tt2 ON tt2.tasktype_id = h.new_value AND h.field_changed='task_type'

                    LEFT JOIN {list_os} los1 ON los1.os_id = h.old_value AND h.field_changed='operating_system'
                    LEFT JOIN {list_os} los2 ON los2.os_id = h.new_value AND h.field_changed='operating_system'

                    LEFT JOIN {list_category} lc1 ON lc1.category_id = h.old_value AND h.field_changed='product_category'
                    LEFT JOIN {list_category} lc2 ON lc2.category_id = h.new_value AND h.field_changed='product_category'
                    
                    LEFT JOIN {list_status} ls1 ON ls1.status_id = h.old_value AND h.field_changed='item_status'
                    LEFT JOIN {list_status} ls2 ON ls2.status_id = h.new_value AND h.field_changed='item_status'
                    LEFT JOIN {list_status} ls3 ON ls3.status_id = t.item_status
                    
                    LEFT JOIN {list_resolution} lr ON lr.resolution_id = h.new_value AND h.event_type = 2

                    LEFT JOIN {projects} p1 ON p1.project_id = h.old_value AND h.field_changed='attached_to_project'
                    LEFT JOIN {projects} p2 ON p2.project_id = h.new_value AND h.field_changed='attached_to_project'
                    
                    LEFT JOIN {comments} c ON c.comment_id = h.field_changed AND h.event_type = 5
                    
                    LEFT JOIN {attachments} att ON att.attachment_id = h.new_value AND h.event_type = 7

                    LEFT JOIN {list_version} lv1 ON lv1.version_id = h.old_value
                              AND (h.field_changed='product_version' OR h.field_changed='closedby_version')
                    LEFT JOIN {list_version} lv2 ON lv2.version_id = h.new_value
                              AND (h.field_changed='product_version' OR h.field_changed='closedby_version')
                        WHERE $where $wheredate
                     ORDER BY $orderby");
             
    $histories = $db->FetchAllArray($histories);
}

/**********************\
*  Voting tally report *
\**********************/

$sql = $db->Query("SELECT COUNT(vote_id) AS num_votes,
                          t.task_id AS id
                     FROM {votes} v, {tasks} t
                    WHERE v.task_id = t.task_id
                      AND t.is_closed <> '1'
                 GROUP BY v.task_id, t.task_id
                 ORDER BY num_votes DESC");

$tasks_voted_for = array();
while ($row = $db->FetchArray($sql)) {
    $tasks_voted_for = $tasks_voted_for + array($row['id'] => $row['num_votes']);
}

$page->uses('histories', 'sort', 'tasks_voted_for');


/**********************\
*  User reports       *
\**********************/

if (Req::has('created')) {array_push($type, 30); }
if (Req::has('deleted')) {array_push($type, 31); }

$where = array();
foreach ($type as $eventtype) {
    $where[] = 'h.event_type = ' . $eventtype;
}
$where = '(' . implode(' OR ', $where) . ')';

if (count($type)) {
    $userhistory = $db->Query("SELECT user_id, new_value, old_value, event_date, event_type FROM {history} h WHERE $where $wheredate");
    $page->assign('userhistory', $db->FetchAllArray($userhistory));
}

$page->pushTpl('reports.tpl');
?>
