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
$fs->get_language_pack('status');
$fs->get_language_pack('severity');
$fs->get_language_pack('priority');
$fs->get_language_pack('modify');

$page->uses('status_list', 'priority_list', 'severity_list', 'task_details',
        'details_text', 'newtask_text','modify_text');

$userlist = $fs->UserList($project_id);

if (!empty($task_details['assigned_to']) ) {
   $assigned_users = explode(" ", $task_details['assigned_to']);
}

$page->assign('userlist', $userlist);
$page->assign('assigned_users', $assigned_users);

if (Get::val('edit') && $user->can_edit_task($task_details)) {
    $page->display('details.edit.tpl');
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
    $page->display('details.view.tpl');

    ////////////////////////////
    // tabbed area

    $sql = $db->Query("SELECT * FROM {comments} WHERE task_id = ?", array($task_id));
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


    $page->display('details.tabs.tpl');

    if ($user->perms['view_comments'] || $proj->prefs['others_view']) {
        $page->display('details.tabs.comment.tpl');
    }

    $page->display('details.tabs.related.tpl');

    if ($user->perms['manage_project']) {
        $page->display('details.tabs.notifs.tpl');
        $page->display('details.tabs.remind.tpl');
    }

    if ($user->perms['view_history']) {
        if (is_numeric($details = Get::val('details'))) {
            $details = " AND h.history_id = $details";
        } else {
            $details = null;
        }

        $page->assign('details', $details);

        $sql = $db->Query("SELECT  *
                             FROM  {history} h
                            WHERE  task_id = ? {$details}
                         ORDER BY  event_date ASC, event_type ASC", array($task_id));
        $page->assign('histories', $db->fetchAllArray($sql));

        // FIXME TODO XXX horrible, but templating history was just too
        // difficult
        $page->uses('db');
        $page->display('details.tabs.history.tpl');
    }
}
?>
