<?php

  /*************************\
  | database modifications  |
  | ~~~~~~~~~~~~~~~~~~~~~~  |
  \*************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

// Include the notifications class
require_once BASEDIR . '/includes/notify.inc.php';
$notify = new Notifications;

if ($lt = Post::val('list_type')) {
    $list_table_name  = '{list_'.addslashes($lt).'}';
    $list_column_name = addslashes($lt)."_name";
    $list_id = addslashes($lt)."_id";
}

function Post_to0($key) { return Post::val($key, 0); }
if (Req::has('task_id')){
    $old_details = $fs->GetTaskDetails(Req::val('task_id'));
}
// Adding a new task  {{{
if (Post::val('action') == 'newtask' && $user->can_open_task($proj)) {
    /*
     * TODO  : merge code with Backend::newTask
     * FIXME : try to reset the form to the previous submited data
     */

    if (!Post::val('item_summary') || !Post::val('detailed_desc')) {
        $_SESSION['ERROR'] = L('summaryanddetails');
        $fs->redirect(CreateURL('newtask', $proj->id));
    }

    $item_summary  = Post::val('item_summary');
    $detailed_desc = Post::val('detailed_desc');

    $param_names = array('task_type', 'item_status',
            'product_category', 'product_version', 'closedby_version',
            'operating_system', 'task_severity', 'task_priority');

    $sql_values = array(time(), time(), Post::val('project_id'), $item_summary,
            $detailed_desc, intval($user->id), '0');

    $sql_params = array();
    foreach ($param_names as $param_name) {
        if (Post::has($param_name)) {
            $sql_params[] = $param_name;
            $sql_values[] = Post::val($param_name);
        }
    }

    // Process the due_date
    if ($due_date = Post::val('due_date', 0)) {
        $due_date = strtotime("$due_date +23 hours 59 minutes 59 seconds");
    }

    $sql_params[] = 'due_date';
    $sql_values[] = $due_date;
    
    $sql_params = join(', ', $sql_params);
    $sql_placeholder = join(', ', array_fill(1, count($sql_values), '?'));

    $result = $db->Query('SELECT  max(task_id)+1
                            FROM  {tasks}');
    $task_id = $db->FetchOne($result);
                        
    $result = $db->Query("INSERT INTO  {tasks}
                             ( task_id, date_opened, last_edited_time,
                               attached_to_project, item_summary,
                               detailed_desc, opened_by,
                               percent_complete, $sql_params )
                     VALUES  ($task_id, $sql_placeholder)", $sql_values);

    // Log the assignments and send notifications to the assignees
    if (trim(Post::val('assigned_to')))
    {
        // Convert assigned_to and store them in the 'assigned' table
        foreach ($fs->int_explode(' ', trim(Post::val('assigned_to'))) as $key => $val)
        {
            if (!empty($val))
            {           
                $db->Query('INSERT INTO {assigned}
                                        (task_id, user_id)
                                 VALUES (?,?)',
                                        array($task_id, $val));
            }
        }

        // Log to task history
        $fs->logEvent($task_id, 14, trim(Post::val('assigned_to')));

        // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
        $to   = $notify->SpecificAddresses($fs->int_explode(' ', Post::val('assigned_to')) );
        $msg  = $notify->GenerateMsg('14', $task_id);
        $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
        $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);
    }
                
    // Log that the task was opened
    $fs->logEvent($task_id, 1);

    $be->UploadFiles($user, $task_id);

    $result = $db->Query('SELECT  *
                            FROM  {list_category}
                           WHERE  category_id = ?', array(Post::val('product_category')));
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
        $be->AddToNotifyList($owner, array($task_id));
    }

    // Create the Notification
    $notify->Create('1', $task_id);

    // If the reporter wanted to be added to the notification list
    if (Post::val('notifyme') == '1' && $user->id != $owner) {
        $be->AddToNotifyList($user->id, $task_id);
    }

    // Status and redirect
    $_SESSION['SUCCESS'] = L('newtaskadded');
    $fs->redirect(CreateURL('details', $task_id));

} // }}}
// Modifying an existing task {{{
elseif (Post::val('action') == 'update' && $user->can_edit_task($old_details)) {

    if (!Post::val('item_summary') || !Post::val('detailed_desc')) {
        $_SESSION['ERROR'] = L('summaryanddetails');
        $fs->redirect(CreateURL('edittask', $old_details['task_id']));
    }

    if (Post::val('edit_start_time') < $old_details['last_edited_time']) {
        // if this task has already been modified before we clicked "save"...
        // we need to confirm that the we really wants to save our changes
        $page->pushTpl('details.edit.conflict.tpl');
    }
    else {
        if ($due_date = Post::val('due_date', 0)) {
            $due_date = strtotime(Post::val('due_date') . '+23 hours 59 minutes 59 seconds');
        }

        $time = time();

        $db->Query('UPDATE  {tasks}
                       SET  attached_to_project = ?, task_type = ?, item_summary = ?,
                            detailed_desc = ?, item_status = ?,
                            product_category = ?, closedby_version = ?, operating_system = ?,
                            task_severity = ?, task_priority = ?, last_edited_by = ?,
                            last_edited_time = ?, due_date = ?, percent_complete = ?, product_version = ?
                     WHERE  task_id = ?',
                array(Post::val('attached_to_project'), Post::val('task_type'),
                    Post::val('item_summary'), Post::val('detailed_desc'),
                    Post::val('item_status'),
                    Post::val('product_category'), Post::val('closedby_version', 0),
                    Post::val('operating_system'), Post::val('task_severity'),
                    Post::val('task_priority'), intval($user->id), $time, $due_date,
                    Post::val('percent_complete'), Post::val('reportedver'),
                    Post::val('task_id')));
        
        // Update the list of users assigned this task
        if (Post::val('old_assigned') != trim(Post::val('assigned_to')) ) {
            
            // Delete the current assignees for this task
            $db->Query('DELETE FROM {assigned}
                              WHERE task_id = ?',
                                    array(Post::val('task_id')));

            // Convert assigned_to and store them in the 'assigned' table
            foreach ($fs->int_explode(' ', trim(Post::val('assigned_to'))) as $key => $val)
            {
               if (!empty($val))
               {           
                  $db->Query("INSERT INTO {assigned}
                                          (task_id, user_id)
                                   VALUES (?,?)",
                                          array(Post::val('task_id'), $val));
               }
            }
         }

        // Get the details of the task we just updated
        // To generate the changed-task message
        $new_details_full = $fs->GetTaskDetails(Post::val('task_id'));
        // Not very nice...maybe combine compare_tasks() and logEvent() ?
        $result = $db->Query("SELECT * FROM {tasks} WHERE task_id = ?",
                array(Post::val('task_id')));
        $new_details = $db->FetchRow($result);

        foreach ($new_details as $key => $val) {
            if (strstr($key, 'last_edited_') || $key == 'assigned_to'
                    || is_numeric($key))
            {
                continue;
            }
            
            if ($val != $old_details[$key]) {
                // Log the changed fields in the task history
                $fs->logEvent(Post::val('task_id'), 0, $val, $old_details[$key], $key, $time);
            }
        }
        
        $changes = $fs->compare_tasks($old_details, $new_details_full);
        if(count($changes) > 0) {
            $notify->Create('2', Post::val('task_id'), $changes);
        }

        if (Post::val('old_assigned') != trim(Post::val('assigned_to')) ) {
            // Log to task history
            $fs->logEvent(Post::val('task_id'), 14, trim(Post::val('assigned_to')),
                    Post::val('old_assigned'));

            // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
            if (Post::val('assigned_to') != '') {
                $to   = $notify->SpecificAddresses( array_diff($fs->int_explode(' ', Post::val('assigned_to')), $fs->int_explode(' ', Post::val('old_assigned') ) ) );
                $msg  = $notify->GenerateMsg('14', Post::val('task_id'));
                $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
                $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);
            }
        }
        
        $be->add_comment($old_details, Post::val('comment_text'), $time);

        $_SESSION['SUCCESS'] = L('taskupdated');
        $fs->redirect(CreateURL('details', Post::val('task_id')));
    }
} // }}}
// closing a task {{{
elseif (Post::val('action') == 'close' && $user->can_close_task($old_details)) {

    if (!Post::val('resolution_reason')) {
        $_SESSION['ERROR'] = L('noclosereason');
        $fs->redirect(CreateURL('details', Post::val('task_id')));
    }

    $db->Query("UPDATE  {tasks}
                   SET  date_closed = ?, closed_by = ?, closure_comment = ?,
                        is_closed = '1', resolution_reason = ?
                 WHERE  task_id = ?",
            array(time(), $user->id, Post::val('closure_comment', 0),
                Post::val('resolution_reason'), Post::val('task_id')));

    if (Post::val('mark100')) {
        $db->Query("UPDATE {tasks} SET percent_complete = '100' WHERE task_id = ?",
                array(Post::val('task_id')));

        $fs->logEvent(Post::val('task_id'), '0', '100',
                $old_details['percent_complete'], 'percent_complete');
    }

    $notify->Create('3', Post::val('task_id'));
    $fs->logEvent(Post::val('task_id'), 2, Post::val('resolution_reason'),
            Post::val('closure_comment'));

    if ($fs->AdminRequestCheck(1, Post::val('task_id'))) {
        // If there's an admin request related to this, close it
        $db->Query("UPDATE  {admin_requests}
                       SET  resolved_by = ?, time_resolved = ?
                     WHERE  task_id = ? AND request_type = ?",
                array($user->id, time(), Post::val('task_id'), 1));
    }

    $_SESSION['SUCCESS'] = L('taskclosedmsg');
    $fs->redirect(CreateURL('details', Post::val('task_id')));

} // }}}
// re-opening an task {{{
elseif (Get::val('action') == 'reopen' && $user->can_close_task($old_details)) {
    // Get last %
    $old_percent = $db->Query("SELECT old_value, new_value
                                 FROM {history}
                                WHERE field_changed = 'percent_complete'
                                      AND task_id = ? AND old_value != '100'
                             ORDER BY event_date DESC",
                              array(Get::val('task_id')));
    $old_percent = $db->FetchArray($old_percent);
    
    $db->Query("UPDATE  {tasks}
                   SET  resolution_reason = '0', closure_comment = '0', date_closed = 0,
                        last_edited_time = ?, last_edited_by = ?, is_closed = '0', percent_complete = ?
                 WHERE  task_id = ?",
                array(time(), $user->id, $old_percent['old_value'], Get::val('task_id')));
    
    $fs->logEvent(Get::val('task_id'), '0', $old_percent['old_value'], $old_percent['new_value'], 'percent_complete');

    $notify->Create('4', Get::val('task_id'));

    if ($fs->AdminRequestCheck(2, Get::val('task_id')) == '1') {
        // If there's an admin request related to this, close it
        $db->Query("UPDATE  {admin_requests}
                       SET  resolved_by = ?, time_resolved = ?
                     WHERE  task_id = ? AND request_type = ?",
                  array($user->id, date('U'), Get::val('task_id'), 2));
    }

    $fs->logEvent(Get::val('task_id'), 13);

    $_SESSION['SUCCESS'] = L('taskreopenedmsg');
    $fs->redirect(CreateURL('details', Get::val('task_id')));
} // }}}
// adding a comment {{{
elseif (Post::val('action') == 'addcomment') {

    if (!$be->add_comment($old_details, Post::val('comment_text'))) {
        // If they pressed submit without actually typing anything
        $_SESSION['ERROR'] = L('nocommententered');
        $fs->redirect(CreateURL('details', Post::val('task_id')));
    }
        
    if (Post::val('notifyme') == '1') {
        // If the user wanted to watch this task for changes
        $be->AddToNotifyList($user->id, Post::val('task_id'));
    }

    $_SESSION['SUCCESS'] = L('commentaddedmsg');
    $fs->redirect(CreateURL('details', Post::val('task_id')));
} // }}}
// sending a new user a confirmation code {{{
elseif (Post::val('action') == 'sendcode') {

    if (!Post::val('user_name') || !Post::val('real_name')
        || (!Post::val('email_address') && Post::val('notify_type') == '1')
        || (!Post::val('jabber_id') && Post::val('notify_type') == '2')
        || (!Post::val('jabber_id') && !Post::val('email_address') && Post::val('notify_type') == '3')
    ) {
        // If the form wasn't filled out correctly, show an error
        $_SESSION['ERROR'] = L('erroronform');
        $fs->redirect(CreateURL('register'));
    }

    // Limit lengths
    $user_name = substr(trim(Post::val('user_name')), 0, 32);
    $real_name = substr(trim(Post::val('real_name')), 0, 100);
    // Remove doubled up spaces and control chars
    if(version_compare(PHP_VERSION, '4.3.4') == 1) {
        $user_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $user_name);
        $real_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $real_name);
    }
    // Strip special chars
    $user_name = utf8_keepalphanum($user_name);

    if (!$user_name || !$real_name) {
        $_SESSION['ERROR'] = L('registererror');
        $fs->redirect(CreateURL('register'));
    }

    // Delete registration codes older than 24 hours
    $yesterday = date('U') - '86400';
    $db->Query("DELETE FROM {registrations} WHERE reg_time < ?", array($yesterday));

    $sql = $db->Query('SELECT COUNT(*) FROM {users} u, {registrations} r
                       WHERE  u.user_name = ? OR r.user_name = ?',
                       array($user_name, $user_name));
    if ($db->fetchOne($sql)) {
        $_SESSION['ERROR'] = L('usernametaken');
        $fs->redirect(CreateURL('register'));
    }

    $params = array();
    $sqlextra = array();
    if (Post::val('jabber_id')) {
       array_push($sqlextra, "jabber_id = ?");
       array_push($params, Post::val('jabber_id'));
    }
    if (Post::val('email_address')) {
       array_push($sqlextra, "email_address = ?");
       array_push($params, Post::val('email_address'));
    }
    $sql = $db->Query("SELECT COUNT(*) FROM {users} WHERE " . implode(' OR ', $sqlextra),$params);
    if ($db->fetchOne($sql)) {
        $_SESSION['ERROR'] = L('emailtaken');
        $fs->redirect(CreateURL('register'));
    }
    
    // Generate a random bunch of numbers for the confirmation code
    mt_srand($fs->make_seed());
    $randval = mt_rand();

    // Convert those numbers to a seemingly random string using crypt
    $confirm_code = crypt($randval, $conf['general']['cookiesalt']);

    // Generate a looonnnnggg random string to send as an URL to complete this registration
    $magic_url = md5(microtime());

    // Insert everything into the database
    $db->Query("INSERT INTO  {registrations}
                             ( reg_time, confirm_code, user_name, real_name,
                               email_address, jabber_id, notify_type,
                               magic_url )
                     VALUES  (?,?,?,?,?,?,?,?)",
                array(time(), $confirm_code, $user_name, $real_name,
                    Post::val('email_address'), Post::val('jabber_id'),
                    Post::val('notify_type'), $magic_url));

    $subject = L('noticefrom') . ' ' . $proj->prefs['project_title'];

    $message = L('noticefrom')." {$proj->prefs['project_title']}\n\n"
             . L('addressused')."\n\n"
             . "{$baseurl}index.php?do=register&magic=$magic_url\n\n"
               // In case that spaces in the username have been removed
             . L('username').": ".$user_name."\n"
             . L('confirmcodeis')." {$confirm_code}";

    // Check how they want to receive their code
    switch (Post::val('notify_type')) {
        case '1':
            $notify->SendEmail(Post::val('email_address'), $subject, $message);
            break;

        case '2':
            $notify->StoreJabber(array(Post::val('jabber_id')), $subject,
                    htmlspecialchars($message),ENT_COMPAT,'utf-8');
            break;

        default: ;
    }

    $_SESSION['SUCCESS'] = L('codesent');
    $fs->redirect('./');
} // }}}
// new user self-registration with a confirmation code {{{
elseif (Post::val('action') == "registeruser" && $fs->prefs['anon_reg']) {

    if (!Post::val('user_pass') || !Post::val('confirmation_code')) {
        $_SESSION['ERROR'] = L('formnotcomplete');
        $fs->redirect(CreateURL('register'));
    }

    if (Post::val('user_pass') != Post::val('user_pass2')) {
        $_SESSION['ERROR'] = L('nomatchpass');
        $fs->redirect(CreateURL('register'));
    }

    // Check that the user entered the right confirmation code
    $sql = $db->Query("SELECT * FROM {registrations} WHERE magic_url = ?",
            array(Post::val('magic_url')));
    $reg_details = $db->FetchArray($sql);

    if ($reg_details['confirm_code'] != Post::val('confirmation_code')) {
        $_SESSION['ERROR'] = L('confirmwrong');
        $fs->redirect(CreateURL('register'));
    }

    // Check that no user still exist with this username
    $sql = $db->Query("SELECT count(*) FROM {users} WHERE user_name = ?",
            array($reg_details['user_name']));
    $check = $db->FetchArray($sql);
    
    if ($check['count'] >= '1'){
        $_SESSION['ERROR'] = L('usernametaken');
        $fs->redirect(CreateURL('register'));
    }

    // Encrypt their password
    $pass_hash = $fs->cryptPassword(Post::val('user_pass'));

    // Add the user to the database
    $db->Query("INSERT INTO  {users}
                             ( user_name, user_pass, real_name, jabber_id,
                               email_address, notify_type, account_enabled,
                               tasks_perpage )
                     VALUES  (?, ?, ?, ?, ?, ?, 1, 25)",
            array($reg_details['user_name'], $pass_hash,
                $reg_details['real_name'], $reg_details['jabber_id'],
                $reg_details['email_address'], $reg_details['notify_type']));

    // Get this user's id for the record
    $sql = $db->Query("SELECT user_id FROM {users} WHERE user_name = ?",
            array($reg_details['user_name']));
    $uid = $db->fetchOne($sql);

    // Now, create a new record in the users_in_groups table
    $db->Query("INSERT INTO  {users_in_groups} (user_id, group_id)
                     VALUES  (?, ?)", array($uid, $fs->prefs['anon_group']));

    $_SESSION['SUCCESS'] = L('accountcreated');
    $page->pushTpl('register.ok.tpl');

} // }}}
// user self-registration without confirmation code (Or, by an admin) {{{
elseif (Post::val('action') == "newuser" &&
        ($user->perms['is_admin'] || $user->can_self_register()))
{

    if ( !Post::val('user_name') || !Post::val('user_pass') || !Post::val('real_name'))
    {
        $_SESSION['ERROR'] = L('formnotcomplete');
        $fs->redirect(CreateURL('newuser'));
    }

    if (Post::val('user_pass') != Post::val('user_pass2')) {
        $_SESSION['ERROR'] = L('nomatchpass');
        $fs->redirect(CreateURL('newuser'));
    }
    
    // Limit lengths
    $user_name = substr(trim(Post::val('user_name')), 0, 32);
    $real_name = substr(trim(Post::val('real_name')), 0, 100);
    // Remove doubled up spaces and control chars
    if(version_compare(PHP_VERSION, '4.3.4') == 1) {
        $user_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $user_name);
        $real_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $real_name);
    }
    // Strip special chars
    $user_name = utf8_keepalphanum($user_name);

    // Check to see if the username is available
    $sql = $db->Query('SELECT COUNT(*) FROM {users} u, {registrations} r
                       WHERE  u.user_name = ? OR r.user_name = ?',
                       array($user_name, $user_name));
    if ($db->fetchOne($sql)) {
        $_SESSION['ERROR'] = L('usernametaken');
        $fs->redirect(CreateURL('newuser'));
    }

    $pass_hash = $fs->cryptPassword(Post::val('user_pass'));

    if ($user->perms['is_admin']) {
        $group_in = Post::val('group_in');
    } else {
        $group_in = $fs->prefs['anon_group'];
    }

    $db->Query("INSERT INTO  {users}
                             ( user_name, user_pass, real_name, jabber_id,
                               email_address, notify_type, account_enabled,
                               tasks_perpage)
                     VALUES  ( ?, ?, ?, ?, ?, ?, 1, 25)",
            array($user_name, $pass_hash, $real_name,
                Post::val('jabber_id'), Post::val('email_address'),
                Post::val('notify_type')));

    // Get this user's id for the record
    $sql = $db->Query("SELECT user_id FROM {users} WHERE user_name = ?",
            array($user_name));
    $uid = $db->fetchOne($sql);

    // Now, create a new record in the users_in_groups table
    $db->Query("INSERT INTO  {users_in_groups} (user_id, group_id)
                     VALUES  ( ?, ?)", array($uid, $group_in));

    if ($user->perms['is_admin']) {
        $_SESSION['SUCCESS'] = L('newusercreated');
        $fs->redirect(CreateURL('admin', 'groups'));
    } else {
        $_SESSION['SUCCESS'] = L('accountcreated');
        $page->pushTpl('register.ok.tpl');
    }

} // }}}
// adding a new group {{{
elseif (Post::val('action') == "newgroup"
          && ((Post::val('belongs_to_project') && $user->perms['manage_project'])
              || $user->perms['is_admin'])
) {

    if (!Post::val('group_name') || !Post::val('group_desc')) {
        $_SESSION['ERROR'] = L('formnotcomplete');
    } else {
        // Check to see if the group name is available
        $sql = $db->Query("SELECT  COUNT(*)
                             FROM  {groups}
                            WHERE  group_name = ? AND belongs_to_project = ?",
                array(Post::val('group_name'), Post::val('project')));

        if ($db->fetchOne($sql)) {
            $_SESSION['ERROR'] = L('groupnametaken');
        } else {
            $cols = array('project', 'group_name', 'group_desc', 'manage_project',
                    'view_tasks', 'open_new_tasks', 'modify_own_tasks',
                    'modify_all_tasks', 'view_comments', 'add_comments',
                    'edit_comments', 'delete_comments', 'create_attachments',
                    'delete_attachments', 'view_history', 'close_own_tasks',
                    'close_other_tasks', 'assign_to_self', 'view_attachments',
                    'assign_others_to_self', 'view_reports', 'group_open');
            // XXX kludge : project in POST is belongs_to_project in the DB
            $db->Query("INSERT INTO  {groups} ( belongs_to_".join(',', $cols).")
                             VALUES  (".join(',', array_fill(0, count($cols), '?')).")",
                                 array_map('Post_to0', $cols));

            $_SESSION['SUCCESS'] = L('newgroupadded');
        }
    }

    if (Post::val('project')) {
        $fs->redirect(CreateURL('pm', 'groups', Post::val('project')));
    } else {
        $fs->redirect(CreateURL('admin', 'groups'));
    }
} // }}}
// Update the global application preferences {{{
elseif (Post::val('action') == "globaloptions" && $user->perms['is_admin']) {
    $settings = array('jabber_server', 'jabber_port', 'jabber_username',
            'jabber_password', 'anon_group', 'user_notify', 'admin_email',
            'lang_code', 'spam_proof', 'default_project', 'dateformat',
            'dateformat_extended', 'anon_reg', 'global_theme', 'smtp_server',
            'smtp_user', 'smtp_pass', 'funky_urls', 'reminder_daemon','cache_feeds');
    foreach ($settings as $setting) {
        $db->Query("UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?",
                array(Post::val($setting), $setting));
    }

    // Process the list of groups into a format we can store
    $assigned_groups = join(' ', array_keys(Post::val('assigned_groups', array())));
    $db->Query("UPDATE  {prefs} SET pref_value = ?
                 WHERE  pref_name = 'assigned_groups'",
            array($assigned_groups));

    $db->Query("UPDATE  {prefs} SET pref_value = ?
                 WHERE  pref_name = 'visible_columns'",
            array(trim(Post::val('visible_columns'))));

    $_SESSION['SUCCESS'] = L('optionssaved');
    $fs->redirect(CreateURL('admin','prefs'));
} // }}}
// adding a new project {{{
elseif (Post::val('action') == "newproject" && $user->perms['is_admin']) {

    if (!Post::val('project_title')) {
        $_SESSION['ERROR'] = L('emptytitle');
        $fs->redirect(CreateURL('admin', 'newproject'));
    }

    $db->Query("INSERT INTO  {projects}
                             ( project_title, theme_style, intro_message,
                               others_view, anon_open, project_is_active,
                               visible_columns, lang_code)
                     VALUES  (?, ?, ?, ?, ?, 1, ?, ?)",
              array(Post::val('project_title'), Post::val('theme_style'),
                  Post::val('intro_message'), Post::val('others_view', 0),
                  Post::val('anon_open', 0),
                  'id tasktype severity summary status dueversion progress', Post::val('lang_code')));

    $sql = $db->Query("SELECT project_id FROM {projects} ORDER BY project_id DESC", false, 1);
    $pid = $db->fetchOne($sql);

    $cols = array( 'manage_project', 'view_tasks', 'open_new_tasks',
            'modify_own_tasks', 'modify_all_tasks', 'view_comments',
            'add_comments', 'edit_comments', 'delete_comments', 'view_attachments',
            'create_attachments', 'delete_attachments', 'view_history',
            'close_own_tasks', 'close_other_tasks', 'assign_to_self',
            'assign_others_to_self', 'add_to_assignees', 'view_reports', 'group_open');
    $args = array_fill(0, count($cols), '1');
    array_unshift($args, 'Project Managers',
            'Permission to do anything related to this project.',
            intval($pid));

    $db->Query("INSERT INTO  {groups}
                             ( group_name, group_desc, belongs_to_project,
                               ".join(',', $cols).")
                     VALUES  ( ?, ?, ?, ".join(',', array_fill(0, count($cols), '?')).")",
                     $args);

    $db->Query("INSERT INTO  {list_category}
                             ( project_id, category_name, list_position,
                               show_in_list, category_owner )
                     VALUES  ( ?, ?, 1, 1, 0)", array($pid, 'Backend / Core'));

    $db->Query("INSERT INTO  {list_os}
                             ( project_id, os_name, list_position, show_in_list )
                     VALUES  (?, ?, 1, 1)", array($pid, 'All'));

    $db->Query("INSERT INTO  {list_version}
                             ( project_id, version_name, list_position,
                               show_in_list, version_tense )
                     VALUES  (?, ?, 1, 1, 2)", array($pid, '1.0'));

    $_SESSION['SUCCESS'] = L('projectcreated');
    $fs->redirect(CreateURL('pm', 'prefs', $pid));
} // }}}
// updating project preferences {{{
elseif (Post::val('action') == 'updateproject' && $user->perms['manage_project']) {

    if (!Post::val('project_title')) {
        $_SESSION['ERROR'] = L('emptytitle');
        $fs->redirect(CreateURL('pm', 'prefs', $proj->id));
    }

    $cols = array( 'project_title', 'theme_style', 'default_cat_owner', 'lang_code',
            'intro_message', 'project_is_active', 'others_view', 'anon_open',
            'notify_email', 'notify_email_when', 'notify_jabber', 'notify_subject',
            'notify_jabber_when', 'feed_description', 'feed_img_url', 'comment_closed');
    $args = array_map('Post_to0', $cols);
    $args[] = Post::val('project_id', 0);

    $update = $db->Query("UPDATE  {projects}
                             SET  ".join('=?, ', $cols)."=?
                           WHERE  project_id = ?", $args);

    $update = $db->Query("UPDATE {projects} SET visible_columns = ? WHERE project_id = ?",
            array(trim(Post::val('visible_columns')), Post::val('project_id')));


    $_SESSION['SUCCESS'] = L('projectupdated');
    $fs->redirect(CreateURL('pm', 'prefs', $proj->id));

} // }}}
// uploading an attachment {{{
elseif (Post::val('action') == "addattachment" && $user->perms['create_attachments'])
{
    mt_srand($fs->make_seed());
    $randval = mt_rand();
    $file_name = Post::val('task_id')."_$randval";

    if (!$_FILES['userfile']['name']) {
        $_SESSION['ERROR'] = L('selectfileerror');
        $fs->redirect(CreateURL('details', Post::val('task_id')));
    }

    @move_uploaded_file($_FILES['userfile']['tmp_name'], "attachments/$file_name");
    @chmod("attachments/$file_name", 0644);

    if (!file_exists("attachments/$file_name")) {
        $_SESSION['ERROR'] = L('fileerror');
        $fs->redirect(CreateURL('details', Post::val('task_id')));
    }

    $file_desc = Post::val('file_desc');
    $db->Query("INSERT INTO  {attachments}
                             ( task_id, orig_name, file_name, file_desc,
                               file_type, file_size, added_by, date_added )
                     VALUES  ( ?, ?, ?, ?, ?, ?, ?, ?)",
            array(Post::val('task_id'), $_FILES['userfile']['name'], $file_name,
                $file_desc, $_FILES['userfile']['type'],
                $_FILES['userfile']['size'], $user->id, time()));

    $notify->Create('8', Post::val('task_id'));

    $sql = $db->Query("SELECT attachment_id FROM {attachments}
                        WHERE task_id = ? ORDER BY attachment_id DESC",
            array(Post::val('task_id')), 1);
    $aid = $db->fetchOne($sql);
    $fs->logEvent(Post::val('task_id'), 7, $aid);

    $_SESSION['SUCCESS'] = L('fileuploaded');
    $fs->redirect(CreateURL('details', Post::val('task_id')));

} // }}}
// Start of modifying user details {{{
elseif (Post::val('action') == "edituser"
          && ($user->perms['is_admin'] || $user->id == Post::val('user_id')))
{
    if (!Post::val('real_name') || (!Post::val('email_address') && !Post::val('jabber_id'))) {
        $_SESSION['ERROR'] = L('realandnotify');
        $fs->redirect(Post::val('prev_page'));
    }

    if (Post::val('changepass') || Post::val('confirmpass')) {
        if (Post::val('changepass') != Post::val('confirmpass')) {
            $_SESSION['ERROR'] = L('passnomatch');
            $fs->redirect(Post::val('prev_page'));
        }

        $new_hash = $fs->cryptPassword(Post::val('changepass'));
        $db->Query("UPDATE {users} SET user_pass = ? WHERE user_id = ?",
                array($new_hash, Post::val('user_id')));

        // If the user is changing their password, better update their cookie hash
        if ($user->id == Post::val('user_id')) {
            $fs->setcookie('flyspray_passhash',
                    crypt($new_hash, $conf['general']['cookiesalt']), time()+3600*24*30);
        }
    }

    $db->Query("UPDATE  {users}
                   SET  real_name = ?, email_address = ?,
                        jabber_id = ?, notify_type = ?,
                        dateformat = ?, dateformat_extended = ?,
                        tasks_perpage = ?
                 WHERE  user_id = ?",
            array(Post::val('real_name'), Post::val('email_address'),
                Post::val('jabber_id'), Post::val('notify_type', 0),
                Post::val('dateformat'), Post::val('dateformat_extended'),
                Post::val('tasks_perpage'), Post::val('user_id')));

    if ($user->perms['is_admin']) {
        $db->Query("UPDATE {users} SET account_enabled = ?  WHERE user_id = ?",
                array(Post::val('account_enabled'), Post::val('user_id')));

        $db->Query("UPDATE {users_in_groups} SET group_id = ?
                     WHERE record_id = ?",
                array(Post::val('group_in'), Post::val('record_id')));
    }

    $_SESSION['SUCCESS'] = L('userupdated');
    $fs->redirect(Post::val('prev_page'));
} // }}}
// updating a group definition {{{
elseif (Post::val('action') == "editgroup" && $user->perms['manage_project']) {

    if (!Post::val('group_name') && !Post::val('group_desc')) {
        $_SESSION['ERROR'] = L('groupanddesc');
        $fs->redirect(Post::val('prev_page'));
    }

    $cols = array( 'group_name', 'group_desc');
    
    // Allow all groups to update permissions except for global Admin
    if (Post::val('group_id') != '1') {
        $cols = $cols + array('manage_project', 'view_tasks', 'edit_own_comments',
                              'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                              'view_comments', 'add_comments', 'edit_comments', 'delete_comments',
                              'view_attachments', 'create_attachments', 'delete_attachments',
                              'view_history', 'close_own_tasks', 'close_other_tasks',
                              'assign_to_self', 'assign_others_to_self', 'view_reports', 'add_votes',
                              'group_open');
    }

    $args = array_map('Post_to0', $cols);
    $args[] = Post::val('group_id');

    $db->Query("UPDATE  {groups}
                   SET  ".join('=?,', $cols)."=?
                 WHERE  group_id = ?", $args);

    $_SESSION['SUCCESS'] = L('groupupdated');
    $fs->redirect(Post::val('prev_page'));

} // }}}
// updating a list {{{
elseif (Post::val('action') == "update_list" && $user->perms['manage_project']) {

    $listname     = Post::val('list_name');
    $listposition = Post::val('list_position');
    $listshow     = Post::val('show_in_list');
    $listdelete   = Post::val('delete');
    $listid       = Post::val('id');

    $redirectmessage = L('listupdated');

    for($i = 0; $i < count($listname); $i++) {
        if (is_numeric($listposition[$i]) && $listname[$i] != '') {
            $update = $db->Query("UPDATE  $list_table_name
                                     SET  $list_column_name = ?, list_position = ?, show_in_list = ?
                                   WHERE  $list_id = '{$listid[$i]}'",
                    array($listname[$i], $listposition[$i], intval($listshow[$i])));
        } else {
            $redirectmessage = L('listupdated') . " " . L('fieldsmissing');
        }
    }

    if (is_array($listdelete)) {
        $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
        $db->Query("DELETE FROM $list_table_name WHERE $deleteids");
    }

    $_SESSION['SUCCESS'] = $redirectmessage;
    $fs->redirect(Post::val('prev_page'));
} // }}}
// adding a list item {{{
elseif (Post::val('action') == "add_to_list" && $user->perms['manage_project']) {

    if (!Post::val('list_name') || !Post::val('list_position')) {
        $_SESSION['ERROR'] = L('fillallfields');
        $fs->redirect(Post::val('prev_page'));
    }

    if (!is_numeric(Post::val('list_position'))){
        $_SESSION['ERROR'] = L('listPmustN');
        $fs->redirect(Post::val('prev_page'));
    }
    
    $db->Query("INSERT INTO  $list_table_name
                             (project_id, $list_column_name, list_position, show_in_list)
                     VALUES  (?, ?, ?, ?)",
            array(Post::val('project_id', '0'), Post::val('list_name'),
                Post::val('list_position'), '1'));

    $_SESSION['SUCCESS'] = L('listitemadded');
    $fs->redirect(Post::val('prev_page'));

} // }}}
// updating the version list {{{
elseif (Post::val('action') == "update_version_list" && $user->perms['manage_project']) {

    $listname     = Post::val('list_name');
    $listposition = Post::val('list_position');
    $listshow     = Post::val('show_in_list');
    $listtense    = Post::val('version_tense');
    $listdelete   = Post::val('delete');
    $listid       = Post::val('id');

    $redirectmessage = L('listupdated');

    for($i = 0; $i < count($listname); $i++) {
        if (is_numeric($listposition[$i])  && $listname[$i] != '') {

            $update = $db->Query("UPDATE  $list_table_name
                                     SET  $list_column_name = ?, list_position = ?,
                                          show_in_list = ?, version_tense = ?
                                   WHERE  $list_id = '{$listid[$i]}'",
                    array($listname[$i], $listposition[$i],
                        intval($listshow[$i]), intval($listtense[$i])));
        } else {
            $redirectmessage = L('listupdated') . " " . L('fieldsmissing');
        }
    }

    if (is_array($listdelete)) {
        $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
        $db->Query("DELETE FROM $list_table_name WHERE $deleteids");
    }

    $_SESSION['SUCCESS'] = $redirectmessage;
    $fs->redirect(Post::val('prev_page'));
} // }}}
// adding a version list item {{{
elseif (Post::val('action') == "add_to_version_list" && $user->perms['manage_project']) {

   if (!Post::val('list_name') || !Post::val('list_position')) {
       $_SESSION['ERROR'] = L('fillallfields');
       $fs->redirect(Post::val('prev_page'));
   }
   
   if (!is_numeric(Post::val('list_position'))){
        $_SESSION['ERROR'] = L('listPmustN');
        $fs->redirect(Post::val('prev_page'));
   }
   
   $db->Query("INSERT INTO  $list_table_name
                            (project_id, $list_column_name, list_position, show_in_list, version_tense)
                    VALUES  (?, ?, ?, ?, ?)",
             array(Post::val('project_id'), Post::val('list_name'),
                 Post::val('list_position'), '1', Post::val('version_tense')));

   $_SESSION['SUCCESS'] = L('listitemadded');
   $fs->redirect(Post::val('prev_page'));

} // }}}
// updating the category list {{{
elseif (Post::val('action') == "update_category" && $user->perms['manage_project']) {

    $listname     = Post::val('list_name');
    $listposition = Post::val('list_position');
    $listshow     = Post::val('show_in_list');
    $listid       = Post::val('id');
    $listowner    = Post::val('category_owner');
    $listdelete   = Post::val('delete');

    $redirectmessage = L('listupdated');

    for ($i = 0; $i < count($listname); $i++) {
        if (is_numeric($listposition[$i])  && $listname[$i] != '') {
            $update = $db->Query("UPDATE  {list_category}
                                     SET  category_name = ?, list_position = ?,
                                          show_in_list = ?, category_owner = ?
                                   WHERE  category_id = ?",
                              array($listname[$i], $listposition[$i],
                                  intval($listshow[$i]), intval($listowner[$i]), $listid[$i]));
        } else {
            $redirectmessage = L('listupdated') . " " . L('fieldsmissing');
        }
    }

    if (is_array($listdelete)) {
        $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
        $db->Query("DELETE FROM {list_category} WHERE $deleteids");
    }

    $_SESSION['SUCCESS'] = $redirectmessage;
    $fs->redirect(Post::val('prev_page'));
} // }}}
// adding a category list item {{{
elseif (Post::val('action') == "add_category" && $user->perms['manage_project']) {

    if (!Post::val('list_name') || !Post::val('list_position')) {
        $_SESSION['ERROR'] = L('fillallfields');
        $fs->redirect(Post::val('prev_page'));
    }
    
    if (!is_numeric(Post::val('list_position'))){
        $_SESSION['ERROR'] = L('listPmustN');
        $fs->redirect(Post::val('prev_page'));
    }

    $db->Query("INSERT INTO  {list_category}
                             ( project_id, category_name, list_position,
                               show_in_list, category_owner, parent_id )
                     VALUES  (?, ?, ?, 1, ?, ?)",
            array(Post::val('project_id', 0), Post::val('list_name'),
                Post::val('list_position'), Post::val('category_owner', 0) ? Post::val('category_owner', 0) : 0,
                Post::val('parent_id', 0) ? Post::val('parent_id', 0) : 0));

    $_SESSION['SUCCESS'] = L('listitemadded');
    $fs->redirect(Post::val('prev_page'));

} // }}}
// adding a related task entry {{{
elseif (Post::val('action') == 'add_related' && $user->can_edit_task($old_details)) {

    if (!is_numeric(Post::val('related_task'))) {
        $_SESSION['ERROR'] = L('relatedinvalid');
        $fs->redirect(CreateURL('details', Post::val('this_task').'#related'));
    }

    $sql = $db->Query("SELECT  attached_to_project
                         FROM  {tasks}
                        WHERE  task_id = ?",
            array(Post::val('related_task')));
    if (!$db->CountRows($sql)) {
        $_SESSION['ERROR'] = L('relatedinvalid');
        $fs->redirect(CreateURL('details', Post::val('this_task').'#related'));
    }

    $relatedproject = $db->fetchOne($sql);

    if ($proj->id == $relatedproject || Post::has('allprojects')) {
        $sql = $db->Query("SELECT related_id
                             FROM {related}
                            WHERE this_task = ? and related_task = ?",
                          array(Post::val('this_task'), Post::val('related_task')));
       
        if ($db->CountRows($sql)) {
            $_SESSION['ERROR'] = L('relatederror');
            $fs->redirect(CreateURL('details', Post::val('this_task').'#related'));
        }
            
        $db->Query("INSERT INTO {related} (this_task, related_task) VALUES(?,?)",
                array(Post::val('this_task'), Post::val('related_task')));

        $fs->logEvent(Post::val('this_task'), 11, Post::val('related_task'));
        $fs->logEvent(Post::val('related_task'), 15, Post::val('this_task'));

        $notify->Create('9', Post::val('this_task'), Post::val('related_task'));

        $_SESSION['SUCCESS'] = L('relatedaddedmsg');
        $fs->redirect(CreateURL('details', Post::val('this_task').'#related'));
    } else {
        $page->pushTpl('details.edit.related.tpl');
    }

} // }}}
// Removing a related task entry {{{
elseif (Post::val('action') == "remove_related" && $user->can_edit_task($old_details)) {

    $db->Query("DELETE FROM {related} WHERE related_id = ?", array(Post::val('related_id')));

    $fs->logEvent(Post::val('id'), 12, Post::val('related_task'));
    $fs->logEvent(Post::val('related_task'), 16, Post::val('id'));

    $_SESSION['SUCCESS'] = L('relatedremoved');
    $fs->redirect(CreateURL('details', Post::val('id')));

} // }}}
// adding a user to the notification list {{{
elseif (Req::val('action') == "add_notification") {
    if (Req::val('prev_page')) {
        $ids = Req::val('ids');
        settype($ids, 'array');

        if (!empty($ids)) {
            $be->AddToNotifyList($user->id, array_keys($ids));
        }
        $fs->redirect(Req::val('prev_page'));
    } else {
        $be->AddToNotifyList(Req::val('user_id'), Req::val('ids'));
    }

    $_SESSION['SUCCESS'] = L('notifyadded');
    $fs->redirect(CreateURL('details', Req::val('ids')) . '#notify');
} // }}}
// removing a notification entry {{{
elseif (Req::val('action') == "remove_notification") {
    if (Req::val('prev_page')) {
        $ids = Req::val('ids');
        settype($ids, 'array');
        $redirect_url = Req::val('prev_page');

        if (!empty($ids)) {
            $be->RemoveFromNotifyList($user->id, array_keys($ids));
        }
        $fs->redirect(Req::val('prev_page'));
    } else {
        $be->RemoveFromNotifyList(Req::val('user_id'), Req::val('ids'));
    }

    $_SESSION['SUCCESS'] = L('notifyremoved');
    $fs->redirect(CreateURL('details', Req::val('ids')) . '#notify');
} // }}}
// editing a comment {{{
elseif (Post::val('action') == "editcomment" && ($user->perms['edit_comments'] || $user->perms['edit_own_comments'])) {
    $where = '';
    if ($user->perms['edit_own_comments'] && !$user->perms['edit_comments']) {
        $where = ' AND user_id = ' . $user->id;
    }
    $db->Query("UPDATE  {comments}
                   SET  comment_text = ?, last_edited_time = ?
                 WHERE  comment_id = ? $where",
            array(Post::val('comment_text'), time(), Post::val('comment_id')));

    $fs->logEvent(Post::val('task_id'), 5, Post::val('comment_text'),
            Post::val('previous_text'), Post::val('comment_id'));

    $_SESSION['SUCCESS'] = L('editcommentsaved');
    $fs->Redirect(CreateURL('details', Req::val('task_id')));
} // }}}
// deleting a comment {{{
elseif (Get::val('action') == "deletecomment" && $user->perms['delete_comments']) {
    $result = $db->Query("SELECT  comment_text, user_id, date_added
                            FROM  {comments}
                           WHERE  comment_id = ?",
                        array(Get::val('comment_id')));
    $comment = $db->FetchRow($result);

    // Check for files attached to this comment
    $check_attachments = $db->Query("SELECT  *
                                       FROM  {attachments}
                                      WHERE  comment_id = ?",
                                    array(Req::val('comment_id')));

    if($db->CountRows($check_attachments) && !$user->perms['delete_attachments']) {
        $_SESSION['ERROR'] = L('commentattachperms');
        $fs->redirect(CreateURL('details', Req::val('task_id')));
    }

    $db->Query("DELETE FROM {comments} WHERE comment_id = ?",
            array(Req::val('comment_id')));

    $fs->logEvent(Req::val('task_id'), 6, $comment['user_id'],
            $comment['comment_text'], $comment['date_added']);

    while ($attachment = $db->FetchRow($check_attachments)) {
        $db->Query("DELETE from {attachments} WHERE attachment_id = ?",
                array($attachment['attachment_id']));

        @unlink("attachments/" . $attachment['file_name']);

        $fs->logEvent($attachment['task_id'], 8, $attachment['orig_name']);
    }

    $_SESSION['SUCCESS'] = L('commentdeletedmsg');
    $fs->redirect(CreateURL('details', Req::val('task_id')));

} // }}}
// deleting an attachment {{{
elseif (Req::val('action') == 'deleteattachment' && $user->perms['delete_attachments']) {
    // if an attachment needs to be deleted do it right now
    $result = $db->Query("SELECT  * FROM {attachments}
                           WHERE  attachment_id = ?",
            array(Req::val('id')));
    $row = $db->FetchArray($result);

    @unlink("attachments/" . $row['file_name']);
    $db->Query("DELETE FROM {attachments} WHERE attachment_id = ?",
            array(Req::val('id')));

    $fs->logEvent($row['task_id'], 8, $row['orig_name']);

    $_SESSION['SUCCESS'] = L('attachmentdeletedmsg');
    $fs->redirect(CreateURL('details', $row['task_id']));
} // }}}
// adding a reminder {{{
elseif (Post::val('action') == "addreminder" && $user->perms['manage_project']) {

    $how_often  = Post::val('timeamount1') * Post::val('timetype1');
    $start_time = Post::val('timeamount2') * Post::val('timetype2') + date('U');
    
    if (!is_numeric(Post::val('timeamount1')) || !is_numeric(Post::val('timeamount2'))) {
        $_SESSION['ERROR'] = L('formnotnumeric');
        $fs->redirect(CreateURL('details', Req::val('task_id')));
    }
    
    $db->Query("INSERT INTO  {reminders}
                             ( task_id, to_user_id, from_user_id,
                               start_time, how_often, reminder_message )
                     VALUES  (?,?,?,?,?,?)",
            array(Post::val('task_id'), Post::val('to_user_id'), $user->id,
                $start_time, $how_often, Post::val('reminder_message')));

    $fs->logEvent(Post::val('task_id'), 17, Post::val('to_user_id'));

    $_SESSION['SUCCESS'] = L('reminderaddedmsg');
    $fs->redirect(CreateURL('details', Req::val('task_id')).'#remind');
} // }}}
// removing a reminder {{{
elseif (Post::val('action') == "deletereminder" && $user->perms['manage_project']) {

    $sql = $db->Query("SELECT to_user_id FROM {reminders} WHERE reminder_id = ?",
            array(Post::val('reminder_id')));
    $reminder = $db->fetchOne($sql);
    $db->Query("DELETE FROM {reminders} WHERE reminder_id = ?",
            array(Post::val('reminder_id')));

    $fs->logEvent(Post::val('task_id'), 18, $reminder);

    $_SESSION['SUCCESS'] = L('reminderdeletedmsg');
    $fs->redirect(CreateURL('details', Req::val('task_id')).'#remind');
} // }}}
// adding a bunch of users to a group {{{
elseif (Post::val('action') == "addtogroup" && $user->perms['manage_project']) {

    if (!is_array(Post::val('user_list'))) {
        $_SESSION['ERROR'] = L('nouserselected');
        $fs->redirect(Post::val('prev_page'));
    }

    foreach (Post::val('user_list') as $key => $val) {
        $db->Query("INSERT INTO  {users_in_groups} (user_id, group_id)
                         VALUES  (?, ?)",
                array($val, Post::val('add_to_group')));
    }

    $_SESSION['SUCCESS'] = L('groupswitchupdated');
    $fs->redirect(Post::val('prev_page'));

} // }}}
// change a bunch of users' groups {{{
elseif (Post::val('action') == 'movetogroup' && $user->perms['manage_project']) {

    foreach (Post::val('users') as $user_id => $val) {
        if (Post::val('switch_to_group') == '0') {
            $db->Query("DELETE FROM  {users_in_groups}
                              WHERE  user_id = ? AND group_id = ?",
                    array($user_id, Post::val('old_group')));
        } else {
            $db->Query("UPDATE  {users_in_groups}
                           SET  group_id = ?
                         WHERE  user_id = ? AND group_id = ?",
                     array(Post::val('switch_to_group'), $user_id, Post::val('old_group')));
        }
    }

    $_SESSION['SUCCESS'] = L('groupswitchupdated');
    $fs->redirect(Post::val('prev_page'));
} // }}}
// taking ownership {{{
elseif (Req::val('action') == 'takeownership') {
   if (Req::val('prev_page')) {
      $ids = Req::val('ids');
      settype($ids, 'array');
      $redirect_url = Req::val('prev_page');

      if (!empty($ids)) {
          $be->AssignToMe($user, array_keys($ids));
      }
   } else {
       $be->AssignToMe($user, Req::val('ids'));
       $redirect_url = CreateURL('details', Req::val('ids'));
   }

   $_SESSION['SUCCESS'] = L('takenownershipmsg');
   $fs->redirect($redirect_url);
} // }}}
// add to assignees list {{{
elseif (Req::val('action') == 'addtoassignees') {
   if (Req::val('prev_page')) {
      $ids = Req::val('ids');
      settype($ids, 'array');
      $redirect_url = Req::val('prev_page');

      if (!empty($ids)) {
          $be->AddToAssignees($user, array_keys($ids));
      }
   } else {
       $be->AddToAssignees($user, Req::val('ids'));
       $redirect_url = CreateURL('details', Req::val('ids'));
   }

   $_SESSION['SUCCESS'] = L('addedtoassignees');
   $fs->redirect($redirect_url);



} // }}}
// requesting task closure {{{
elseif (Post::val('action') == 'requestclose') {

    $fs->AdminRequest(1, $old_details['attached_to_project'],
            Post::val('task_id'), $user->id, Post::val('reason_given'));
    $fs->logEvent(Post::val('task_id'), 20, Post::val('reason_given'));

    // Now, get the project managers' details for this project
    $sql = $db->Query("SELECT  u.user_id
                         FROM  {users} u
                    LEFT JOIN  {users_in_groups} uig ON u.user_id = uig.user_id
                    LEFT JOIN  {groups} g ON uig.group_id = g.group_id
                        WHERE  g.belongs_to_project = ? AND g.manage_project = '1'",
                      array($proj->id));

    $pms = $db->fetchCol($sql);

    // Call the functions to create the address arrays, and send notifications
    $to   = $notify->SpecificAddresses($pms);
    $msg  = $notify->GenerateMsg('12', Post::val('task_id'));
    $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
    $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);

    $_SESSION['SUCCESS'] = L('adminrequestmade');
    $fs->redirect(CreateURL('details', Req::val('task_id')));
} // }}}
// requesting task re-opening {{{
elseif (Post::val('action') == 'requestreopen') {

    $fs->AdminRequest(2, $proj->id, Post::val('task_id'), $user->id, Post::val('reason_given'));
    $fs->logEvent(Post::val('task_id'), 21, Post::val('reason_given'));
    $be->AddToNotifyList($user->id, Post::val('task_id'));

    // Now, get the project managers' details for this project
    $sql = $db->Query("SELECT  u.user_id
                         FROM  {users} u
                    LEFT JOIN  {users_in_groups} uig ON u.user_id = uig.user_id
                    LEFT JOIN  {groups} g ON uig.group_id = g.group_id
                        WHERE  g.belongs_to_project = ? AND g.manage_project = '1'",
                      array($proj->id));

    $pms = $db->fetchCol($sql);

    // Call the functions to create the address arrays, and send notifications
    $to   = $notify->SpecificAddresses($pms);
    $msg  = $notify->GenerateMsg('12', Post::val('task_id'));
    $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
    $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);


    $_SESSION['SUCCESS'] = L('adminrequestmade');
    $fs->redirect(CreateURL('details', Req::val('task_id')));
} // }}}
// denying a PM request {{{
elseif (Req::val('action') == 'denypmreq' && $user->perms['manage_project']) {

    $result = $db->Query("SELECT  task_id
                            FROM  {admin_requests}
                           WHERE  request_id = ?",
                          array(Req::val('req_id')));
    $req_details = $db->FetchArray($result);

    // Mark the PM request as 'resolved'
    $db->Query("UPDATE  {admin_requests}
                   SET  resolved_by = ?, time_resolved = ?, deny_reason = ?
                 WHERE  request_id = ?",
                array($user->id, time(), Req::val('deny_reason'), Req::val('req_id')));

    $fs->logEvent($req_details['task_id'], 28, Req::val('deny_reason'));
    $notify->Create('13', $req_details['task_id'], Req::val('deny_reason'));

    $_SESSION['SUCCESS'] = L('pmreqdeniedmsg');
    $fs->redirect(Req::val('prev_page'));
} // }}}
// adding a dependency {{{
elseif (Post::val('action') == 'newdep' && $user->can_edit_task($old_details))
{
    if (!Post::val('dep_task_id')) {
        $_SESSION['ERROR'] = L('formnotcomplete');
        $fs->redirect(CreateURL('details', Req::val('task_id')));
    }
    
    if (!is_numeric(Post::val('dep_task_id'))) {
        $_SESSION['ERROR'] = L('formnotnumeric');
        $fs->redirect(CreateURL('details', Req::val('task_id')));
    }
    					
    // First check that the user hasn't tried to add this twice
    $sql1 = $db->Query("SELECT  COUNT(*) FROM {dependencies}
                         WHERE  task_id = ? AND dep_task_id = ?",
            array(Post::val('task_id'), Post::val('dep_task_id')));

    // or that they are trying to reverse-depend the same task, creating a mutual-block
    $sql2 = $db->Query("SELECT  COUNT(*) FROM {dependencies}
                         WHERE  task_id = ? AND dep_task_id = ?",
            array(Post::val('dep_task_id'), Post::val('task_id')));

    // Check that the dependency actually exists!
    $sql3 = $db->Query("SELECT COUNT(*) FROM {tasks} WHERE task_id = ?",
            array(Post::val('dep_task_id')));

    if ($db->fetchOne($sql1) || $db->fetchOne($sql2) || !$db->fetchOne($sql3)
            // Check that the user hasn't tried to add the same task as a dependency
            || Post::val('task_id') == Post::val('dep_task_id'))
    {
        $_SESSION['ERROR'] = L('dependaddfailed');
        $fs->redirect(CreateURL('details', Req::val('task_id')));
    }

    $notify->Create('5', Post::val('task_id'), Post::val('dep_task_id'));
    $notify->Create('15', Post::val('dep_task_id'), Post::val('task_id'));

    // Log this event to the task history, both ways
    $fs->logEvent(Post::val('task_id'), 22, Post::val('dep_task_id'));
    $fs->logEvent(Post::val('dep_task_id'), 23, Post::val('task_id'));

    $db->Query("INSERT INTO  {dependencies} (task_id, dep_task_id)
                     VALUES  (?,?)",
            array(Post::val('task_id'), Post::val('dep_task_id')));

    $_SESSION['SUCCESS'] = L('dependadded');

    $fs->redirect(CreateURL('details', Req::val('task_id')));
} // }}}
// removing a dependency {{{
elseif (Get::val('action') == 'removedep' && $user->can_edit_task($old_details)) {

    $result = $db->Query("SELECT  * FROM {dependencies}
                           WHERE  depend_id = ?",
                        array(Get::val('depend_id')));
    $dep_info = $db->FetchArray($result);

    $notify->Create('6', $dep_info['task_id'], $dep_info['dep_task_id']);
    $notify->Create('16', $dep_info['dep_task_id'], $dep_info['task_id']);

    $fs->logEvent($dep_info['task_id'], 24, $dep_info['dep_task_id']);
    $fs->logEvent($dep_info['dep_task_id'], 25, $dep_info['task_id']);

    $db->Query("DELETE FROM {dependencies} WHERE depend_id = ?",
            array(Get::val('depend_id')));

    $_SESSION['SUCCESS'] = L('depremovedmsg');
    $fs->redirect(CreateURL('details', $dep_info['task_id']));

} // }}}
// user requesting a password change {{{
elseif (Post::val('action') == 'sendmagic') {

    // Check that the username exists
    $sql = $db->Query("SELECT * FROM {users} WHERE user_name = ?",
            array(Post::val('user_name')));

    // If the username doesn't exist, throw an error
    if (!$db->CountRows($sql)) {
        $_SESSION['ERROR'] = L('usernotexist');
        $fs->redirect(CreateURL('lostpw', null));
    }

    $user_details = $db->FetchArray($sql);
    $magic_url    = md5(microtime());

    // Insert the random "magic url" into the user's profile
    $db->Query("UPDATE {users}
                   SET magic_url = ?
                 WHERE user_id = ?",
            array($magic_url, $user_details['user_id']));

    $subject = L('changefspass');

    $message = L('messagefrom')." $baseurl. \n\n"
             . L('magicurlmessage')." \n"
             . "{$baseurl}index.php?do=lostpw&amp;magic=$magic_url\n";

    $to   = $notify->SpecificAddresses(array($user_details), true);
    $mail = $notify->SendEmail($to[0], $subject, $message);
    $jabb = $notify->StoreJabber($to[1], $subject, $message);

    $_SESSION['SUCCESS'] = L('magicurlsent');
    $fs->redirect('./');

} // }}}
// Change the user's password {{{
elseif (Post::val('action') == 'chpass') {
    // Check that the user submitted both the fields, and they are the same
    if (!Post::val('pass1') || !Post::val('magic_url')) {
        $_SESSION['ERROR'] = L('erroronform');
        $fs->redirect('./');
    }

    if (Post::val('pass1') != Post::val('pass2')) {
        $_SESSION['ERROR'] = L('passnomatch');
        $fs->redirect('./');
    }

    $new_pass_hash = $fs->cryptPassword(Post::val('pass1'));
    $db->Query("UPDATE  {users} SET user_pass = ?, magic_url = ''
                 WHERE  magic_url = ?",
            array($new_pass_hash, Post::val('magic_url')));

    $_SESSION['SUCCESS'] = L('passchanged');
    $fs->redirect('./');
} // }}}
// making a task private {{{
elseif (Get::val('action') == 'makeprivate' && $user->perms['manage_project']) {
    $db->Query("UPDATE  {tasks} SET mark_private = '1'
                 WHERE  task_id = ?", array(Get::val('id')));

    $fs->logEvent(Get::val('id'), 26);

    $_SESSION['SUCCESS'] = L('taskmadeprivatemsg');
    $fs->redirect(CreateURL('details', Req::val('id')));
} // }}}
// making a task public {{{
elseif (Get::val('action') == 'makepublic' && $user->perms['manage_project']) {
    $db->Query("UPDATE  {tasks}
                   SET  mark_private = '0'
                 WHERE  task_id = ?", array(Get::val('id')));

    $fs->logEvent(Get::val('id'), 27);

    $_SESSION['SUCCESS'] = L('taskmadepublicmsg');
    $fs->redirect(CreateURL('details', Req::val('id')));
} // }}}
// Adding a vote for a task {{{
elseif (Get::val('action') == 'addvote') {
    
    if ($user->can_vote(Get::val('id'))) {
        $db->Query("INSERT INTO {votes}
                                (user_id, task_id, date_time)
                         VALUES (?,?,?)", array($user->id, Get::val('id'), time()));
                         
        $_SESSION['SUCCESS'] = L('voterecorded');
        $fs->redirect(CreateURL('details', Get::val('id')));
    } else {
        $_SESSION['ERROR'] = L('votefailed');
        $fs->redirect(CreateURL('details', Get::val('id')));
    }
    
} // }}}
?>
