<?php

  /*************************************************************\
  | Details a task (and edit it)                                |
  | ~~~~~~~~~~~~~~~~~~~~~~~~~~~~                                |
  | This script displays task details when in view mode,        |
  | and allows the user to edit task details when in edit mode. |
  | It also shows comments, attachments, notifications etc.     |
  \*************************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

$task_id = Req::num('task_id');

if ( !($task = Flyspray::GetTaskDetails($task_id))
        || !$user->can_view_task($task))
{
    Flyspray::show_error(10);
}

require_once(BASEDIR . '/includes/events.inc.php');

$page->uses('task');

// Send user variables to the template
$page->assign('assigned_users', $task['assigned_to']);
$page->assign('old_assigned', implode(' ', $task['assigned_to']));

$page->setTitle('FS#' . $task['task_id'] . ': ' . $task['item_summary']);

if ((Get::val('edit') || (Post::has('item_summary') && !isset($_SESSION['SUCCESS']))) && ($user->can_edit_task($task) || $user->can_correct_task($task))) {
    $result = $db->Execute('SELECT u.user_id, u.user_name, u.real_name, g.group_name
                            FROM {assigned} a, {users} u
                       LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id
                       LEFT JOIN {groups} g ON g.group_id = uig.group_id
                           WHERE a.user_id = u.user_id AND task_id = ? AND (g.project_id = 0 OR g.project_id = ?)
                        ORDER BY g.project_id DESC',
                          array($task_id, $proj->id));
    $result = GroupBy($result, 'user_id');
    $userlist = array();
    foreach ($result as $row) {
        $userlist[] = array(0 => $row['user_id'], 1 => "[{$row['group_name']}] {$row['user_name']} ({$row['real_name']})");
    }

    $page->assign('userlist', $userlist);
    $page->pushTpl('details.edit.tpl');
}
else {
    $prev_id = $next_id = 0;

    if (isset($_SESSION['tasklist']) && ($id_list = $_SESSION['tasklist'])
            && ($i = array_search($task_id, $id_list)) !== false)
    {
        $prev_id = isset($id_list[$i - 1]) ? $id_list[$i - 1] : '';
        $next_id = isset($id_list[$i + 1]) ? $id_list[$i + 1] : '';
    }

    // Parent categories for each category field
    $parents = array();
    foreach ($proj->fields as $field) {
        if ($field->prefs['list_type'] != LIST_CATEGORY) {
            continue;
        }
        $sql = $db->Execute('SELECT lft, rgt FROM {list_category} WHERE category_id = ?',
                          array($task['f' . $field->id]));
        $cat = $sql->FetchRow();

        $parent = $db->GetCol('SELECT  category_name
                                 FROM  {list_category}
                                WHERE  lft < ? AND rgt > ? AND list_id  = ? AND lft <> 1
                             ORDER BY  lft ASC',
                             array($cat['lft'], $cat['rgt'], $field->prefs['list_id']));
        $parents[$field->id] = $parent;
    }

    // Check for task dependencies that block closing this task
    $check_deps   = $db->Execute('SELECT  t.*, r.item_name AS resolution_name, d.depend_id
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                             LEFT JOIN  {list_items} r ON t.resolution_reason = r.list_item_id
                                 WHERE  d.task_id = ?', array($task_id));

    // Check for tasks that this task blocks
    $check_blocks = $db->Execute('SELECT  t.*, r.item_name AS resolution_name
                                  FROM  {dependencies} d
                             LEFT JOIN  {tasks} t on d.task_id = t.task_id
                             LEFT JOIN  {list_items} r ON t.resolution_reason = r.list_item_id
                                 WHERE  d.dep_task_id = ?', array($task_id));

    // Check for pending PM requests
    $get_pending  = $db->Execute('SELECT  *
                                  FROM  {admin_requests}
                                 WHERE  task_id = ?  AND resolved_by = 0',
                                 array($task_id));

    // Get info on the dependencies again
    $open_deps    = $db->GetOne('SELECT  COUNT(*) - SUM(is_closed)
                                   FROM  {dependencies} d
                              LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                  WHERE  d.task_id = ?', array($task_id));

    $watching     =  $db->GetOne('SELECT  COUNT(*)
                                    FROM  {notifications}
                                   WHERE  task_id = ?  AND user_id = ?',
                                  array($task_id, $user->id));

    // Check for cached version
    $cached = $db->Execute("SELECT content, last_updated
                            FROM {cache}
                           WHERE topic = ? AND type = 'task'",
                           array($task['task_id']));
    $cached = $cached->FetchRow();

    // List of votes
    $get_votes = $db->Execute('SELECT u.user_id, u.user_name, u.real_name, v.date_time
                               FROM {votes} v
                          LEFT JOIN {users} u ON v.user_id = u.user_id
                               WHERE v.task_id = ?
                            ORDER BY v.date_time DESC',
                            array($task_id));

    if ($task['last_edited_time'] > $cached['last_updated'] || !defined('FLYSPRAY_USE_CACHE')) {
        $task_text = TextFormatter::render($task['detailed_desc'], false, 'task', $task['task_id']);
    } else {
        $task_text = TextFormatter::render($task['detailed_desc'], false, 'task', $task['task_id'], $cached['content']);
    }

    $page->assign('prev_id',   $prev_id);
    $page->assign('next_id',   $next_id);
    $page->assign('task_text', $task_text);
    $page->assign('deps',      $check_deps->GetArray());
    $page->assign('blocks',    $check_blocks->GetArray());
    $page->assign('votes',     $get_votes->GetArray());
    $page->assign('penreqs',   $get_pending->GetArray());
    $page->assign('d_open',    $open_deps);
    $page->assign('watched',   $watching);
    $page->uses('parents');
    $page->pushTpl('details.view.tpl');

    ////////////////////////////
    // tabbed area

    // Comments + cache
    $sql = $db->Execute('  SELECT * FROM {comments} c
                      LEFT JOIN {cache} ca ON (c.comment_id = ca.topic AND ca.type = ? AND c.last_edited_time <= ca.last_updated)
                          WHERE task_id = ?
                       ORDER BY date_added ASC',
                           array('comm', $task_id));

    $page->assign('comments', $sql->GetArray());

    // Comment events
    $sql = get_events($task_id, ' AND (event_type = 3 OR event_type = 14)');
    $comment_changes = array();
    while ($row = $sql->FetchRow()) {
        $comment_changes[$row['event_date']][] = $row;
    }
    $page->assign('comment_changes', $comment_changes);

    // Comment attachments
    $attachments = array();
    $sql = $db->Execute('SELECT *
                         FROM {attachments} a, {comments} c
                        WHERE c.task_id = ? AND a.comment_id = c.comment_id',
                       array($task_id));
    while ($row = $sql->FetchRow()) {
        $attachments[$row['comment_id']][] = $row;
    }
    $page->assign('comment_attachments', $attachments);

    // Relations, notifications and reminders
    $sql = $db->Execute('SELECT  t.*, r.*, res.item_name AS resolution_name
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON (r.related_task = t.task_id AND r.this_task = ? OR r.this_task = t.task_id AND r.related_task = ?)
                    LEFT JOIN  {list_items} res ON t.resolution_reason = res.list_item_id
                        WHERE  t.task_id is NOT NULL AND is_duplicate = 0 AND ( t.mark_private = 0 OR ? = 1 )
                     ORDER BY  t.task_id ASC',
            array($task_id, $task_id, $user->perms('manage_project')));
    $page->assign('related', $sql->GetArray());

    $sql = $db->Execute('SELECT  t.*, r.*, res.item_name AS resolution_name
                         FROM  {related} r
                    LEFT JOIN  {tasks} t ON r.this_task = t.task_id
                    LEFT JOIN  {list_items} res ON t.resolution_reason = res.list_item_id
                        WHERE  is_duplicate = 1 AND r.related_task = ?
                     ORDER BY  t.task_id ASC',
                      array($task_id));
    $page->assign('duplicates', $sql->GetArray());

    $sql = $db->Execute('SELECT  *
                         FROM  {notifications} n
                    LEFT JOIN  {users} u ON n.user_id = u.user_id
                        WHERE  n.task_id = ?', array($task_id));
    $page->assign('notifications', $sql->GetArray());

    $sql = $db->Execute('SELECT  *
                         FROM  {reminders} r
                    LEFT JOIN  {users} u ON r.to_user_id = u.user_id
                        WHERE  task_id = ?
                     ORDER BY  reminder_id', array($task_id));
    $page->assign('reminders', $sql->GetArray());


    $page->pushTpl('details.tabs.tpl');

    if ($user->perms('view_comments') || $proj->prefs['others_view'] || ($user->isAnon() && $task['task_token'] && Get::val('task_token') == $task['task_token'])) {
        $page->pushTpl('details.tabs.comment.tpl');
    }

    $page->pushTpl('details.tabs.related.tpl');

    if ($user->perms('manage_project')) {
        $page->pushTpl('details.tabs.notifs.tpl');
        $page->pushTpl('details.tabs.remind.tpl');
    }

    $page->pushTpl('details.tabs.history.tpl');
}
?>
