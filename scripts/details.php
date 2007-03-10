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

require_once(BASEDIR . '/includes/events.inc.php');

class FlysprayDoDetails extends FlysprayDo
{
    var $task = array();

    // **********************
    // Begin all action_ functions
    // **********************

    function action_newdep($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->can_edit_task($task)) {
            return array(ERROR_PERMS);
        }

        if (!Post::val('dep_task_id')) {
            return array(ERROR_RECOVER, L('formnotcomplete'));
        }

        // First check that the user hasn't tried to add this twice
        $sql1 = $db->GetOne('SELECT  COUNT(*) FROM {dependencies}
                             WHERE  task_id = ? AND dep_task_id = ?',
                             array($task['task_id'], Post::val('dep_task_id')));

        // or that they are trying to reverse-depend the same task, creating a mutual-block
        $sql2 = $db->GetOne('SELECT  COUNT(*) FROM {dependencies}
                             WHERE  task_id = ? AND dep_task_id = ?',
                            array(Post::val('dep_task_id'), $task['task_id']));

        // Check that the dependency actually exists!
        $sql3 = $db->GetOne('SELECT COUNT(*) FROM {tasks} WHERE task_id = ?',
                            array(Post::val('dep_task_id')));

        if ($sql1 || $sql2 || !$sql3
                // Check that the user hasn't tried to add the same task as a dependency
                || Post::val('task_id') == Post::val('dep_task_id'))
        {
            return array(ERROR_RECOVER, L('dependaddfailed'));
        }

        Notifications::send($task['task_id'], ADDRESS_TASK, NOTIFY_DEP_ADDED, array('dep_task' => Post::val('dep_task_id')));
        Notifications::send(Post::val('dep_task_id'), ADDRESS_TASK, NOTIFY_REV_DEP, array('dep_task' => $task['task_id']));

        // Log this event to the task history, both ways
        Flyspray::logEvent($task['task_id'], 22, Post::val('dep_task_id'));
        Flyspray::logEvent(Post::val('dep_task_id'), 23, $task['task_id']);

        $db->Execute('INSERT INTO  {dependencies} (task_id, dep_task_id)
                         VALUES  (?,?)',
                    array($task['task_id'], Post::val('dep_task_id')));

        return array(SUBMIT_OK, L('dependadded'));
    }

    function action_removedep($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->can_edit_task($task)) {
            return array(ERROR_PERMS);
        }

        $result = $db->Execute('SELECT  * FROM {dependencies}
                                 WHERE  depend_id = ?',
                                array(Get::val('depend_id')));
        $dep_info = $result->FetchRow();

        $db->Execute('DELETE FROM {dependencies} WHERE depend_id = ? AND task_id = ?',
                      array(Get::val('depend_id'), $task['task_id']));

        if ($db->Affected_Rows()) {
            Notifications::send($dep_info['task_id'], ADDRESS_TASK, NOTIFY_DEP_REMOVED, array('dep_task' => $dep_info['dep_task_id']));
            Notifications::send($dep_info['dep_task_id'], ADDRESS_TASK, NOTIFY_REV_DEP_REMOVED, array('dep_task' => $dep_info['task_id']));

            Flyspray::logEvent($dep_info['task_id'], 24, $dep_info['dep_task_id']);
            Flyspray::logEvent($dep_info['dep_task_id'], 25, $dep_info['task_id']);
        }

        return array(SUBMIT_OK, L('depremovedmsg'));
    }

    function action_edit_task($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->can_edit_task($task) && !$user->can_correct_task($task)) {
            return array(ERROR_PERMS);
        }

        // check missing fields
        if (!Post::val('item_summary') || !Post::val('detailed_desc')) {
            return array(ERROR_RECOVER, L('summaryanddetails'));
        }

        foreach ($proj->fields as $field) {
            if ($field->prefs['value_required'] && !Post::val('field' . $field->id)
                && !($field->prefs['force_default'] && !$user->perms('modify_all_tasks'))) {
                return array(ERROR_RECOVER, L('missingrequired') . ' (' . $field->prefs['field_name'] . ')');
            }
        }

        $time = time();

        $db->Execute('UPDATE  {tasks}
                       SET  project_id = ?, item_summary = ?,
                            detailed_desc = ?, mark_private = ?,
                            task_severity = ?, last_edited_by = ?,
                            last_edited_time = ?, percent_complete = ?
                     WHERE  task_id = ?',
                array(Post::val('project_id'), Post::val('item_summary'),
                      Post::val('detailed_desc'), intval($user->can_change_private($task) && Post::val('mark_private')),
                      Post::val('task_severity'), intval($user->id), $time,
                      Post::val('percent_complete'), $task['task_id']));
        // Now the custom fields
        foreach ($proj->fields as $field) {
            if ($field->prefs['force_default'] && !$user->can_edit_task($task)) {
                continue; // make sure that a user who is only correcting his task does not change certain fields
            }
            $field_value = Post::val('field' . $field->id);
            if ($field->prefs['field_type'] == FIELD_DATE) {
                $field_value = Flyspray::strtotime($field_value);
            }
            $db->Replace('{field_values}',
                         array('field_id'=> $field->id, 'task_id'=> $task['task_id'], 'field_value' => "'" . $field_value . "'"),
                         array('field_id','task_id'), ADODB_AUTOQUOTE);
        }

        // Update the list of users assigned this task
        if ($user->perms('edit_assignments') && Post::val('old_assigned') != trim(Post::val('assigned_to')) ) {

            // Delete the current assignees for this task
            $db->Execute('DELETE FROM {assigned}
                              WHERE task_id = ?',
                        array($task['task_id']));

            // Convert assigned_to and store them in the 'assigned' table
            foreach (Flyspray::int_explode(' ', trim(Post::val('assigned_to'))) as $key => $val)
            {
                $db->Replace('{assigned}', array('user_id'=> $val, 'task_id'=> $task['task_id']), array('user_id','task_id'), ADODB_AUTOQUOTE);
            }
         }

        // Get the details of the task we just updated
        // To generate the changed-task message
        $new_details_full = Flyspray::GetTaskDetails($task['task_id']);

        $changes = Flyspray::compare_tasks($task, $new_details_full);
        foreach ($changes as $change) {
            if ($change[4] == 'assigned_to_name') {
                continue;
            }
            Flyspray::logEvent($task['task_id'], 3, $change[2], $change[1], $change[4], $time);
        }
        if (count($changes) > 0) {
            Notifications::send($task['task_id'], ADDRESS_TASK, NOTIFY_TASK_CHANGED, array('changes' => $changes));
        }

        if (Post::val('old_assigned') != trim(Post::val('assigned_to')) ) {
            // Log to task history
            Flyspray::logEvent($task['task_id'], 14, trim(Post::val('assigned_to')), Post::val('old_assigned'), '', $time);

            // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
            if (Post::val('assigned_to') != '') {
                $new_assignees = array_diff(Flyspray::int_explode(' ', Post::val('assigned_to')), Flyspray::int_explode(' ', Post::val('old_assigned')));
                // Remove current user from notification list
                if (!$user->infos['notify_own']) {
                    $new_assignees = array_filter($new_assignees, create_function('$u', 'global $user; return $user->id != $u;'));
                }
                if (count($new_assignees)) {
                    Notifications::send($new_assignees, ADDRESS_USER, NOTIFY_NEW_ASSIGNEE, array('task_id' => $task['task_id']));
                }
            }
        }

        Backend::add_comment($task, Post::val('comment_text'), $time);
        Backend::delete_files(Post::val('delete_att'));
        Backend::upload_files($task['task_id'], '0', 'usertaskfile');

        return array(SUBMIT_OK, L('taskupdated'));
    }

    function action_close($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->can_close_task($task)) {
            return array(ERROR_PERMS);
        }

        if ($task['is_closed']) {
            return array(ERROR_INPUT, L('taskalreadyclosed'));
        }

        if (!Post::val('resolution_reason')) {
            return array(ERROR_RECOVER, L('noclosereason'));
        }

        Backend::close_task($task['task_id'], Post::val('resolution_reason'), Post::val('closure_comment', ''), Post::val('mark100', false));

        return array(SUBMIT_OK, L('taskclosedmsg'));
    }

    function action_addcomment($task)
    {
        global $user, $db, $fs, $proj;

        if (!Backend::add_comment($task, Post::val('comment_text'))) {
            return array(ERROR_RECOVER, L('nocommententered'));
        }

        if (Post::val('notifyme') == '1') {
            // If the user wanted to watch this task for changes
            Backend::add_notification($user->id, $task['task_id']);
        }

        return array(SUBMIT_OK, L('commentaddedmsg'));
    }

    function action_editcomment($task)
    {
        global $user, $db, $fs, $proj;

        if (!($user->perms('edit_comments') || $user->perms('edit_own_comments'))) {
            return array(ERROR_PERMS);
        }

        $where = '';

        $params = array(Post::val('comment_text'), time(),
                        Post::val('comment_id'), $task['task_id']);

        if ($user->perms('edit_own_comments') && !$user->perms('edit_comments')) {
            $where = ' AND user_id = ?';
            array_push($params, $user->id);
        }

        $db->Execute("UPDATE  {comments}
                         SET  comment_text = ?, last_edited_time = ?
                       WHERE  comment_id = ? AND task_id = ? $where", $params);

        Flyspray::logEvent($task['task_id'], 5, Post::val('comment_text'),
                           Post::val('previous_text'), Post::val('comment_id'));

        Backend::upload_files($task['task_id'], Post::val('comment_id'));
        Backend::delete_files(Post::val('delete_att'));

        return array(SUBMIT_OK, L('editcommentsaved'));
    }

    function action_add_related($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->can_edit_task($task)) {
            return array(ERROR_PERMS);
        }

        $pid = $db->GetOne('SELECT  project_id
                              FROM  {tasks}
                             WHERE  task_id = ?',
                            array(Post::val('related_task')));
        if (!$pid) {
            return array(ERROR_RECOVER, L('relatedinvalid'));
        }

        $rid = $db->GetOne("SELECT related_id
                              FROM {related}
                             WHERE this_task = ? AND related_task = ?
                                   OR
                                   related_task = ? AND this_task = ?",
                            array($task['task_id'], Post::val('related_task'),
                                  $task['task_id'], Post::val('related_task')));

        if ($rid) {
            return array(ERROR_RECOVER, L('relatederror'));
        }

        $db->Execute('INSERT INTO {related} (this_task, related_task) VALUES(?,?)',
                        array($task['task_id'], Post::val('related_task')));

        Flyspray::logEvent($task['task_id'], 11, Post::val('related_task'));
        Flyspray::logEvent(Post::val('related_task'), 11, $task['task_id']);

        Notifications::send($task['task_id'], ADDRESS_TASK, NOTIFY_REL_ADDED, array('rel_task' => Post::val('related_task')));

        return array(SUBMIT_OK, L('relatedaddedmsg'));
    }

    function action_remove_related($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->can_edit_task($task)) {
            return array(ERROR_PERMS);
        }

        foreach ( (array) Post::val('related_id') as $related) {
            $sql = $db->Execute('SELECT this_task, related_task FROM {related} WHERE related_id = ?',
                                 array($related));
            $db->Execute('DELETE FROM {related} WHERE related_id = ? AND (this_task = ? OR related_task = ?)',
                          array($related, $task['task_id'], $task['task_id']));
            if ($db->Affected_Rows()) {
                $related_task = $sql->FetchRow();
                $related_task = ($related_task['this_task'] == $task['task_id']) ? $related_task['related_task'] : $task['task_id'];
                Flyspray::logEvent($task['task_id'], 12, $related_task);
                Flyspray::logEvent($related_task, 12, $task['task_id']);
            }
        }

        if (isset($related_task)) {
            return array(SUBMIT_OK, L('relatedremoved'));
        } else {
            return array(ERROR_RECOVER, L('relatedinvalid'));
        }
    }

    function action_add_notification()
    {
        if (!Backend::add_notification(Req::val('user_id'), Req::val('ids'))) {
            return array(ERROR_RECOVER, L('couldnotaddusernotif'));
        }

        return array(SUBMIT_OK, L('notifyadded'));
    }

    function action_remove_notification()
    {
        Backend::remove_notification(Req::val('user_id'), Req::val('ids'));

        return array(SUBMIT_OK, L('notifyremoved'));
    }

    function action_deletecomment()
    {
        global $user, $db, $fs, $proj;

        if (!$user->perms('delete_comments')) {
            return array(ERROR_PERMS);
        }

        $result = $db->Execute('SELECT  task_id, comment_text, user_id, date_added
                                FROM  {comments}
                               WHERE  comment_id = ?',
                            array(Get::val('comment_id')));
        $comment = $result->FetchRow();

        // Check for files attached to this comment
        $check_attachments = $db->GetOne('SELECT  count(*)
                                            FROM  {attachments}
                                           WHERE  comment_id = ?',
                                          array(Req::val('comment_id')));

        if ($check_attachments && !$user->perms('delete_attachments')) {
            return array(ERROR_PERMS, L('commentattachperms'));
        }

        $db->Execute("DELETE FROM {comments} WHERE comment_id = ? AND task_id = ?",
                   array(Req::val('comment_id'), $task['task_id']));

        if ($db->Affected_Rows()) {
            Flyspray::logEvent($task['task_id'], 6, $comment['user_id'],
                    $comment['comment_text'], $comment['date_added']);
        }

        while ($attachment = $check_attachments->FetchRow()) {
            $db->Execute("DELETE from {attachments} WHERE attachment_id = ?",
                    array($attachment['attachment_id']));

            @unlink(BASEDIR .'/attachments/' . $attachment['file_name']);

            Flyspray::logEvent($attachment['task_id'], 8, $attachment['orig_name']);
        }

        return array(SUBMIT_OK, L('commentdeletedmsg'));
    }

    function action_addreminder($task)
    {
        global $user, $db, $fs, $proj;

        $how_often  = Post::val('timeamount1', 1) * Post::val('timetype1');
        $start_time = Flyspray::strtotime(Post::val('timeamount2', 0));

        if (!Backend::add_reminder($task['task_id'], Post::val('reminder_message'), $how_often, $start_time, Post::val('to_user_id'))) {
            return array(ERROR_RECOVER, L('usernotexist'));
        }

        return array(SUBMIT_OK, L('reminderaddedmsg'));
    }


    function action_deletereminder($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->perms('manage_project')) {
            return array(ERROR_PERMS);
        }

        foreach ( (array) Post::val('reminder_id') as $reminder_id) {
            $reminder = $db->GetOne('SELECT to_user_id FROM {reminders} WHERE reminder_id = ?',
                                     array($reminder_id));
            $db->Execute('DELETE FROM {reminders} WHERE reminder_id = ? AND task_id = ?',
                          array($reminder_id, $task['task_id']));
            if ($db && $db->Affected_Rows()) {
                Flyspray::logEvent($task['task_id'], 18, $reminder);
            }
        }

        return array(SUBMIT_OK, L('reminderdeletedmsg'));
    }

    function action_addvote($task)
    {
        global $user, $db, $fs, $proj;

        if (Backend::add_vote($user->id, $task['task_id'])) {
            return array(SUBMIT_OK, L('voterecorded'));
        } else {
            return array(ERROR_RECOVER, L('votefailed'));
        }
    }

    function action_makeprivate($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->perms('manage_project')) {
            return array(ERROR_PERMS);
        }

        $db->Execute('UPDATE  {tasks}
                         SET  mark_private = 1
                       WHERE  task_id = ?', array($task['task_id']));

        Flyspray::logEvent($task['task_id'], 3, 1, 0, 'mark_private');

        return array(SUBMIT_OK, L('taskmadeprivatemsg'));
    }

    function action_makepublic($task)
    {
        global $user, $db, $fs, $proj;

        if (!$user->perms('manage_project')) {
            return array(ERROR_PERMS);
        }

        $db->Execute('UPDATE  {tasks}
                         SET  mark_private = 0
                       WHERE  task_id = ?', array($task['task_id']));

        Flyspray::logEvent($task['task_id'], 3, 0, 1, 'mark_private');

        return array(SUBMIT_OK, L('taskmadepublicmsg'));
    }

    // **********************
    // End of all action_ functions
    // **********************

    function is_accessible()
    {
        global $user;
        $this->task = Flyspray::GetTaskDetails(Req::num('task_id'));
        return $this->task && $user->can_view_task($this->task);
    }

	function _onsubmit()
	{
        list($type, $msg, $url) = $this->handle('action', Req::val('action'), $this->task);
        if ($type != NO_SUBMIT) {
            $this->task = Flyspray::GetTaskDetails(Req::num('task_id'));
        }

        return array($type, $msg, $url);
	}

    function _show()
    {
        global $page, $user, $fs, $proj, $db;

        // Send user variables to the template
        $page->assign('assigned_users', $this->task['assigned_to']);
        $page->assign('old_assigned', implode(' ', $this->task['assigned_to']));
        $page->assign('task', $this->task);

        $page->setTitle('FS#' . $this->task['task_id'] . ': ' . $this->task['item_summary']);

        if ((Get::val('edit') || (Post::has('item_summary') && !isset($_SESSION['SUCCESS']))) && ($user->can_edit_task($this->task) || $user->can_correct_task($this->task))) {
            $result = $db->Execute('SELECT u.user_id, u.user_name, u.real_name, g.group_name
                                      FROM {assigned} a, {users} u
                                 LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id
                                 LEFT JOIN {groups} g ON g.group_id = uig.group_id
                                     WHERE a.user_id = u.user_id AND task_id = ? AND (g.project_id = 0 OR g.project_id = ?)
                                  ORDER BY g.project_id DESC',
                                    array($this->task['task_id'], $proj->id));
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
                    && ($i = array_search($this->task['task_id'], $id_list)) !== false)
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
                                  array($this->task['f' . $field->id]));
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
                                         WHERE  d.task_id = ?', array($this->task['task_id']));

            // Check for tasks that this task blocks
            $check_blocks = $db->Execute('SELECT  t.*, r.item_name AS resolution_name
                                          FROM  {dependencies} d
                                     LEFT JOIN  {tasks} t on d.task_id = t.task_id
                                     LEFT JOIN  {list_items} r ON t.resolution_reason = r.list_item_id
                                         WHERE  d.dep_task_id = ?', array($this->task['task_id']));

            // Check for pending PM requests
            $get_pending  = $db->Execute('SELECT  *
                                          FROM  {admin_requests}
                                         WHERE  task_id = ?  AND resolved_by = 0',
                                         array($this->task['task_id']));

            // Get info on the dependencies again
            $open_deps    = $db->GetOne('SELECT  COUNT(*) - SUM(is_closed)
                                           FROM  {dependencies} d
                                      LEFT JOIN  {tasks} t on d.dep_task_id = t.task_id
                                          WHERE  d.task_id = ?', array($this->task['task_id']));

            $watching     =  $db->GetOne('SELECT  COUNT(*)
                                            FROM  {notifications}
                                           WHERE  task_id = ?  AND user_id = ?',
                                          array($this->task['task_id'], $user->id));

            // Check for cached version
            $cached = $db->Execute("SELECT content, last_updated
                                    FROM {cache}
                                   WHERE topic = ? AND type = 'task'",
                                   array($this->task['task_id']));
            $cached = $cached->FetchRow();

            // List of votes
            $get_votes = $db->Execute('SELECT u.user_id, u.user_name, u.real_name, v.date_time
                                       FROM {votes} v
                                  LEFT JOIN {users} u ON v.user_id = u.user_id
                                       WHERE v.task_id = ?
                                    ORDER BY v.date_time DESC',
                                    array($this->task['task_id']));

            if ($this->task['last_edited_time'] > $cached['last_updated'] || !defined('FLYSPRAY_USE_CACHE')) {
                $task_text = TextFormatter::render($this->task['detailed_desc'], false, 'task', $this->task['task_id']);
            } else {
                $task_text = TextFormatter::render($this->task['detailed_desc'], false, 'task', $this->task['task_id'], $cached['content']);
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
            $page->assign('parents',   $parents);
            $page->pushTpl('details.view.tpl');

            ////////////////////////////
            // tabbed area

            // Comments + cache
            $sql = $db->Execute('  SELECT * FROM {comments} c
                                LEFT JOIN {cache} ca ON (c.comment_id = ca.topic AND ca.type = ? AND c.last_edited_time <= ca.last_updated)
                                    WHERE task_id = ?
                                 ORDER BY date_added ASC',
                                   array('comm', $this->task['task_id']));

            $page->assign('comments', $sql->GetArray());

            // Comment events
            $sql = get_events($this->task['task_id'], ' AND (event_type = 3 OR event_type = 14)');
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
                               array($this->task['task_id']));
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
                    array($this->task['task_id'], $this->task['task_id'], $user->perms('manage_project')));
            $page->assign('related', $sql->GetArray());

            $sql = $db->Execute('SELECT  t.*, r.*, res.item_name AS resolution_name
                                 FROM  {related} r
                            LEFT JOIN  {tasks} t ON r.this_task = t.task_id
                            LEFT JOIN  {list_items} res ON t.resolution_reason = res.list_item_id
                                WHERE  is_duplicate = 1 AND r.related_task = ?
                             ORDER BY  t.task_id ASC',
                              array($this->task['task_id']));
            $page->assign('duplicates', $sql->GetArray());

            $sql = $db->Execute('SELECT  *
                                 FROM  {notifications} n
                            LEFT JOIN  {users} u ON n.user_id = u.user_id
                                WHERE  n.task_id = ?', array($this->task['task_id']));
            $page->assign('notifications', $sql->GetArray());

            $sql = $db->Execute('SELECT  *
                                 FROM  {reminders} r
                            LEFT JOIN  {users} u ON r.to_user_id = u.user_id
                                WHERE  task_id = ?
                             ORDER BY  reminder_id', array($this->task['task_id']));
            $page->assign('reminders', $sql->GetArray());


            $page->pushTpl('details.tabs.tpl');

            if ($user->perms('view_comments') || $proj->prefs['others_view'] || ($user->isAnon() && $this->task['task_token'] && Get::val('task_token') == $this->task['task_token'])) {
                $page->pushTpl('details.tabs.comment.tpl');
            }

            $page->pushTpl('details.tabs.related.tpl');

            if ($user->perms('manage_project')) {
                $page->pushTpl('details.tabs.notifs.tpl');
                $page->pushTpl('details.tabs.remind.tpl');
            }

            $page->pushTpl('details.tabs.history.tpl');
        }
    }
}

?>
