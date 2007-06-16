<?php
/**
 * Flyspray
 *
 * Backend class
 *
 * This script contains reusable functions we use to modify
 * various things in the Flyspray database tables.
 *
 * @license http://opensource.org/licenses/lgpl-license.php Lesser GNU Public License
 * @package flyspray
 * @author Tony Collins, Florian Schmitz
 */

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

// Include the notifications class
require_once BASEDIR . '/includes/class.notify.php';

class Backend
{
    /**
     * Adds the user $user_id to the notifications list of $tasks
     * @param integer $user_id
     * @param array $tasks
     * @param bool $do Force execution independent of user permissions
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_notification($user_id, $tasks, $do = false)
    {
        global $db, $user;

        settype($tasks, 'array');

        $user_id = Flyspray::username_to_id($user_id);

        if (!$user_id || !count($tasks)) {
            return false;
        }

        $sql = $db->Execute(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                             $tasks);

        while ($row = $sql->FetchRow()) {
            // -> user adds himself
            if ($user->id == $user_id) {
                if (!$user->can_view_task($row) && !$do) {
                    continue;
                }
            // -> user is added by someone else
            } else  {
                if (!$user->perms('manage_project', $row['project_id']) && !$do) {
                    continue;
                }
            }

            $notif = $db->GetOne('SELECT notify_id
                                    FROM {notifications}
                                   WHERE task_id = ? and user_id = ?',
                                  array($row['task_id'], $user_id));

            if (!$notif) {
                $db->Execute('INSERT INTO {notifications} (task_id, user_id)
                                   VALUES  (?,?)', array($row['task_id'], $user_id));
                Flyspray::logEvent($row['task_id'], 9, $user_id);
            }
        }

        return isset($notif); // only indicates whether or not there have been tries to add a notification
    }


    /**
     * Removes a user $user_id from the notifications list of $tasks
     * @param integer $user_id
     * @param array $tasks
     * @access public
     * @return void
     * @version 1.0
     */

    function remove_notification($user_id, $tasks)
    {
        global $db, $user;

        settype($tasks, 'array');

        if (!count($tasks)) {
            return;
        }

        $sql = $db->Execute(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                          $tasks);

        while ($row = $sql->FetchRow()) {
            // -> user removes himself
            if ($user->id == $user_id) {
                if (!$user->can_view_task($row)) {
                    continue;
                }
            // -> user is removed by someone else
            } else  {
                if (!$user->perms('manage_project', $row['project_id'])) {
                    continue;
                }
            }

            $db->Execute('DELETE FROM  {notifications}
                              WHERE  task_id = ? AND user_id = ?',
                        array($row['task_id'], $user_id));
            if ($db->Affected_Rows()) {
                Flyspray::logEvent($row['task_id'], 10, $user_id);
            }
        }
    }

    /**
     * Adds one or more users to a group (few permission checks)
     * @param string $users komma sperated list of users (preferably IDs)
     * @param integer $group_id of new group
     * @access public
     * @return integer 0:failure 1:added 2:removed from project -1:perms error
     * @version 1.0
     */
    function add_user_to_group($users, $group_id, $proj_id = 0)
    {
        global $db, $user;

        $users = preg_split('/[\s,;]+/', $users, -1, PREG_SPLIT_NO_EMPTY);
        $return = 9;

        foreach ($users as $uid) {
            $uid = Flyspray::username_to_id($uid);

            if (!$uid) {
                $return = min($return, 0);
                continue;
            }

            // Delete from project?
            if (!$group_id && $proj_id) {
                $db->Execute('DELETE uig FROM {users_in_groups} uig
                         LEFT JOIN {groups} g ON uig.group_id = g.group_id
                             WHERE uig.user_id = ? AND g.project_id = ?',
                            array($uid, $proj_id));
                $return = min($return, 1);
                continue;
            }

            // If user is already a member of one of the project's groups, **move** (not add) him to the new group
            $group_project = $db->GetOne('SELECT project_id FROM {groups} WHERE group_id = ?', array($group_id));

            if (!$user->perms('manage_project', $group_project)) {
                $return = min($return, -1);
                continue;
            }

            $oldid = $db->GetOne('SELECT g.group_id
                                    FROM {users_in_groups} uig, {groups} g
                                   WHERE g.group_id = uig.group_id AND uig.user_id = ? AND project_id = ?',
                                   array($uid, $group_project));
            if ($oldid) {
                $db->Execute('UPDATE {users_in_groups} SET group_id = ? WHERE user_id = ? AND group_id = ?',
                            array($group_id, $uid, $oldid));
            } else {
                $db->Execute('INSERT INTO {users_in_groups} (group_id, user_id) VALUES(?, ?)',
                            array($group_id, $uid));
            }
            $return = min($return, 2);
            continue;
        }
        return $return;
    }

    /**
     * Assigns one or more $tasks only to a user $user_id
     * @param integer $user_id
     * @param array $tasks
     * @access public
     * @return void
     * @version 1.0
     */
    function assign_to_me($user_id, $tasks)
    {
        global $db;

        $user = $GLOBALS['user'];
        if ($user_id != $user->id) {
            $user = new User($user_id);
        }

        settype($tasks, 'array');
        if (!count($tasks)) {
            return;
        }

        $sql = $db->Execute(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                          $tasks);

        while ($row = $sql->FetchRow()) {
            if (!$user->can_take_ownership($row)) {
                continue;
            }

            $db->Execute('DELETE FROM {assigned}
                              WHERE task_id = ?',
                        array($row['task_id']));

            $db->Execute('INSERT INTO {assigned}
                                    (task_id, user_id)
                             VALUES (?,?)',
                        array($row['task_id'], $user->id));

            if ($db->Affected_Rows()) {
                Flyspray::logEvent($row['task_id'], 19, $user->id, implode(' ', Flyspray::GetAssignees($row['task_id'])));
                Notifications::send($row['task_id'], ADDRESS_TASK, NOTIFY_OWNERSHIP);
            }
        }
    }

    /**
     * Adds a user $user_id to the assignees of one or more $tasks
     * @param integer $user_id
     * @param array $tasks
     * @param bool $do Force execution independent of user permissions
     * @access public
     * @return void
     * @version 1.0
     */
    function add_to_assignees($user_id, $tasks, $do = false)
    {
        global $db;

        $user = $GLOBALS['user'];
        if ($user_id != $user->id) {
            $user = new User($user_id);
        }

        settype($tasks, 'array');
        if (!count($tasks)) {
            return;
        }

        $sql = $db->Execute(' SELECT *
                              FROM {tasks}
                             WHERE ' . substr(str_repeat(' task_id = ? OR ', count($tasks)), 0, -3),
                          array($tasks));

        while ($row = $sql->FetchRow()) {
            if (!$user->can_add_to_assignees($row) && !$do) {
                continue;
            }

            $db->Replace('{assigned}', array('user_id'=> $user->id, 'task_id'=> $row['task_id']), array('user_id','task_id'), ADODB_AUTOQUOTE);

            if ($db->Affected_Rows()) {
                Flyspray::logEvent($row['task_id'], 29, $user->id, implode(' ', Flyspray::GetAssignees($row['task_id'])));
                Notifications::send($row['task_id'], ADDRESS_TASK, NOTIFY_ADDED_ASSIGNEES);
            }
        }
    }

    /**
     * Adds a vote from $user_id to the task $task_id
     * @param integer $user_id
     * @param integer $task_id
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_vote($user_id, $task_id)
    {
        global $db;

        $user = $GLOBALS['user'];
        if ($user_id != $user->id) {
            $user = new User($user_id);
        }

        $task = Flyspray::GetTaskDetails($task_id);

        if ($user->can_vote($task) > 0) {

            if($db->Execute("INSERT INTO {votes} (user_id, task_id, date_time)
                           VALUES (?,?,?)", array($user->id, $task_id, time()))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Edits the task in $task using the parameters in $args
     * @param array $task a task array
     * @param array $args usually $_POST
     * @access public
     * @return array array(STATUS_CODE, msg)
     */
    function edit_task($task, $args)
    {
        global $user, $db, $fs, $proj;
        if ($proj->id != Post::val('project_id', $task['project_id'])) {
            $proj = new Project(Post::val('project_id', $task['project_id']));
        }

        if (!$user->can_edit_task($task) && !$user->can_correct_task($task)) {
            return array(ERROR_PERMS);
        }

        // check missing fields
        if (!array_get($args, 'item_summary') || !array_get($args, 'detailed_desc')) {
            return array(ERROR_RECOVER, L('summaryanddetails'));
        }

        foreach ($proj->fields as $field) {
            if ($field->prefs['value_required'] && !array_get($args, 'field' . $field->id)
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
                  array($proj->id, array_get($args, 'item_summary'),
                        array_get($args, 'detailed_desc'), intval($user->can_change_private($task) && array_get($args, 'mark_private')),
                        array_get($args, 'task_severity'), intval($user->id), $time,
                        array_get($args, 'percent_complete'), $task['task_id']));

        // Now the custom fields
        foreach ($proj->fields as $field) {
            $field_value = $field->read(array_get($args, 'field' . $field->id));
            $db->Replace('{field_values}',
                         array('field_id'=> $field->id, 'task_id'=> $task['task_id'], 'field_value' => $field_value),
                         array('field_id','task_id'), ADODB_AUTOQUOTE);
        }

        // Prepare assignee list
        $assignees = explode(';', trim(array_get($args, 'assigned_to')));
        $assignees = array_map(array('Flyspray', 'username_to_id'), $assignees);
        $assignees = array_filter($assignees, create_function('$x', 'return ($x > 0);'));

        // Update the list of users assigned this task
        if ($user->perms('edit_assignments') && array_get($args, 'old_assigned') != trim(array_get($args, 'assigned_to')) ) {

            // Delete the current assignees for this task
            $db->Execute('DELETE FROM {assigned}
                                WHERE task_id = ?',
                          array($task['task_id']));

            // Convert assigned_to and store them in the 'assigned' table
            foreach ($assignees as $val)
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
            Flyspray::logEvent($task['task_id'], 3, $change[6], $change[5], $change[4], $time);
        }
        if (count($changes) > 0) {
            Notifications::send($task['task_id'], ADDRESS_TASK, NOTIFY_TASK_CHANGED, array('changes' => $changes));
        }

        if (trim(array_get($args, 'old_assigned')) != implode(' ', $assignees)) {
            // Log to task history
            Flyspray::logEvent($task['task_id'], 14, implode(' ', $assignees), array_get($args, 'old_assigned'), '', $time);

            // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
            if (array_get($args, 'assigned_to') != '') {
                $new_assignees = array_diff($assignees, Flyspray::int_explode(' ', array_get($args, 'old_assigned')));
                // Remove current user from notification list
                if (!$user->infos['notify_own']) {
                    $new_assignees = array_filter($new_assignees, create_function('$u', 'global $user; return $user->id != $u;'));
                }
                if (count($new_assignees)) {
                    Notifications::send($new_assignees, ADDRESS_USER, NOTIFY_NEW_ASSIGNEE, array('task_id' => $task['task_id']));
                }
            }
        }

        Backend::add_comment($task, array_get($args, 'comment_text'), $time);
        Backend::delete_files(array_get($args, 'delete_att'));
        Backend::upload_files($task['task_id'], '0', 'usertaskfile');

        return array(SUBMIT_OK, L('taskupdated'));
    }

    /**
     * Adds a comment to $task
     * @param array $task
     * @param string $comment_text
     * @param integer $time for synchronisation with other functions
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_comment($task, $comment_text, $time = null)
    {
        global $db, $user;

        if (!($user->perms('add_comments', $task['project_id']) && (!$task['is_closed'] || $user->perms('comment_closed', $task['project_id'])))) {
            return false;
        }

        if (!is_string($comment_text) || !strlen($comment_text)) {
            return false;
        }

        $time =  !is_numeric($time) ? time() : $time ;

        $db->Execute('INSERT INTO  {comments}
                                 (task_id, date_added, last_edited_time, user_id, comment_text)
                         VALUES  ( ?, ?, ?, ?, ? )',
                    array($task['task_id'], $time, $time, $user->id, $comment_text));

        $cid = $db->GetOne('SELECT  comment_id
                              FROM  {comments}
                             WHERE  task_id = ?
                          ORDER BY  comment_id DESC',
                            array($task['task_id']), 1);

        Flyspray::logEvent($task['task_id'], 4, $cid);

        if (Backend::upload_files($task['task_id'], $cid)) {
            Notifications::send($task['task_id'], ADDRESS_TASK, NOTIFY_COMMENT_ADDED, array('files' => true, 'cid' => $cid));
        } else {
            Notifications::send($task['task_id'], ADDRESS_TASK, NOTIFY_COMMENT_ADDED, array('cid' => $cid));
        }

        return true;
    }

    /**
     * Upload files for a comment or a task
     * @param integer $task_id
     * @param integer $comment_id if it is 0, the files will be attached to the task itself
     * @param string $source name of the file input
     * @access public
     * @return bool
     * @version 1.0
     */
    function upload_files($task_id, $comment_id = 0, $source = 'userfile')
    {
        global $db, $conf, $user;

        $task = Flyspray::GetTaskDetails($task_id);

        if (!$user->perms('create_attachments', $task['project_id'])) {
            return false;
        }

        $res = false;

		if (!isset($_FILES[$source]['error'])) {
			return false;
		}

        foreach ($_FILES[$source]['error'] as $key => $error) {
            if ($error != UPLOAD_ERR_OK) {
                continue;
            }

            $fname = md5(uniqid(mt_rand(), true));
            $path = FS_ATTACHMENTS_DIR. DIRECTORY_SEPARATOR.  $fname ;

            $tmp_name = $_FILES[$source]['tmp_name'][$key];

            // Then move the uploaded file and remove exe permissions
            if (!move_uploaded_file($tmp_name, $path)) {
                return false;
            }

            @chmod($path, 0644);
            $res = true;

            // Use a different MIME type
            $fileparts = explode('.', $_FILES[$source]['name'][$key]);
			$extension = end($fileparts);
            if (isset($conf['attachments'][$extension])) {
                $_FILES[$source]['type'][$key] = $conf['attachments'][$extension];
            //actually, try really hard to get the real filetype, not what the browser reports.
            } elseif($type = Flyspray::check_mime_type($path)) {
             $_FILES[$source]['type'][$key] = $type;
            }// we can try even more, however, far too much code is needed.

            $db->Execute('INSERT INTO  {attachments}
                                     ( task_id, comment_id, file_name,
                                       file_type, file_size, orig_name,
                                       added_by, date_added )
                             VALUES  (?, ?, ?, ?, ?, ?, ?, ?)',
                    array($task_id, $comment_id, $fname,
                        $_FILES[$source]['type'][$key],
                        $_FILES[$source]['size'][$key],
                        $_FILES[$source]['name'][$key],
                        $user->id, time()));

            // Fetch the attachment id for the history log
            $aid = $db->GetOne('SELECT  attachment_id
                                  FROM  {attachments}
                                 WHERE  task_id = ?
                              ORDER BY  attachment_id DESC',
                                 array($task_id), 1);
            Flyspray::logEvent($task_id, 7, $aid, $_FILES[$source]['name'][$key]);
        }

        return $res;
    }

    /**
     * Delete one or more attachments of a task or comment
     * @param array $attachments
     * @access public
     * @return void
     * @version 1.0
     */
    function delete_files($attachments)
    {
        global $db, $user;

        settype($attachments, 'array');
        if (!count($attachments)) {
            return;
        }

        $sql = $db->Execute(' SELECT t.*, a.*
                              FROM {attachments} a
                         LEFT JOIN {tasks} t ON t.task_id = a.task_id
                             WHERE ' . substr(str_repeat(' attachment_id = ? OR ', count($attachments)), 0, -3),
                          $attachments);

        while ($task = $sql->FetchRow()) {
            if (!$user->perms('delete_attachments', $task['project_id'])) {
                continue;
            }

            $db->Execute('DELETE FROM {attachments} WHERE attachment_id = ?',
                       array($task['attachment_id']));
            @unlink(FS_ATTACHMENTS_DIR . DIRECTORY_SEPARATOR . $task['file_name']);
            Flyspray::logEvent($task['task_id'], 8, $task['orig_name']);
        }
    }

    /**
     * Cleans a username (length, special chars, spaces)
     * @param string $user_name
     * @access public
     * @return string
     */
    function clean_username($user_name)
    {
        // Limit length
        $user_name = substr(trim($user_name), 0, 32);
        // Remove doubled up spaces and control chars
        $user_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $user_name);
        // Strip special chars
        return utf8_keepalphanum($user_name);
    }

    /**
     * Creates a new user
     * @param string $user_name
     * @param string $password
     * @param string $real_name
     * @param string $jabber_id
     * @param string $email
     * @param integer $notify_type
     * @param integer $time_zone
     * @param integer $group_in
     * @access public
     * @return mixed false if username is already taken, otherwise integer uid
     * @version 1.0
     * @notes This function does not have any permission checks (checked elsewhere)
     */
    function create_user($user_name, $password, $real_name, $jabber_id, $email, $notify_type, $time_zone, $group_in)
    {
        global $fs, $db, $baseurl;

        $user_name = Backend::clean_username($user_name);
        // Limit lengths
        $real_name = substr(trim($real_name), 0, 100);
        // Remove doubled up spaces and control chars
        $real_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $real_name);

        // Check to see if the username is available
        $username_exists = $db->GetOne('SELECT COUNT(*) FROM {users} WHERE user_name = ?', array($user_name));

        if ($username_exists) {
            return false;
        }

        $auto = false;
        // Autogenerate a password
        if (!$password) {
            $auto = true;
            $password = substr(md5(uniqid(mt_rand(), true)), 0, mt_rand(8, 12));
        }

        $salt = md5(uniqid(mt_rand() , true));

        $db->Execute("INSERT INTO  {users}
                             ( user_name, user_pass, password_salt, real_name, jabber_id, dateformat,
                               email_address, notify_type, account_enabled, dateformat_extended,
                               tasks_perpage, register_date, time_zone, magic_url)
                     VALUES  ( ?, ?, ?, ?, ?, '', ?, ?, 1, '', 25, ?, ?, '')",
            array($user_name, Flyspray::cryptPassword($password, $salt), $salt, $real_name, $jabber_id, $email, $notify_type, time(), $time_zone));

        // Get this user's id for the record
        $uid = Flyspray::username_to_id($user_name);

        // Now, create a new record in the users_in_groups table
        $db->Execute('INSERT INTO  {users_in_groups} (user_id, group_id)
                           VALUES  (?, ?)', array($uid, $group_in));

        Flyspray::logEvent(0, 30, serialize(Flyspray::getUserDetails($uid)));

        // Add user to project groups
        $sql = $db->Execute('SELECT anon_group FROM {projects} WHERE anon_group != 0');
        while ($row = $sql->FetchRow()) {
            $db->Execute('INSERT INTO  {users_in_groups} (user_id, group_id)
                               VALUES  (?, ?)', array($uid, $row['anon_group']));
        }

        $varnames = array('iwatch','atome','iopened');

        $toserialize = array('string' => null,
                        'type' => array (''),
                        'sev' => array (''),
                        'due' => array (''),
                        'dev' => null,
                        'cat' => array (''),
                        'status' => array ('open'),
                        'order' => null,
                        'sort' => null,
                        'percent' => array (''),
                        'opened' => null,
                        'search_in_comments' => null,
                        'search_for_all' => null,
                        'reported' => array (''),
                        'only_primary' => null,
                        'only_watched' => null);


        foreach($varnames as $tmpname) {

            if($tmpname == 'iwatch') {

                $tmparr = array('only_watched' => '1');

            } elseif ($tmpname == 'atome') {

                $tmparr = array('dev'=> $uid);

            } elseif($tmpname == 'iopened') {

                $tmparr = array('opened'=> $uid);
            }

            $$tmpname = $tmparr + $toserialize;
        }

        // Now give him his default searches
        $db->Execute('INSERT INTO {searches} (user_id, name, search_string, time)
                         VALUES (?, ?, ?, ?)',
                    array($uid, L('taskswatched'), serialize($iwatch), time()));
        $db->Execute('INSERT INTO {searches} (user_id, name, search_string, time)
                         VALUES (?, ?, ?, ?)',
                    array($uid, L('assignedtome'), serialize($atome), time()));
        $db->Execute('INSERT INTO {searches} (user_id, name, search_string, time)
                         VALUES (?, ?, ?, ?)',
                    array($uid, L('tasksireported'), serialize($iopened), time()));

        // Send a user his details (his username might be altered, password auto-generated)
        if ($fs->prefs['notify_registration']) {
            $sql = $db->Execute('SELECT u.user_id
                                 FROM {users} u
                            LEFT JOIN {users_in_groups} g ON u.user_id = g.user_id
                                WHERE g.group_id = 1');
            Notifications::send($sql->GetArray(), ADDRESS_USER, NOTIFY_NEW_USER,
                          array($baseurl, $user_name, $real_name, $email, $jabber_id, $password, $auto));
        }

        return $uid;
    }

    /**
     * Deletes a user
     * @param integer $uid
     * @access public
     * @return bool
     * @version 1.0
     */
    function delete_user($uid)
    {
        global $db, $user;

        if (!$user->perms('is_admin')) {
            return false;
        }

        $user_data = serialize(Flyspray::getUserDetails($uid));
        $tables = array('users', 'users_in_groups', 'searches',
                        'notifications', 'assigned');

        foreach ($tables as $table) {
            if (!$db->Execute('DELETE FROM ' .'{' . $table .'}' . ' WHERE user_id = ?', array($uid))) {
                return false;
            }
        }

        Flyspray::logEvent(0, 31, $user_data);

        return true;
    }

    /**
     * Deletes a project
     * @param integer $pid
     * @param integer $move_to to which project contents of the project are moved
     * @access public
     * @return bool
     * @version 1.0
     */
    function delete_project($pid, $move_to = 0)
    {
        global $db, $user;

        if (!$user->perms('manage_project', $pid)) {
            return false;
        }

        $tables = array('lists', 'admin_requests',
                        'cache', 'projects', 'tasks');

        foreach ($tables as $table) {
            if ($move_to && $table !== 'projects') {
                $base_sql = 'UPDATE {' . $table . '} SET project_id = ?';
                $sql_params = array($move_to, $pid);
            } else {
                $base_sql = 'DELETE FROM {' . $table . '}';
                $sql_params = array($pid);
            }

            if (!$db->Execute($base_sql . ' WHERE project_id = ?', $sql_params)) {
                return false;
            }
        }

        // groups are only deleted, not moved (it is likely
        // that the destination project already has all kinds
        // of groups which are also used by the old project)
        $sql = $db->Execute('SELECT group_id FROM {groups} WHERE project_id = ?', array($pid));
        while ($row = $sql->FetchRow()) {
            $db->Execute('DELETE FROM {users_in_groups} WHERE group_id = ?', array($row['group_id']));
        }
        $sql = $db->Execute('DELETE FROM {groups} WHERE project_id = ?', array($pid));

        //we have enough reasons ..  the process is OK.
        return true;
    }

    /**
     * Adds a reminder to a task
     * @param integer $task_id
     * @param string $message
     * @param integer $how_often send a reminder every ~ seconds
     * @param integer $start_time time when the reminder starts
     * @param $user_id the user who is reminded. by default (null) all users assigned to the task are reminded.
     * @access public
     * @return bool
     * @version 1.0
     */
    function add_reminder($task_id, $message, $how_often, $start_time, $user_id = null)
    {
        global $user, $db;
        $task = Flyspray::GetTaskDetails($task_id);

        if (!$user->perms('manage_project', $task['project_id'])) {
            return false;
        }

        if (is_null($user_id)) {
            // Get all users assigned to a task
            $user_id = Flyspray::GetAssignees($task_id);
        } else {
            $user_id = array(Flyspray::username_to_id($user_id));
            if (!reset($user_id)) {
                return false;
            }
        }

        foreach ($user_id as $id) {
            $sql = $db->Replace('{reminders}',
                                array('task_id'=> $task_id, 'to_user_id'=> $id,
                                     'from_user_id' => $user->id, 'start_time' => $start_time,
                                     'how_often' => $how_often, 'reminder_message' => $message),
                                array('task_id', 'to_user_id', 'how_often', 'reminder_message'), ADODB_AUTOQUOTE);
            if(!$sql) {
                // query has failed :(
                return false;
            }
        }
        // 2 = no record has found and was INSERT'ed correclty
        if (isset($sql) && $sql == 2) {
            Flyspray::logEvent($task_id, 17, $task_id);
        }
        return true;
    }

    /**
     * Adds a new task
     * @param array $args array containing all task properties. unknown properties will be ignored
     * @access public
     * @return array(error type, msg, false) or array(task ID, token, true)
     * @version 1.0
     * @notes $args is POST data, bad..bad user..
     */
    function create_task($args)
    {
        global $db, $user, $proj, $fs;

        if ($proj->id !=  $args['project_id']) {
            $proj = new Project($args['project_id']);
        }

        if (!$user->can_open_task($proj) || count($args) < 3) {
            return array(ERROR_RECOVER, L('missingrequired'), false);
        }

        // check required fields
        if (!(($item_summary = $args['item_summary']) && ($detailed_desc = $args['detailed_desc']))) {
            return array(ERROR_RECOVER, L('summaryanddetails'), false);
        }

        foreach ($proj->fields as $field) {
            if ($field->prefs['value_required'] && !array_get($args, 'field' . $field->id)
                && !($field->prefs['force_default'] && !$user->perms('modify_all_tasks'))) {
                return array(ERROR_RECOVER, L('missingrequired') . ' (' . $field->prefs['field_name'] . ')', false);
            }
        }

        if ($user->isAnon() && $fs->prefs['use_recaptcha']) {
            include_once BASEDIR . '/includes/external/recaptchalib.php';
            $solution =& new reCAPTCHA_Solution();
            $solution->privatekey = $fs->prefs['recaptcha_private_key'];
            $solution->challenge = Post::val('recaptcha_challenge_field');
            $solution->response = Post::val('recaptcha_response_field');
            $solution->remoteip = $_SERVER['REMOTE_ADDR'];

            if(!$solution->isValid()) {
                return array(ERROR_RECOVER, $solution->error_code, false);
            }
        }

        $sql_values = array(time(), time(), $args['project_id'], $item_summary,
                            $detailed_desc, intval($user->id), 0);

        $sql_params[] = 'task_severity';
        $sql_values[] = array_get($args, 'task_severity', 2); // low severity

        $sql_params[] = 'mark_private';
        $sql_values[] = isset($args['mark_private']) && $args['mark_private'] == '1';

        $sql_params[] = 'closure_comment';
        $sql_values[] = '';

        // Token for anonymous users
        $token = '';
        if ($user->isAnon()) {
            $token = md5(uniqid(mt_rand(), true));
            $sql_params[] = 'task_token';
            $sql_values[] = $token;
        }

        $sql_params[] = 'anon_email';
        $sql_values[] = array_get($args, 'anon_email', '');

        $sql_params = join(', ', $sql_params);
        $sql_placeholder = join(', ', array_fill(1, count($sql_values), '?'));

        $task_id = $db->GetOne('SELECT  MAX(task_id)+1 FROM  {tasks}');
        $task_id = $task_id ? $task_id : 1;
        //now, $task_id is always the first element of $sql_values
        array_unshift($sql_values, $task_id);

        $result = $db->Execute("INSERT INTO  {tasks}
                                 ( task_id, date_opened, last_edited_time,
                                   project_id, item_summary,
                                   detailed_desc, opened_by,
                                   percent_complete, $sql_params )
                         VALUES  (?, $sql_placeholder)", $sql_values);

        // Per project task ID
        $prefix_id = $db->GetOne('SELECT MAX(prefix_id)+1 FROM {tasks} WHERE project_id = ?', array($proj->id));
        $db->Execute('UPDATE {tasks} SET prefix_id = ? WHERE task_id = ?', array($prefix_id, $task_id));

        $values = array();
        // Now the custom fields
        foreach ($proj->fields as $field) {
            $values[] = array($task_id, $field->id, $field->read(array_get($args, 'field' . $field->id, 0)));
        }
        if(count($values)) {
            $db->Execute('INSERT INTO {field_values} (task_id, field_id, field_value)
                          VALUES (?, ?, ?)', $values);
        }

        if(isset($args['assigned_to'])) {
        // Prepare assignee list
        $assignees = explode(';', trim($args['assigned_to']));
        $assignees = array_map(array('Flyspray', 'username_to_id'), $assignees);
        $assignees = array_filter($assignees, create_function('$x', 'return ($x > 0);'));

        // Log the assignments and send notifications to the assignees
        if (count($assignees)) {
            // Convert assigned_to and store them in the 'assigned' table
            foreach ($assignees as $val) {
                $db->Replace('{assigned}', array('user_id'=> $val, 'task_id'=> $task_id), array('user_id','task_id'), ADODB_AUTOQUOTE);
            }

            Flyspray::logEvent($task_id, 14, implode(' ', $assignees));

            // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
            Notifications::send($assignees, ADDRESS_USER, NOTIFY_NEW_ASSIGNEE, array('task_id' => $task_id));
        }
    }

        // Log that the task was opened
        Flyspray::logEvent($task_id, 1);

        // find category owners
        $owners = array();
        foreach ($proj->fields as $field) {
            if ($field->prefs['list_type'] != LIST_CATEGORY) {
                continue;
            }

            $sql = $db->Execute('SELECT  *
                                 FROM  {list_category}
                                WHERE  category_id = ?',
                               array(array_get($args, 'field' . $field->id, 0)));
            $cat = $sql->FetchRow();

            if ($cat['category_owner']) {
                $owners[] = $cat['category_owner'];
            } else {
            // check parent categories
                $sql = $db->Execute('SELECT  *
                                     FROM  {list_category}
                                    WHERE  lft < ? AND rgt > ? AND list_id  = ?
                                 ORDER BY  lft DESC',
                                   array($cat['lft'], $cat['rgt'], $cat['list_id']));
                while ($row = $sql->FetchRow()) {
                    // If there's a parent category owner, send to them
                    if ($row['category_owner']) {
                        $owners[] = $row['category_owner'];
                        break;
                    }
                }
            }
        }
        // last try...
        if (!count($owners) && $proj->prefs['default_cat_owner']) {
            $owners[] = $proj->prefs['default_cat_owner'];
        }

        if (count($owners)) {
            foreach ($owners as $owner) {
                if ($proj->prefs['auto_assign'] && !in_array($owner, $assignees)) {
                    Backend::add_to_assignees($owner, $task_id, true);
                }

                Backend::add_notification($owner, $task_id, true);
            }
        }

        // Create the Notification
        if (Backend::upload_files($task_id)) {
            Notifications::send($task_id, ADDRESS_TASK, NOTIFY_TASK_OPENED, array('files' => true));
        } else {
            Notifications::send($task_id, ADDRESS_TASK, NOTIFY_TASK_OPENED);
        }


        // If the reporter wanted to be added to the notification list
        if (isset($args['notifyme']) && $args['notifyme'] == '1' && !in_array($user->id, $owners)) {
            Backend::add_notification($user->id, $task_id, true);
        }
        // this is relaxed, if the anonymous email is not valid, just dont bother..
        if ($user->isAnon() && Flyspray::check_email($args['anon_email'])) {
            Notifications::send($args['anon_email'], ADDRESS_EMAIL, NOTIFY_ANON_TASK, array('task_id' => $task_id, 'token' => $token));
        }

        return array($task_id, $token, true);
    }

    /**
     * Closes a task
     * @param integer $task_id
     * @param integer $reason
     * @param string $comment
     * @param bool $mark100
     * @access public
     * @return bool
     * @version 1.0
     */
    function close_task($task_id, $reason, $comment, $mark100 = true)
    {
        global $db, $user, $fs;
        $task = Flyspray::GetTaskDetails($task_id);

        if (!$user->can_close_task($task)) {
            return false;
        }

        if ($task['is_closed']) {
            return false;
        }

        $db->Execute('UPDATE  {tasks}
                       SET  date_closed = ?, closed_by = ?, closure_comment = ?,
                            is_closed = 1, resolution_reason = ?, last_edited_time = ?,
                            last_edited_by = ?, percent_complete = ?
                     WHERE  task_id = ?',
                    array(time(), $user->id, $comment, $reason, time(), $user->id, ((bool) $mark100) * 100, $task_id));

        if ($mark100) {
            Flyspray::logEvent($task_id, 3, 100, $task['percent_complete'], 'percent_complete');
        }

        Notifications::send($task_id, ADDRESS_TASK, NOTIFY_TASK_CLOSED);
        Flyspray::logEvent($task_id, 2, $reason, $comment);

        // If there's an admin request related to this, close it
        $db->Execute('UPDATE  {admin_requests}
                       SET  resolved_by = ?, time_resolved = ?
                     WHERE  task_id = ? AND request_type = ?',
                    array($user->id, time(), $task_id, 1));

        // duplicate
        if ($reason == $fs->prefs['resolution_dupe']) {
            $look = array('FS#', 'bug ');
            foreach ($fs->projects as $project) {
                $look[] = preg_quote($project['project_prefix'] . '#', '/');
            }
            preg_match("/\b(" . implode('|', $look) . ")(\d+)\b/", $comment, $dupe_of);
            if (count($dupe_of) >= 2) {
                $existing = $db->Execute('SELECT * FROM {related} WHERE this_task = ? AND related_task = ? AND related_type = 1',
                                        array($task_id, $dupe_of[1]));

                if (!$existing->FetchRow()) {
                    $db->Execute('INSERT INTO {related} (this_task, related_task, related_type) VALUES(?, ?, 1)',
                                array($task_id, $dupe_of[1]));
                }
                Backend::add_vote($task['opened_by'], $dupe_of[1]);
            }
        }

        return true;
    }

    /**
     * Returns an array of tasks (respecting pagination) and an ID list (all tasks)
     * @param array $args call by reference because we have to modifiy $_GET if we use default values from a user profile
     * @param array $visible
     * @param integer $offset
     * @param integer $comment
     * @param bool $perpage
     * @access public
     * @return array
     * @version 1.0
     */
    function get_task_list(&$args, $visible, $offset = 0, $perpage = null)
    {
        global $proj, $db, $user, $conf;
        /* build SQL statement {{{ */
        // Original SQL courtesy of Lance Conry http://www.rhinosw.com/
        $where  = $sql_params = array();

        $select = '';
        $groupby = '';
        $from   = '             {tasks}         t
                     LEFT JOIN  {projects}      p   ON t.project_id = p.project_id
                     LEFT JOIN  {list_items} lr ON t.resolution_reason = lr.list_item_id ';
        // Only join tables which are really necessary to speed up the db-query
        if (in_array('votes', $visible)) {
            $from   .= ' LEFT JOIN  {votes} vot         ON t.task_id = vot.task_id ';
            $select .= ' COUNT(DISTINCT vot.vote_id)    AS num_votes, ';
        }
        $search_for_changes = in_array('lastedit', $visible) || array_get($args, 'changedto') || array_get($args, 'changedfrom');
        if (array_get($args, 'search_in_comments') || in_array('comments', $visible) || $search_for_changes) {
            $from   .= ' LEFT JOIN  {comments} c        ON t.task_id = c.task_id ';
            $select .= ' COUNT(DISTINCT c.comment_id)   AS num_comments, ';
            // in other words: max(max(c.date_added), t.date_closed, t.date_opened, t.last_edited_time)
            if ($search_for_changes) {
                $select .= ' CASE WHEN max(c.date_added)>t.date_closed THEN
                                CASE WHEN max(c.date_added)>t.date_opened THEN CASE WHEN max(c.date_added) > t.last_edited_time THEN max(c.date_added) ELSE t.last_edited_time END ELSE
                                    CASE WHEN t.date_opened > t.last_edited_time THEN t.date_opened ELSE t.last_edited_time END END ELSE
                                CASE WHEN t.date_closed>t.date_opened THEN CASE WHEN t.date_closed > t.last_edited_time THEN t.date_closed ELSE t.last_edited_time END ELSE
                                    CASE WHEN t.date_opened > t.last_edited_time THEN t.date_opened ELSE t.last_edited_time END END END AS max_date, ';
            }
            $groupby .= 'c.date_added, ';
        }
        if (array_get($args, 'opened') || in_array('openedby', $visible)) {
            $from   .= ' LEFT JOIN  {users} uo          ON t.opened_by = uo.user_id ';
            $select .= ' uo.real_name                   AS opened_by_name, ';
            $groupby .= 'uo.real_name, ';
        }
        if (array_get($args, 'closed')) {
            $from   .= ' LEFT JOIN  {users} uc          ON t.closed_by = uc.user_id ';
            $select .= ' uc.real_name                   AS closed_by_name, ';
            $groupby .= 'uc.real_name, ';
        }
        if (in_array('attachments', $visible) || array_get($args, 'has_attachment')) {
            $from   .= ' LEFT JOIN  {attachments} att   ON t.task_id = att.task_id ';
            $select .= ' COUNT(DISTINCT att.attachment_id) AS num_attachments, ';
        }

        $from   .= ' LEFT JOIN  {assigned} ass      ON t.task_id = ass.task_id ';
        $from   .= ' LEFT JOIN  {users} u           ON ass.user_id = u.user_id ';
        if (array_get($args, 'dev') || in_array('assignedto', $visible)) {
            $select .= ' MIN(u.real_name)               AS assigned_to_name, ';
            $select .= ' COUNT(DISTINCT ass.user_id)    AS num_assigned, ';
        }

        if (array_get($args, 'only_primary')) {
            $from   .= ' LEFT JOIN  {dependencies} dep  ON dep.dep_task_id = t.task_id ';
            $where[] = 'dep.depend_id IS null';
        }
        if (array_get($args, 'has_attachment')) {
            $where[] = 'att.attachment_id IS NOT null';
        }

        // sortable default fields
        $order_keys = array (
                'id'           => 't.task_id',
                'project'      => 'project_title',
                'dateopened'   => 'date_opened',
                'summary'      => 'item_summary',
                'severity'     => 'task_severity',
                'progress'     => 'percent_complete',
                'lastedit'     => 'max_date',
                'openedby'     => 'uo.real_name',
                'assignedto'   => 'u.real_name',
                'dateclosed'   => 't.date_closed',
                'votes'        => 'num_votes',
                'attachments'  => 'num_attachments',
                'comments'     => 'num_comments',
                'state'        => 'closed_by, is_closed',
                'projectlevelid' => 'prefix_id',
        );
        // custom sortable fields
        foreach ($proj->fields as $field) {
            $order_keys['field' . $field->id] = 'field' . $field->id;
        }

        // Default user sort column and order
        if (!$user->isAnon()) {
            if (!isset($args['sort'])) {
                $args['sort'] = $user->infos['defaultorder'];
            }

            if (!isset($args['order'])) {
                $usercolumns = explode(' ', $user->infos['defaultsortcolumn']);
                foreach ($usercolumns as $column) {
                    if (isset($order_keys[$column])) {
                        $args['order'] = $column;
                        break;
                    }
                }
            }
        }

        // make sure that only columns can be sorted that are visible (and task severity, since it is always loaded)
        $order_keys = array_intersect_key($order_keys, array_merge(array_flip($visible), array('severity' => 'task_severity')));

        $order_column[0] = $order_keys[Filters::enum(array_get($args, 'order', 'severity'), array_keys($order_keys))];
        $order_column[1] = $order_keys[Filters::enum(array_get($args, 'order2', 'priority'), array_keys($order_keys))];

        $sortorder  = sprintf('%s %s, %s %s, t.task_id ASC',
                $order_column[0], Filters::enum(array_get($args, 'sort', 'desc'), array('asc', 'desc')),
                $order_column[1], Filters::enum(array_get($args, 'sort2', 'desc'), array('asc', 'desc')));

        // search custom fields
        $custom_fields_joined = array();
        foreach ($proj->fields as $field) {
            $ref = 'field' . $field->id;
            if ($field->prefs['field_type'] == FIELD_DATE) {
                if (!array_get($args, $field->id . 'from') && !array_get($args, $field->id . 'to')) {
                    continue;
                }

                $from   .= " LEFT JOIN {field_values} {$ref} ON t.task_id = {$ref}.task_id AND {$ref}.field_id = ? ";
                $sql_params[] = $field->id;
                $custom_fields_joined[] = $field->id;

                if ($date = array_get($args, $field->id . 'from')) {
                    $where[]      = "({$ref}.field_value >= ?)";
                    $sql_params[] = Flyspray::strtotime($date);
                }
                if ($date = array_get($args, $field->id . 'to')) {
                    $where[]      = "({$ref}.field_value <= ? AND {$ref}.field_value > 0)";
                    $sql_params[] = Flyspray::strtotime($date);
                }
            } elseif ($field->prefs['field_type'] == FIELD_LIST) {
                if (in_array('', (array) array_get($args, 'field' . $field->id, array('')))) {
                    continue;
                }

                $from   .= " LEFT JOIN {field_values} {$ref} ON t.task_id = {$ref}.task_id AND {$ref}.field_id = ? ";
                $sql_params[] = $field->id;
                $custom_fields_joined[] = $field->id;
                $fwhere = array();

                foreach ($args['field' . $field->id] as $val) {
                    $fwhere[] = " {$ref}.field_value = ? ";
                    $sql_params[] = $val;
                }
                if (count($fwhere)) {
                    $where[] =  ' (' . implode(' OR ', $fwhere) . ') ';
                }
            } else {
                if ( !($val = array_get($args, 'field' . $field->id)) ) {
                    continue;
                }

                $from   .= " LEFT JOIN {field_values} {$ref} ON t.task_id = {$ref}.task_id AND {$ref}.field_id = ? ";
                $sql_params[] = $field->id;
                $custom_fields_joined[] = $field->id;
                $where[] = "({$ref}.field_value LIKE ?)";
                $sql_params[] = ($field->prefs['field_type'] == FIELD_USER) ? Flyspray::username_to_id($val) : $val;
            }
        }
        // now join custom fields used in columns
        foreach ($proj->columns as $col => $name) {
            if (preg_match('/^field(\d+)$/', $col, $match) && in_array($col, $visible)) {
                if (!in_array($match[1], $custom_fields_joined)) {
                    $from   .= " LEFT JOIN {field_values} $col ON t.task_id = $col.task_id AND $col.field_id = " . intval($match[1]);
                }
                $from .= " LEFT JOIN {fields} f$col ON f$col.field_id = $col.field_id ";

                // join special tables for certain fields
                if ($proj->fields['field' . $match[1]]->prefs['field_type'] == FIELD_LIST) {
                    $from .= "LEFT JOIN {list_items} li$col ON (f$col.list_id = li$col.list_id AND $col.field_value = li$col.list_item_id)
                              LEFT JOIN {list_category} lc$col ON (f$col.list_id = lc$col.list_id AND $col.field_value = lc$col.category_id) ";
                    $select .= " li$col.item_name AS {$col}_name, ";

                } else if ($proj->fields['field' . $match[1]]->prefs['field_type'] == FIELD_USER) {
                    $from .= " LEFT JOIN {users} u$col ON $col.field_value = u$col.user_id ";
                    $select .= " u$col.user_name AS {$col}_name, ";
                }

                $select .= "$col.field_value AS $col, "; // adding data to queries not nice, but otherwise sql_params and joins are not in sync
            }
        }

        // open / closed (never thought that I'd use XOR some time)
        if (in_array('open', array_get($args, 'status', array('open'))) XOR in_array('closed', array_get($args, 'status', array()))) {
            $where[] = ' is_closed = ? ';
            $sql_params[] = (int) in_array('closed', array_get($args, 'status', array()));
        }

        /// process search-conditions {{{
        $submits = array('sev' => 'task_severity',
                         'percent' => 'percent_complete',
                         'dev' => array('a.user_id', 'us.user_name'),
                         'opened' => array('opened_by', 'uo.user_name'),
                         'closed' => array('closed_by', 'uc.user_name'));
        // add custom user fields

        foreach ($submits as $key => $db_key) {
            $type = array_get($args, $key, '');
            settype($type, 'array');

            if (in_array('', $type)) continue;

            if ($key == 'dev') {
                $from .= 'LEFT JOIN {assigned} a  ON t.task_id = a.task_id ';
                $from .= 'LEFT JOIN {users} us  ON a.user_id = us.user_id ';
            }

            $temp = '';
            $condition = '';
            foreach ($type as $val) {
                if (is_numeric($val) && !is_array($db_key)) {
                    $temp .= ' ' . $db_key . ' = ?  OR';
                    $sql_params[] = $val;
                } elseif (is_array($db_key)) {
                    if ($key == 'dev' && ($val == 'notassigned' || $val == '0' || $val == '-1')) {
                        $temp .= ' a.user_id IS NULL  OR';
                    } else {
                        if (is_numeric($val)) {
                            $condition = ' = ? OR';
                        } else {
                           $val = '%' . $val . '%';
                           $condition = ' LIKE ? OR';
                        }
                        foreach ($db_key as $value) {
                            $temp .= ' ' . $value . $condition;
                            $sql_params[] = $val;
                        }
                    }
                }
            }

            if ($temp) $where[] = '(' . substr($temp, 0, -3) . ')';
        }
        /// }}}

        $having = array();
        $dates = array('due_date', 'changed' => 'max_date', 'opened' => 'date_opened', 'closed' => 'date_closed');
        foreach ($dates as $post => $db_key) {
            $var = ($post == 'changed') ? 'having' : 'where';
            if ($date = array_get($args, $post . 'from')) {
                ${$var}[]      = '(' . $db_key . ' >= ' . Flyspray::strtotime($date) . ')';
            }
            if ($date = array_get($args, $post . 'to')) {
                ${$var}[]      = '(' . $db_key . ' <= ' . Flyspray::strtotime($date) . ' AND ' . $db_key . ' > 0)';
            }
        }

        if (array_get($args, 'string')) {
            $words = explode(' ', strtr(array_get($args, 'string'), '()', '  '));
            $comments = '';
            $where_temp = array();

            if (array_get($args, 'search_in_comments')) {
                $comments .= 'OR c.comment_text LIKE ?';
            }
            if (array_get($args, 'search_in_details')) {
                $comments .= 'OR t.detailed_desc LIKE ?';
            }

            foreach ($words as $word) {
                $word = '%' . str_replace('+', ' ', trim($word)) . '%';
                $where_temp[] = "(t.item_summary LIKE ? OR t.task_id LIKE ? $comments)";
                array_push($sql_params, $word, $word);
                if (array_get($args, 'search_in_comments')) {
                    array_push($sql_params, $word);
                }
                if (array_get($args, 'search_in_details')) {
                    array_push($sql_params, $word);
                }
            }

            $where[] = '(' . implode( (array_get($args, 'search_for_all') ? ' AND ' : ' OR '), $where_temp) . ')';
        }

        if (array_get($args, 'only_watched')) {
            //join the notification table to get watched tasks
            $from        .= ' LEFT JOIN {notifications} fsn ON t.task_id = fsn.task_id';
            $where[]      = 'fsn.user_id = ?';
            $sql_params[] = $user->id;
        }

        if ($proj->id) {
            $where[]       = 't.project_id = ?';
            $sql_params[]  = $proj->id;
        } else {
            $tmpwhere = array();
            foreach (array_get($args, 'search_project', array()) as $id) {
                if ($id) {
                    $tmpwhere[]       = 't.project_id = ?';
                    $sql_params[]  = $id;
                }
            }
            if (count($tmpwhere)) {
                $where[] = '(' . implode(' OR ', $tmpwhere) . ')';
            }
        }

        $where = (count($where)) ? 'WHERE '. join(' AND ', $where) : '';

        // Get the column names of table tasks for the group by statement
        if (!strcasecmp($conf['database']['dbtype'], 'pgsql')) {
             $groupby .= "p.project_title, lst.item_name, lt.item_name,{$order_column[0]},{$order_column[1]}, lr.item_name, ";
             $groupby .= GetColumnNames('{tasks}', 't.task_id', 't');
        } else {
            $groupby = 't.task_id';
        }

        $having = (count($having)) ? 'HAVING '. join(' AND ', $having) : '';

        $sql = $db->Execute("
                          SELECT   t.*, $select
                                   p.project_title, p.project_prefix,
                                   lr.item_name AS resolution_name
                          FROM     $from
                          $where
                          GROUP BY $groupby
                          $having
                          ORDER BY $sortorder", $sql_params);

        $tasks = $sql->GetArray();
        $id_list = array();
        $limit = array_get($args, 'limit', -1);
        $task_count = 0;
        foreach ($tasks as $key => $task) {
            $id_list[] = $task['task_id'];
            if (!$user->can_view_task($task)) {
                unset($tasks[$key]);
                array_pop($id_list);
                --$task_count;
            } elseif ($perpage && ($task_count < $offset || ($task_count > $offset - 1 + $perpage) || ($limit > 0 && $task_count >= $limit))) {
                unset($tasks[$key]);
            }

            ++$task_count;
        }

        return array($tasks, $id_list);
    }

}
?>
