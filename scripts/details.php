<?php

  /*************************************************************\
  | Details a task (and edit it)                                |
  | ~~~~~~~~~~~~~~~~~~~~~~~~~~~~                                |
  | This script displays task details when in view mode,        |
  | and allows the user to edit task details when in edit mode. |
  | It also shows comments, attachments, notifications etc.     |
  \*************************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

$task_id = Get::val('id');

if ( !($task_details = $fs->GetTaskDetails($task_id))
        || !$user->can_view_task($task_details))
{
    $fs->Redirect( CreateURL('error', null) );
}

require_once(BASEDIR . '/includes/events.inc.php');

$page->uses('priority_list', 'severity_list', 'task_details',
            'status_list');

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
    $check_deps   = $db->Query('SELECT  *
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                 WHERE  d.task_id = ?', array($task_id));

    // Check for tasks that this task blocks
    $check_blocks = $db->Query('SELECT  *
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.task_id = t.task_id
                                 WHERE  d.dep_task_id = ?', array($task_id));

    // Check for pending PM requests
    $get_pending  = $db->Query("SELECT  *
                                  FROM  {admin_requests}
                                 WHERE  task_id = ?  AND resolved_by = '0'",
                                 array($task_id));

    // Get info on the dependencies again
    $open_deps    = $db->Query('SELECT  COUNT(*) - SUM(is_closed)
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                 WHERE  d.task_id = ?', array($task_id));

    $watching     =  $db->Query('SELECT  COUNT(*)
                                   FROM  {notifications}
                                  WHERE  task_id = ?  AND user_id = ?',
                                  array($task_id, $user->id));
    
    // Check if task has been reopened some time
    $reopened     =  $db->Query('SELECT  COUNT(*)
                                   FROM  {history}
                                  WHERE  task_id = ?  AND event_type = 13',
                                  array($task_id));

    $page->assign('prev_id',  $prev_id);
    $page->assign('next_id',  $next_id);
    $page->assign('deps',     $db->fetchAllArray($check_deps));
    $page->assign('blocks',   $db->fetchAllArray($check_blocks));
    $page->assign('penreqs',  $db->fetchAllArray($get_pending));
    $page->assign('d_open',   $db->fetchOne($open_deps));
    $page->assign('watched',  $db->fetchOne($watching));
    $page->assign('reopened', $db->fetchOne($reopened));
    $page->pushTpl('details.view.tpl');

    ////////////////////////////
    // tabbed area

    $sql = $db->Query('SELECT * FROM {comments} WHERE task_id = ? ORDER BY date_added ASC',
                       array($task_id));
    $page->assign('comments', $db->fetchAllArray($sql));

    $sql = get_events($task_id, ' AND (event_type = 0 OR event_type = 14)');
    $comment_changes = array();
    while ($row = $db->FetchRow($sql)) {
        $comment_changes[$row['event_date']][] = $row;
    }
    $page->assign('comment_changes', $comment_changes);

    $sql = $db->Query('SELECT  *
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON r.related_task = t.task_id
                        WHERE  r.this_task = ?
                               AND ( t.mark_private = 0 OR ? = 1
                                   OR t.assigned_to = ? )',
            array($task_id, $user->perms['manage_project'], $user->id));
    $page->assign('related', $db->fetchAllArray($sql));

    $sql = $db->Query('SELECT  *
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON r.this_task = t.task_id
                        WHERE  r.related_task = ?', array($task_id));
    $page->assign('related_to', $db->fetchAllArray($sql));

    $sql = $db->Query('SELECT  *
                         FROM  {notifications} n
                    LEFT JOIN  {users} u ON n.user_id = u.user_id
                        WHERE  n.task_id = ?', array($task_id));
    $page->assign('notifications', $db->fetchAllArray($sql));

    $sql = $db->Query('SELECT  *
                         FROM  {reminders} r
                    LEFT JOIN  {users} u ON r.to_user_id = u.user_id
                        WHERE  task_id = ?
                     ORDER BY  reminder_id', array($task_id));
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

    $page->pushTpl('details.tabs.history.tpl');
}
?>
