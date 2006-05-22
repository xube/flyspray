<?php
/*
   ------------------------------------------------------------
   | This script contains reusable functions we use to modify |
   | various things in the Flyspray database tables.          |
   ------------------------------------------------------------
*/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class Backend
{
    /* This function is used to ADD a user to the
       Notification list of multiple tasks (if desired).
       Expected args are user_id and an array of tasks
    */
    function AddToNotifyList($user_id, $tasks, $do = false)
    {
        global $db, $fs, $user;

        settype($tasks, 'array');
        
        if (!is_numeric($user_id)) {
            $sql = $db->Query('SELECT user_id FROM {users} WHERE user_name = ?', array($user_id));
            $user_id = $db->FetchOne($sql);
        }
        
        foreach ($tasks as $key => $task) {
            $tasks[$key] = 't.task_id = ' . $task;
        }
        $where = implode(' OR ', $tasks);

        // XXX keep permission checks in sync with class.user.php!
        $sql = $db->Query(" SELECT t.task_id
                              FROM {tasks} t
                         LEFT JOIN {projects} p ON p.project_id = t.task_id
                         LEFT JOIN {assigned} a ON t.task_id = a.task_id AND a.user_id = ?
                         LEFT JOIN {groups} g ON g.belongs_to_project=p.project_id OR g.belongs_to_project = 0
                         LEFT JOIN {groups} g2 ON g2.belongs_to_project=p.project_id OR g2.belongs_to_project = 0
                         LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                         LEFT JOIN {users_in_groups} uig2 ON uig2.group_id = g2.group_id
                         LEFT JOIN {users} u ON u.user_id = uig.user_id AND u.user_id = ?
                         LEFT JOIN {users} u2 ON u2.user_id = uig2.user_id AND uig2.user_id = ?
                             WHERE ($where) AND u.user_name is NOT NULL AND u2.user_name is NOT NULL
                                   AND (? = ?
                                       AND (t.opened_by = u.user_id
                                        OR (t.mark_private = 0 AND (g.view_tasks = 1 OR p.others_view = 1))
                                        OR a.user_id is NOT NULL
                                        OR g.manage_project = 1
                                        OR g.is_admin = 1)
                                   OR (g2.is_admin = 1 OR g2.manage_project = 1))
                          GROUP BY t.task_id", array($user_id, $user_id, $user->id, $user_id, $user->id));

        while ($row = $db->FetchRow($sql)) {
            $notif = $db->Query('SELECT notify_id
                                   FROM {notifications}
                                  WHERE task_id = ? and user_id = ?',
                              array($row['task_id'], $user_id));
           
            if (!$db->CountRows($notif)) {
                $db->Query('INSERT INTO {notifications} (task_id, user_id)
                                 VALUES  (?,?)', array($row['task_id'], $user_id));
                $fs->logEvent($row['task_id'], 9, $user_id);
            }
        }
        
        return (bool) $db->CountRows($sql);
    }


    /* This function is used to REMOVE a user from the
       Notification list of multiple tasks (if desired).
       Expected args are user_id and an array of tasks.
    */
    function RemoveFromNotifyList($user_id, $tasks)
    {
        global $db, $fs, $user;

        settype($tasks, 'array');
                
        foreach ($tasks as $key => $task) {
            $tasks[$key] = 't.task_id = ' . $task;
        }
        $where = implode(' OR ', $tasks);

        // XXX keep permission checks in sync with class.user.php!
        $sql = $db->Query(" SELECT t.task_id
                              FROM {tasks} t
                         LEFT JOIN {projects} p ON p.project_id = t.task_id
                         LEFT JOIN {assigned} a ON t.task_id = a.task_id AND a.user_id = ?
                         LEFT JOIN {groups} g ON g.belongs_to_project=p.project_id OR g.belongs_to_project = 0
                         LEFT JOIN {groups} g2 ON g2.belongs_to_project=p.project_id OR g2.belongs_to_project = 0
                         LEFT JOIN {users_in_groups} uig ON uig.group_id = g.group_id
                         LEFT JOIN {users_in_groups} uig2 ON uig2.group_id = g2.group_id
                         LEFT JOIN {users} u ON u.user_id = uig.user_id AND u.user_id = ?
                         LEFT JOIN {users} u2 ON u2.user_id = uig2.user_id AND uig2.user_id = ?
                             WHERE ($where) AND u.user_name is NOT NULL AND u2.user_name is NOT NULL
                                   AND (? = ? OR g2.is_admin = 1 OR g2.manage_project = 1)
                          GROUP BY t.task_id", array($user_id, $user_id, $user->id, $user_id, $user->id));

        while ($row = $db->FetchRow($sql)) {
            $db->Query('DELETE FROM  {notifications}
                              WHERE  task_id = ? AND user_id = ?',
                        array($row['task_id'], $user_id));
            if ($db->affectedRows()) {
                $fs->logEvent($row['task_id'], 10, $user_id);
            }
        }
    }


    /* This function is for a user to assign multiple tasks to themselves.
       Expected args are user_id and an array of tasks.
    */
    function AssignToMe($user_id, $tasks)
    {
        global $db, $fs, $notify;

        settype($tasks, 'array');
        
        // XXX keep permission checks in sync with class.user.php!
        foreach ($tasks as $key => $task) {
            $tasks[$key] = 't.task_id = ' . $task;
        }
        $where = implode(' OR ', $tasks);
        
        $sql = $db->Query(" SELECT t.task_id
                              FROM {tasks} t
                         LEFT JOIN {projects} p ON p.project_id = t.task_id
                         LEFT JOIN {groups} g ON g.belongs_to_project = p.project_id OR g.belongs_to_project = 0
                         LEFT JOIN {users_in_groups} uig ON g.group_id = uig.group_id
                         LEFT JOIN {users} u ON u.user_id = uig.user_id AND u.user_id = ?
                         LEFT JOIN {assigned} ass ON t.task_id = ass.task_id
                             WHERE ($where) AND u.user_name is NOT NULL
                                            AND g.edit_assignments = 1
                                            AND (g.assign_to_self = 1 AND ass.user_id is NULL OR g.assign_others_to_self = 1 AND ass.user_id != ?)
                          GROUP BY t.task_id", array($user_id, $user_id));
        
        while ($row = $db->FetchRow($sql)) {
            $db->Query('DELETE FROM {assigned}
                              WHERE task_id = ?',
                        array($row['task_id']));

            $db->Query('INSERT INTO {assigned}
                                    (task_id, user_id)
                             VALUES (?,?)',
                        array($row['task_id'], $user_id));
            
            if ($db->affectedRows()) {
                $fs->logEvent($row['task_id'], 19, $user_id, implode(' ', $task['assigned_to']));
                $notify->Create(NOTIFY_OWNERSHIP, $row['task_id']);
            }
            
            if ($row['item_status'] == STATUS_UNCONFIRMED || $row['item_status'] == STATUS_NEW) {
                $db->Query('UPDATE {tasks} SET item_status = 3 WHERE task_id = ?', array($row['task_id']));
                $fs->logEvent($task_id, 0, 3, 1, 'item_status');
            }
        }
    }
    
    /* This function is for a user to assign multiple tasks to themselves.
       Expected args are user_id and an array of tasks.
    */
    function AddToAssignees($user, $tasks)
    {
        global $db, $fs, $notify;
        
        if (!is_object($user)) {
            $user = new User($user);
        }

        settype($tasks, 'array');

        foreach ($tasks as $key => $task_id) {
            // Get the task details
            // FIXME make it less greedy in term of SQL
            $task = @$fs->getTaskDetails($task_id);
            $proj = new Project($task['attached_to_project']);
            $user->get_perms($proj);
            
            if ($user->can_view_project($proj)
                    && $user->can_view_task($task)
                    && $user->can_add_to_assignees($task))
            {
               $db->Query("INSERT INTO {assigned}
                                       (task_id, user_id)
                                VALUES (?,?)",
                                       array($task_id, $user->id));

                if ($db->affectedRows()) {
                    $fs->logEvent($task_id, 29, $user->id, implode(' ', $task['assigned_to']));
                    $notify->Create(NOTIFY_ADDED_ASSIGNEES, $task_id);
                }
                
                if ($task['item_status'] == STATUS_UNCONFIRMED || $task['item_status'] == STATUS_NEW) {
                    $db->Query('UPDATE {tasks} SET item_status = 3 WHERE task_id = ?', array($task_id));
                    $fs->logEvent($task_id, 0, 3, 1, 'item_status');
                }
            }
        }
    }
    
    function add_comment($task, $comment_text, $time = null)
    {
        global $db, $user, $fs, $notify, $proj;
        
        if (!($user->perms['add_comments'] && (!$task['is_closed'] || $proj->prefs['comment_closed']))) {
            return false;
        }
        
        if (!$comment_text) {
            return false;
        }
        
        $time = ( (is_null($time)) ? time() : $time );

        $db->Query('INSERT INTO  {comments}
                                 (task_id, date_added, last_edited_time, user_id, comment_text)
                         VALUES  ( ?, ?, ?, ?, ? )',
                    array($task['task_id'], $time, $time, $user->id, $comment_text));

        $result = $db->Query('SELECT  comment_id
                                FROM  {comments}
                               WHERE  task_id = ?
                            ORDER BY  comment_id DESC',
                            array($task['task_id']), 1);
        $cid = $db->FetchOne($result);

        $fs->logEvent($task['task_id'], 4, $cid);

        if ($this->UploadFiles($user, $task['task_id'], $cid)) {
            $notify->Create(NOTIFY_COMMENT_ADDED, $task['task_id'], 'files');
        } else {
            $notify->Create(NOTIFY_COMMENT_ADDED, $task['task_id']);
        }

        return true;
    }

    /*
       This function handles file uploads.  Flyspray doesn't allow
       anonymous uploads of files, so the $user is necessary.
       $taskid is the task that the files will be attached to
       $commentid is only valid if the files are to be attached to a comment
     */
    function UploadFiles(&$user, $taskid, $commentid = 0, $source = 'userfile')
    {
        global $db, $fs, $notify, $conf;

        mt_srand($fs->make_seed());

        // Retrieve some important information
        $task = $fs->GetTaskDetails($taskid);
        $project = new Project($task['attached_to_project']);
        $user->get_perms($project);

        if (!$user->perms['create_attachments']) {
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

            $fname = $taskid.'_'.mt_rand();
            while (file_exists($path = 'attachments/'.$fname)) {
                $fname = $taskid.'_'.mt_rand();
            }

            $tmp_name = $_FILES[$source]['tmp_name'][$key];

            // Then move the uploaded file and remove exe permissions
            @move_uploaded_file($tmp_name, $path);
            @chmod($path, 0644);

            if (!file_exists($path)) {
                // there was an error ...
                // file was not uploaded correctly
                continue;
            }

            $res = true;
            
            // Use a different MIME type
            $extension = end(explode('.', $_FILES[$source]['name'][$key]));
            if (isset($conf['attachments'][$extension])) {
                $_FILES[$source]['type'][$key] = $conf['attachments'][$extension];
            }

            $db->Query("INSERT INTO  {attachments}
                                     ( task_id, comment_id, file_name,
                                       file_type, file_size, orig_name,
                                       added_by, date_added )
                             VALUES  (?, ?, ?, ?, ?, ?, ?, ?)",
                    array($taskid, $commentid, $fname,
                        $_FILES[$source]['type'][$key],
                        $_FILES[$source]['size'][$key],
                        $_FILES[$source]['name'][$key],
                        $user->id, time()));

            // Fetch the attachment id for the history log
            $result = $db->Query('SELECT  attachment_id
                                    FROM  {attachments}
                                   WHERE  task_id = ?
                                ORDER BY  attachment_id DESC',
                    array($taskid), 1);
            $fs->logEvent($taskid, 7, $db->fetchOne($result));
        }

        return $res;
    }

    /*
       This function handles file deletions.
     */
    function DeleteFiles(&$user, $task_id)
    {
        global $db, $fs;
        
        if(!$user->perms['delete_attachments'] || !count(Post::val('delete_att'))) {
            return false;
        }
        
        $attachments = array_map('intval', Post::val('delete_att'));
        $where = array();
        
        foreach ($attachments as $attachment) {
            $where[] = 'attachment_id = ' . $attachment;
        }
        
        $where = '(' . implode(' OR ', $where) . ') AND task_id = ' . intval($task_id);
        
        $db->Query("DELETE FROM {attachments} WHERE $where");
        $result = $db->Query("SELECT * FROM {attachments} WHERE $where");
        
        while($row = $db->FetchRow($result)) {
            @unlink(BASEDIR . '/attachments/' . $row['file_name']);
            $fs->logEvent($row['task_id'], 8, $row['orig_name']);
        }
    }
    
    // returns false if user name is taken, else true
    function create_user($user_name, $password, $real_name, $jabber_id, $email, $notify_type, $group_in)
    {
        global $fs, $db;
        
        // Limit lengths
        $user_name = substr(trim($user_name), 0, 32);
        $real_name = substr(trim($real_name), 0, 100);
        // Remove doubled up spaces and control chars
        if (version_compare(PHP_VERSION, '4.3.4') == 1) {
            $user_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $user_name);
            $real_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $real_name);
        }
        // Strip special chars
        $user_name = utf8_keepalphanum($user_name);

        // Check to see if the username is available
        $sql = $db->Query('SELECT COUNT(*) FROM {users} WHERE user_name = ?', array($user_name));
        
        if ($db->fetchOne($sql)) {
            return false;
        }
                       
        $password = $fs->cryptPassword($password);
        $db->Query("INSERT INTO  {users}
                             ( user_name, user_pass, real_name, jabber_id,
                               email_address, notify_type, account_enabled,
                               tasks_perpage, register_date)
                     VALUES  ( ?, ?, ?, ?, ?, ?, 1, 25, ?)",
            array($user_name, $password, $real_name, $jabber_id, $email, $notify_type, time()));

        // Get this user's id for the record
        $sql = $db->Query('SELECT user_id FROM {users} WHERE user_name = ?', array($user_name));
        $uid = $db->fetchOne($sql);

        // Now, create a new record in the users_in_groups table
        $db->Query('INSERT INTO  {users_in_groups} (user_id, group_id)
                         VALUES  ( ?, ?)', array($uid, $group_in));
        
        $fs->logEvent(0, 30, serialize($fs->getUserDetails($uid)));
        
        return true;
    }
    
    function delete_user($uid)
    {
        global $fs, $db;
        
        $user = serialize($fs->getUserDetails($uid));
        
        $sql = $db->Query('DELETE a, b
                             FROM {users} a, {users_in_groups} b
                            WHERE a.user_id = b.user_id AND b.user_id = ?',
                            array($uid));
        // Other necessary deletions (merge all into one not possible?)
        $db->Query('DELETE FROM {searches}      WHERE user_id = ?', array($uid));
        $db->Query('DELETE FROM {notifications} WHERE user_id = ?', array($uid));
        $db->Query('DELETE FROM {assigned}      WHERE user_id = ?', array($uid));
        
        $fs->logEvent(0, 31, $user);
        
        return (bool) $db->affectedRows($sql);
    }
    
    function delete_project($pid, $move_to = 0)
    {
        global $fs, $db;
        
        if (!$move_to) {
            $db->Query('DELETE FROM {list_category}     WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {list_os}           WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {list_resolution}   WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {list_status}       WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {list_tasktype}     WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {list_version}      WHERE project_id = ?', array($pid));
            
            $db->Query('DELETE FROM {admin_requests}    WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {cache}             WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {groups}            WHERE belongs_to_project = ?', array($pid));
            $db->Query('DELETE FROM {projects}          WHERE project_id = ?', array($pid));
            $db->Query('DELETE FROM {tasks}             WHERE attached_to_project = ?', array($pid));
            // if we want to increase complexity of this process, we can remove all entries which
            // belong to tasks or groups of this project too
        } else { // else move them to the new project
            $db->Query('UPDATE {list_category} SET project_id = ?      WHERE project_id = ?', array($move_to, $pid));
            $db->Query('UPDATE {list_os} SET project_id = ?            WHERE project_id = ?', array($move_to, $pid));
            $db->Query('UPDATE {list_resolution} SET project_id = ?    WHERE project_id = ?', array($move_to, $pid));
            $db->Query('UPDATE {list_status} SET project_id = ?        WHERE project_id = ?', array($move_to, $pid));
            $db->Query('UPDATE {list_tasktype} SET project_id = ?      WHERE project_id = ?', array($move_to, $pid));
            $db->Query('UPDATE {list_version} SET project_id = ?       WHERE project_id = ?', array($move_to, $pid));
            
            $db->Query('UPDATE {admin_requests} SET  project_id = ?    WHERE project_id = ?', array($move_to, $pid));
            $db->Query('UPDATE {cache} SET project = ?                 WHERE project_id = ?', array($move_to, $pid));
            $db->Query('UPDATE {groups} SET belongs_to_project = ?     WHERE belongs_to_project = ?', array($move_to, $pid));
            $db->Query('UPDATE {tasks} SET attached_to_project = ?     WHERE attached_to_project = ?', array($move_to, $pid));
            $db->Query('DELETE FROM {projects}                         WHERE project_id = ?', array($pid));
        }
        
    }

   /* This function creates a new task.  Due to the nature of lists
      being specified in the database, we can't really accept default
      values, right?
   */
   function CreateTask($args)
   {
        global $db, $fs, $user;
        $notify = new Notifications();
        
        if (count($args) < 3) {
            return null;
        }

        if (!(($item_summary = $args['item_summary']) && ($detailed_desc = $args['detailed_desc']))) {
            return null;
        }
        
        // Some fields can have default values set
        if ($user->perms['modify_all_tasks'] != '1')
        {
            $args['closedby_version'] = 0;
            $args['task_priority'] = 2;
            $args['due_date'] = 0;
            $args['item_status'] = STATUS_UNCONFIRMED;
        } 

        $param_names = array('task_type', 'item_status',
                'product_category', 'product_version', 'closedby_version',
                'operating_system', 'task_severity', 'task_priority');

        $sql_values = array(time(), time(), $args['project_id'], $item_summary,
                $detailed_desc, intval($user->id), '0');

        $sql_params = array();
        foreach ($param_names as $param_name) {
            if (isset($args[$param_name])) {
                $sql_params[] = $param_name;
                $sql_values[] = $args[$param_name];
            }
        }

        // Process the due_date
        if ( ($due_date = $args['due_date']) || ($due_date = 0) ) {
            $due_date = strtotime("$due_date +23 hours 59 minutes 59 seconds");
        }

        $sql_params[] = 'due_date';
        $sql_values[] = $due_date;
        
        $sql_params[] = 'closure_comment';
        $sql_values[] = '';
        
        // Token for anonymous users
        if ($user->isAnon()) {
            $token = md5(time() . $_SERVER['REQUEST_URI'] . mt_rand() . microtime());
            $sql_params[] = 'task_token';
            $sql_values[] = $token;
            
            $sql_params[] = 'anon_email';
            $sql_values[] = $args['anon_email'];
        }
        
        $sql_params = join(', ', $sql_params);
        $sql_placeholder = join(', ', array_fill(1, count($sql_values), '?'));
        
        $result = $db->Query('SELECT  max(task_id)+1
                                FROM  {tasks}');
        $task_id = $db->FetchOne($result) or 1;
                            
        $result = $db->Query("INSERT INTO  {tasks}
                                 ( task_id, date_opened, last_edited_time,
                                   attached_to_project, item_summary,
                                   detailed_desc, opened_by,
                                   percent_complete, $sql_params )
                         VALUES  ($task_id, $sql_placeholder)", $sql_values);

        // Log the assignments and send notifications to the assignees
        if (trim($args['assigned_to']))
        {
            // Convert assigned_to and store them in the 'assigned' table
            foreach (Flyspray::int_explode(' ', trim($args['assigned_to'])) as $key => $val)
            {
                $db->Query('INSERT INTO {assigned}
                                        (task_id, user_id)
                                 VALUES (?,?)',
                            array($task_id, $val));
            }
        }

        // Log to task history
        $fs->logEvent($task_id, 14, trim($args['assigned_to']));

        // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
        $notify->Create(NOTIFY_NEW_ASSIGNEE, $task_id, null,
                        $notify->SpecificAddresses(Flyspray::int_explode(' ', $args['assigned_to'])));
                    
        // Log that the task was opened
        $fs->logEvent($task_id, 1);

        $this->UploadFiles($user, $task_id);

        $result = $db->Query('SELECT  *
                                FROM  {list_category}
                               WHERE  category_id = ?',
                               array($args['product_category']));
        $cat_details = $db->FetchArray($result);

        // We need to figure out who is the category owner for this task
        if (!empty($cat_details['category_owner'])) {
            $owner = $cat_details['category_owner'];
        }
        elseif (!empty($cat_details['parent_id'])) {
            $result = $db->Query('SELECT  category_owner
                                    FROM  {list_category}
                                   WHERE  category_id = ?', array($cat_details['parent_id']));
            $parent_cat_details = $db->FetchArray($result);

            // If there's a parent category owner, send to them
            if (!empty($parent_cat_details['category_owner'])) {
                $owner = $parent_cat_details['category_owner'];
            }
        }

        if (empty($owner)) {
            $owner = $proj->prefs['default_cat_owner'];
        }

        if ($owner) {
            if ($proj->prefs['auto_assign'] && $args['item_status'] == 1) {
                $this->AddToAssignees($owner, $task_id);
            }
            $this->AddToNotifyList($owner, array($task_id), true);
        }

        // Create the Notification
        $notify->Create(NOTIFY_TASK_OPENED, $task_id);

        // If the reporter wanted to be added to the notification list
        if ($args['notifyme'] == '1' && $user->id != $owner) {
            $this->AddToNotifyList($user->id, $task_id, true);
        }
        
        if ($user->isAnon()) {
            $notify->Create(NOTIFY_ANON_TASK, $task_id, null, $args['anon_email']);
        }
        
        return $task_id;
   }

    /************************************************************************************************
     * below this line, functions are old wrt new flyspray internals.  they will
     * need to be refactored
     */
    
   /*
      This function takes an array of arguments and returns a list of 
      task ids that match.
    
    Originally all of this was in generateTaskList but it got refactored
    to support the xmlrpc interface
    */
   function getTaskIdList($args) 
   {
      if (!is_array($args)) {
         return 'We were not given an array of arguments to process.';
      }

      global $db, $fs;

      /*
      Since all variables will be passed to this function by Ander's
      XHMLHttpRequest implementation, we know that they will all be set,
      and all be valid.  Therefore we don't need to check that the variables
      are correct and safe, right?
      */

      $userid     = $args[0];    // The user id of the person requesting the tasklist
      $projectid  = $args[1];    // The project the user wants tasks from. '0' equals all projects
      $tasks_req  = $args[2];    // 'all', 'assigned', 'reported' or 'watched'
      $string     = $args[3];    // The search string
      $type       = $args[4];    // Task Type, from the editable list
      $sev        = $args[5];    // Severity, from the editable list
      $dev        = $args[6];    // User id of the person assigned the tasks
      $cat        = $args[7];    // Category, from the editable list
      $status     = $args[8];    // Status, from the translatable list
      $due        = $args[9];    // Version the tasks are due in
      $date       = $args[10];   // Date the tasks are due by
      $limit      = $args[11];   // The amount of tasks requested.  '0' = all

      // We only accept numeric values for the following args
      if (  !is_numeric($userid)
            OR !is_numeric($projectid)
            OR !is_numeric($type)
            OR !is_numeric($sev)
            OR !is_numeric($dev)
            OR !is_numeric($cat)
            OR !is_numeric($due)
            OR !is_numeric($limit)
         )
         return 'At least one required argument was not numerical.';

      /*
      I trust that Ander's funky javascript can handle sorting and paginating
      the tasks returned by this function, therefore we don't really need
      any of the following variables that we used to use on the previous
      task list page, right?

      $args[12] = $perpage;      // How many results to display
      $args[13] = $pagenum;      // Which page of the search results we're on
      $args[14] = $order;        // Which column to order by
      $args[15] = $sort;         // [asc|desc]ending order for the above column ordering
      $args[16] = $order2;        // Secondary column to order by
      $args[17] = $sort2;         // [asc|desc]ending order for the above column ordering
      */

      $criteria = array('task_type'          => $type,
                        'task_severity'      => $sev,
                        'assigned_to'        => $dev,
                        'product_category'   => $cat,
                        'closedby_version'   => $due,
                       );

      $project = new Project($projectid);
      $user = new User($userid);
      $user->get_perms($project);

      // Check if the user can view tasks from this project
      if ($user->perms['view_tasks'] == '0' && $project->prefs['others_view'] == '0') {
        return 'You don\'t have permission to view tasks from that project.';
      }

      $where = array();
      $params = array('0');

      // Check the requested status
      if (empty($status))
      {
         $where[] = "t.is_closed <> '1'";

      } elseif ($status == 'closed')
      {
         $where[] = "t.is_closed = '1'";

      } else
      {
         $where[] = "t.item_status = ? AND t.is_closed <> '1'";
         $params[] = $status;
      }


      // Select which project we want. If $projectid is zero, we want everything
      if (!empty($projectid))
      {
         $where[] = "t.attached_to_project = ?";
         $params[] = $projectid;
      }

      // Restrict query results based upon (lack of) PM permissions
      if (!$user->isAnon() && $user->perms['manage_project'] != '1')
      {
         $where[] = "(t.mark_private = '0')";
         $params[] = $userid;

      } elseif (empty($userid))
      {
         $where[] = "t.mark_private = '0'";
      }

      // Change query results based upon type of tasks requested
      if ($tasks_req == 'reported')
      {
         $where[] = "t.opened_by = ?";
         $params[] = $userid;

      } elseif ($tasks_req == 'watched')
      {
         $where[] = "fsn.user_id = ?";
         $params[] = $userid;
      }

      // Calculate due-by-date
      if (!empty($date))
      {
         $where[] = "(t.due_date < ? AND t.due_date <> '0' AND t.due_date <> '')";
         $params[] = strtotime("$date +24 hours");
      }

      // The search string
      if (!empty($string))
      {
         $string = ereg_replace('\(', ' ', $string);
         $string = ereg_replace('\)', ' ', $string);
         $string = trim($string);

         $where[] = "(t.item_summary LIKE ? OR t.detailed_desc LIKE ? OR t.task_id LIKE ?)";
         $params[] = "%$string%";
         $params[] = "%$string%";
         $params[] = "%$string%";
      }

      // Add the other search narrowing criteria
      foreach ($criteria AS $key => $val)
      {
         if (!empty($val))
         {
            $where[] = "t.$key = ?";
            $params[] = $val;
         }
      }

      // Expand the $params
      $sql_where = implode(' AND ', $where);

      // Alrighty.  We should be ok to build the query now!
      $search = $db->Query("SELECT DISTINCT t.task_id
                            FROM {tasks} t
                            LEFT JOIN {notifications} fsn ON t.task_id = fsn.task_id
                            WHERE t.task_id > ?
                            AND $sql_where
                            ORDER BY t.task_severity DESC, t.task_id ASC
                            ", $params, $limit
                          );

      $tasklist = array();

      while ($row = $db->FetchArray($search))
         $tasklist[] = $row['task_id'];

      return $tasklist;

   // End of GenerateTaskList() function
   }

}
?>
