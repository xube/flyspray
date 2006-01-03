<?php

  /*************************************************************\
  | Details a task (and edit it)                                |
  | ~~~~~~~~~~~~~~~~~~~~~~~~~~~~                                |
  | This script displays task details when in view mode,        |
  | and allows the user to edit task details when in edit mode. |
  | It also shows comments, attachments, notifications etc.     |
  \*************************************************************/

$task_id = Get::val('id');

if ( !($task_details = $fs->GetTaskDetails($task_id))
        || !$user->can_view_task($task_details))
{
    $fs->Redirect( $fs->CreateURL('error', null) );
}

$fs->get_language_pack('details');
$fs->get_language_pack('newtask');
$fs->get_language_pack('index');
$fs->get_language_pack('severity');
$fs->get_language_pack('priority');
$fs->get_language_pack('modify');

$status_list = array();
$sql = $db->Query('SELECT status_id, status_name FROM {list_status}');
while ($row = $db->FetchArray($sql)) {
    $status_list[$row[0]] = $row[1];
}

$page->uses('priority_list', 'severity_list', 'task_details',
            'status_list', 'details_text', 'newtask_text','modify_text');

$userlist = $proj->UserList();

// Find the users assigned to this task
$assigned_users = $task_details['assigned_to'];
$old_assigned = implode(' ', $assigned_users);

// Send user variables to the template
$page->assign('userlist', $userlist);
$page->assign('assigned_users', $assigned_users);
$page->assign('old_assigned', $old_assigned);

$page->setTitle('FS#' . $task_details['task_id'] . ': ' . $task_details['item_summary']);

if (Get::val('edit') && $user->can_edit_task($task_details)) {
    $page->pushTpl('details.edit.tpl');
}
else {
    $prev_id = $next_id = 0;

    if (($id_list = @$_SESSION['tasklist'])
            && ($i = array_search($task_id, $id_list)) !== false)
    {
        $prev_id = @$id_list[$i - 1];
        $next_id = @$id_list[$i + 1];
    }

    // Check for task dependencies that block closing this task
    $check_deps   = $db->Query("SELECT  *
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                 WHERE  d.task_id = ?", array($task_id));

    // Check for tasks that this task blocks
    $check_blocks = $db->Query("SELECT  *
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.task_id = t.task_id
                                 WHERE  d.dep_task_id = ?", array($task_id));

    // Check for pending PM requests
    $get_pending  = $db->Query("SELECT  *
                                  FROM  {admin_requests}
                                 WHERE  task_id = ?  AND resolved_by = '0'",
                                 array($task_id));

    // Get info on the dependencies again
    $open_deps    = $db->Query("SELECT  COUNT(*) - SUM(is_closed)
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                 WHERE  d.task_id = ?", array($task_id));

    $watching     =  $db->Query("SELECT  COUNT(*)
                                   FROM  {notifications}
                                  WHERE  task_id = ?  AND user_id = ?",
                                  array($task_id, $user->id));

    $page->assign('prev_id', $prev_id);
    $page->assign('next_id', $next_id);
    $page->assign('deps',    $db->fetchAllArray($check_deps));
    $page->assign('blocks',  $db->fetchAllArray($check_blocks));
    $page->assign('penreqs', $db->fetchAllArray($get_pending));
    $page->assign('d_open',  $db->fetchOne($open_deps));
    $page->assign('watched', $db->fetchOne($watching));
    $page->pushTpl('details.view.tpl');

    ////////////////////////////
    // tabbed area

    $sql = $db->Query("SELECT * FROM {comments} WHERE task_id = ? ORDER BY date_added ASC",
                       array($task_id));
    $page->assign('comments', $db->fetchAllArray($sql));

    $sql = $db->Query("SELECT  *
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON r.related_task = t.task_id
                        WHERE  r.this_task = ?
                               AND ( t.mark_private = 0 OR ? = 1
                                   OR t.assigned_to = ? )",
            array($task_id, $user->perms['manage_project'], $user->id));
    $page->assign('related', $db->fetchAllArray($sql));

    $sql = $db->Query("SELECT  *
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON r.this_task = t.task_id
                        WHERE  r.related_task = ?", array($task_id));
    $page->assign('related_to', $db->fetchAllArray($sql));

    $sql = $db->Query("SELECT  *
                         FROM  {notifications} n
                    LEFT JOIN  {users} u ON n.user_id = u.user_id
                        WHERE  n.task_id = ?", array($task_id));
    $page->assign('notifications', $db->fetchAllArray($sql));

    $sql = $db->Query("SELECT  *
                         FROM  {reminders} r
                    LEFT JOIN  {users} u ON r.to_user_id = u.user_id
                        WHERE  task_id = ?
                     ORDER BY  reminder_id", array($task_id));
    $page->assign('reminders', $db->fetchAllArray($sql));


    $page->pushTpl('details.tabs.tpl');

    if ($user->perms['view_comments'] || $proj->prefs['others_view']) {
        $page->pushTpl('details.tabs.comment.tpl');
    }

    $page->pushTpl('details.tabs.related.tpl');

    if ($user->perms['manage_project']) {
        $page->pushTpl('details.tabs.notifs.tpl');
        $page->pushTpl('details.tabs.remind.tpl');
    }

    if ($user->perms['view_history'] && Get::has('history')) {
        if (is_numeric($details = Get::val('details'))) {
            $details = " AND h.history_id = $details";
        } else {
            $details = null;
        }

        $page->assign('details', $details);

        $sql = $db->Query("SELECT h.*,
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
                                  lr.resolution_name,
                                  c.date_added AS c_date_added,
                                  c.user_id AS c_user_id,
                                  att.orig_name, att.file_desc

                            FROM  {history} h

                        LEFT JOIN {list_tasktype} tt1 ON tt1.tasktype_id = h.old_value AND h.field_changed='task_type'
                        LEFT JOIN {list_tasktype} tt2 ON tt2.tasktype_id = h.new_value AND h.field_changed='task_type'

                        LEFT JOIN {list_os} los1 ON los1.os_id = h.old_value AND h.field_changed='operating_system'
                        LEFT JOIN {list_os} los2 ON los2.os_id = h.new_value AND h.field_changed='operating_system'

                        LEFT JOIN {list_category} lc1 ON lc1.category_id = h.old_value AND h.field_changed='product_category'
                        LEFT JOIN {list_category} lc2 ON lc2.category_id = h.new_value AND h.field_changed='product_category'
                        
                        LEFT JOIN {list_status} ls1 ON ls1.status_id = h.old_value AND h.field_changed='item_status'
                        LEFT JOIN {list_status} ls2 ON ls2.status_id = h.new_value AND h.field_changed='item_status'
                        
                        LEFT JOIN {list_resolution} lr ON lr.resolution_id = h.new_value AND h.event_type = 2

                        LEFT JOIN {projects} p1 ON p1.project_id = h.old_value AND h.field_changed='attached_to_project'
                        LEFT JOIN {projects} p2 ON p2.project_id = h.new_value AND h.field_changed='attached_to_project'
                        
                        LEFT JOIN {comments} c ON c.comment_id = h.field_changed AND h.event_type = 5
                        
                        LEFT JOIN {attachments} att ON att.attachment_id = h.new_value AND h.event_type = 7

                        LEFT JOIN {list_version} lv1 ON lv1.version_id = h.old_value
                                  AND (h.field_changed='product_version' OR h.field_changed='closedby_version')
                        LEFT JOIN {list_version} lv2 ON lv2.version_id = h.new_value
                                  AND (h.field_changed='product_version' OR h.field_changed='closedby_version')

                            WHERE h.task_id = ? $details
                         ORDER BY event_date ASC, event_type ASC", array($task_id));
        $page->assign('histories', $db->fetchAllArray($sql));

        $page->pushTpl('details.tabs.history.tpl');
    }
}
?>
