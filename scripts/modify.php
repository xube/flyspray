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
if (Req::has('task_id') || Req::has('id')) {
    $old_details = $fs->GetTaskDetails(Req::num('task_id', Req::num('id')));
}

unset($_SESSION['SUCCESS'], $_SESSION['ERROR']);

switch (Req::val('action'))
{
    // ##################
    // Adding a new task
    // ##################
    case 'newtask.newtask':
        if (!Post::val('item_summary') || !Post::val('detailed_desc')) {
            Flyspray::show_error(L('summaryanddetails'));
            break;
        }
        
        $task_id = $be->CreateTask($_POST);

        // Status and redirect
        if ($task_id) {
            $_SESSION['SUCCESS'] = L('newtaskadded');
            
            if ($user->isAnon()) {        
                Flyspray::Redirect(CreateURL('details', $task_id, null, array('task_token' => $token)));
            } else {
                Flyspray::Redirect(CreateURL('details', $task_id));
            }
        } else {
            Flyspray::show_error(L('databasemodfailed'));
            break;
        }
        break;

    // ##################
    // Modifying an existing task
    // ##################
    case 'details.update':
        if (!$user->can_edit_task($old_details)) {
            break;
        }
        
        if (!Post::val('item_summary') || !Post::val('detailed_desc')) {
            Flyspray::show_error(L('summaryanddetails'));
            break;
        }


        if ($due_date = Post::val('due_date', 0)) {
            $due_date = strtotime(Post::val('due_date') . '+23 hours 59 minutes 59 seconds');
        }

        $time = time();

        $db->Query('UPDATE  {tasks}
                       SET  attached_to_project = ?, task_type = ?, item_summary = ?,
                            detailed_desc = ?, item_status = ?, mark_private = ?,
                            product_category = ?, closedby_version = ?, operating_system = ?,
                            task_severity = ?, task_priority = ?, last_edited_by = ?,
                            last_edited_time = ?, due_date = ?, percent_complete = ?, product_version = ?
                     WHERE  task_id = ?',
                array(Post::val('attached_to_project'), Post::val('task_type'),
                    Post::val('item_summary'), Post::val('detailed_desc'),
                    Post::val('item_status'), ($user->can_change_private($old_details) && Post::val('mark_private')),
                    Post::val('product_category'), Post::val('closedby_version', 0),
                    Post::val('operating_system'), Post::val('task_severity'),
                    Post::val('task_priority'), intval($user->id), $time, $due_date,
                    Post::val('percent_complete'), Post::val('reportedver'),
                    Post::val('task_id')));

        // Update the list of users assigned this task
        if ($user->perms['edit_assignments'] && Post::val('old_assigned') != trim(Post::val('assigned_to')) ) {
            
            // Delete the current assignees for this task
            $db->Query('DELETE FROM {assigned}
                              WHERE task_id = ?',
                                    array(Post::val('task_id')));

            // Convert assigned_to and store them in the 'assigned' table
            foreach (Flyspray::int_explode(' ', trim(Post::val('assigned_to'))) as $key => $val)
            {
                $db->Query('INSERT INTO {assigned}
                                      (task_id, user_id)
                               VALUES (?,?)',
                           array(Post::val('task_id'), $val));
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
            $notify->Create(NOTIFY_TASK_CHANGED, Post::val('task_id'), $changes);
        }

        if (Post::val('old_assigned') != trim(Post::val('assigned_to')) ) {
            // Log to task history
            $fs->logEvent(Post::val('task_id'), 14, trim(Post::val('assigned_to')), Post::val('old_assigned'), null, $time);

            // Notify the new assignees what happened.  This obviously won't happen if the task is now assigned to no-one.
            if (Post::val('assigned_to') != '') {
                $new_assignees = array_diff(Flyspray::int_explode(' ', Post::val('assigned_to')), Flyspray::int_explode(' ', Post::val('old_assigned')));
                // Remove current user from notification list
                if (!$user->prefs['notify_own']) {
                    $new_assignees = array_filter($new_assignees, create_function('$u', 'global $user; return $user->id != $u;'));
                }
                $notify->Create(NOTIFY_NEW_ASSIGNEE, Post::val('task_id'), null, $notify->SpecificAddresses($new_assignees));
            }
        }
        
        $be->add_comment($old_details, Post::val('comment_text'), $time);
        $be->DeleteFiles($user, Post::val('task_id'));
        $be->UploadFiles($user, Post::val('task_id'), '0', 'usertaskfile');

        $_SESSION['SUCCESS'] = L('taskupdated');
        Flyspray::Redirect(CreateURL('details', Post::val('task_id')));
        break;
    
    // ##################
    // closing a task
    // ##################
    case 'details.close':
        if (!$user->can_close_task($old_details)) {
            break;
        }
        
        if (!Post::val('resolution_reason')) {
            Flyspray::show_error(L('noclosereason'));
            break;
        }
        
        $be->close_task($user, Post::val('task_id'), Post::val('resolution_reason'), Post::val('closure_comment', ''), Post::val('mark100', true));

        $_SESSION['SUCCESS'] = L('taskclosedmsg');
        Flyspray::Redirect(CreateURL('details', Post::val('task_id')));
        break;
    
    case 'reopen':
    // ##################
    // re-opening an task
    // ##################
        if (!$user->can_close_task($old_details)) {
            break;
        }
        
        // Get last %
        $old_percent = $db->Query("SELECT old_value, new_value
                                     FROM {history}
                                    WHERE field_changed = 'percent_complete'
                                          AND task_id = ? AND old_value != '100'
                                 ORDER BY event_date DESC",
                                  array(Get::val('task_id')));
        $old_percent = $db->FetchRow($old_percent);
        
        $db->Query("UPDATE  {tasks}
                       SET  resolution_reason = '0', closure_comment = '0', date_closed = 0,
                            last_edited_time = ?, last_edited_by = ?, is_closed = '0', percent_complete = ?
                     WHERE  task_id = ?",
                    array(time(), $user->id, $old_percent['old_value'], Get::val('task_id')));
        
        $fs->logEvent(Get::val('task_id'), '0', $old_percent['old_value'], $old_percent['new_value'], 'percent_complete');

        $notify->Create(NOTIFY_TASK_REOPENED, Get::val('task_id'));

        if ($fs->AdminRequestCheck(2, Get::val('task_id')) == '1') {
            // If there's an admin request related to this, close it
            $db->Query("UPDATE  {admin_requests}
                           SET  resolved_by = ?, time_resolved = ?
                         WHERE  task_id = ? AND request_type = ?",
                      array($user->id, time(), Get::val('task_id'), 2));
        }

        $fs->logEvent(Get::val('task_id'), 13);

        $_SESSION['SUCCESS'] = L('taskreopenedmsg');
        Flyspray::Redirect(CreateURL('details', Get::val('task_id')));
        break;

    // ##################
    // adding a comment
    // ##################
    case 'details.addcomment':
        if (!$be->add_comment($old_details, Post::val('comment_text'))) {
            Flyspray::show_error(L('nocommententered'));
            break;
        }
            
        if (Post::val('notifyme') == '1') {
            // If the user wanted to watch this task for changes
            $be->AddToNotifyList($user->id, Post::val('task_id'));
        }

        $_SESSION['SUCCESS'] = L('commentaddedmsg');
        Flyspray::Redirect(CreateURL('details', Post::val('task_id')));
        break;

    // ##################
    // sending a new user a confirmation code 
    // ##################
    case 'register.sendcode':
        if (!Post::val('user_name') || !Post::val('real_name')
            || (!Post::val('email_address') && Post::num('notify_type') == '1')
            || (!Post::val('jabber_id') && Post::num('notify_type') == '2')
            || (!Post::val('jabber_id') && !Post::val('email_address') && Post::num('notify_type') == '3')
        ) {
            // If the form wasn't filled out correctly, show an error
            Flyspray::show_error(L('registererror'));
            break;
        }
        
        $mailpattern = "/^[a-z0-9._\-']+(?:\+[a-z0-9._-]+)?@([a-z0-9.-]+\.)+[a-z]{2,4}+$/i";
        if (Post::val('email_address') && !preg_match($mailpattern, Post::val('email_address'))) {
            Flyspray::show_error(L('novalidemail'));
            break;
        }
        if (Post::val('jabber_id') && !preg_match($mailpattern, Post::val('jabber_id'))) {
            Flyspray::show_error(L('novalidjabber'));
            break;
        }

        // Limit lengths
        $user_name = substr(trim(Post::val('user_name')), 0, 32);
        $real_name = substr(trim(Post::val('real_name')), 0, 100);
        // Remove doubled up spaces and control chars
        if (version_compare(PHP_VERSION, '4.3.4') == 1) {
            $user_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $user_name);
            $real_name = preg_replace('![\x00-\x1f\s]+!u', ' ', $real_name);
        }
        // Strip special chars
        $user_name = utf8_keepalphanum($user_name);

        if (!$user_name || !$real_name) {
            Flyspray::show_error(L('entervalidusername'));
            break;
        }

        // Delete registration codes older than 24 hours
        $yesterday = time() - 86400;
        $db->Query("DELETE FROM {registrations} WHERE reg_time < ?", array($yesterday));

        $sql = $db->Query('SELECT COUNT(*) FROM {users} u, {registrations} r
                           WHERE  u.user_name = ? OR r.user_name = ?',
                           array($user_name, $user_name));
        if ($db->fetchOne($sql)) {
            Flyspray::show_error(L('usernametaken'));
            break;
        }

        $sql = $db->Query('SELECT COUNT(*) FROM {users} WHERE jabber_id = ? OR email_address = ?',
                          array(Post::val('jabber_id'), Post::val('email_address')));
        if ($db->fetchOne($sql)) {
            Flyspray::show_error(L('emailtaken'));
            break;
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
                        Post::num('notify_type'), $magic_url));

        $notify->Create(NOTIFY_CONFIRMATION, null, array($baseurl, $magic_url, $user_name, $confirm_code),
                        array(Post::val('email_address')), Post::num('notify_type'));

        $_SESSION['SUCCESS'] = L('codesent');
        Flyspray::Redirect('./');
        break;

    // ##################
    // new user self-registration with a confirmation code
    // ##################
    case 'register.registeruser':
        if (!$fs->prefs['anon_reg']) {
            break;
        }
        
        if (!Post::val('user_pass') || !Post::val('confirmation_code')) {
            Flyspray::show_error(L('formnotcomplete'));
            break;
        }

        if (Post::val('user_pass') != Post::val('user_pass2')) {
            Flyspray::show_error(L('nomatchpass'));
            break;
        }

        // Check that the user entered the right confirmation code
        $sql = $db->Query("SELECT * FROM {registrations} WHERE magic_url = ?",
                array(Post::val('magic_url')));
        $reg_details = $db->FetchRow($sql);

        if ($reg_details['confirm_code'] != trim(Post::val('confirmation_code'))) {
            Flyspray::show_error(L('confirmwrong'));
            break;
        }

        if (!$be->create_user($reg_details['user_name'], Post::val('user_pass'), $reg_details['real_name'], $reg_details['jabber_id'],
                         $reg_details['email_address'], $reg_details['notify_type'], $fs->prefs['anon_group'])) {
            Flyspray::show_error(L('usernametaken'));
            break;
        }

        $_SESSION['SUCCESS'] = L('accountcreated');
        $page->pushTpl('register.ok.tpl');
        break;

    // ##################
    // new user self-registration with a confirmation code
    // ##################
    case 'newuser.newuser':
        if (!($user->perms['is_admin'] || $user->can_self_register())) {
            break;
        }

        if (!Post::val('user_name') || !Post::val('real_name')
            || (!Post::val('email_address') && Post::num('notify_type') == '1')
            || (!Post::val('jabber_id') && Post::num('notify_type') == '2')
            || (!Post::val('jabber_id') && !Post::val('email_address') && Post::num('notify_type') == '3')
        ) {
            // If the form wasn't filled out correctly, show an error
            Flyspray::show_error(L('registererror'));
            break;
        }
        
        if (Post::val('user_pass') != Post::val('user_pass2')) {
            Flyspray::show_error(L('nomatchpass'));
            break;
        }
        
        if ($user->perms['is_admin']) {
            $group_in = Post::val('group_in');
        } else {
            $group_in = $fs->prefs['anon_group'];
        }

        if (!$be->create_user(Post::val('user_name'), Post::val('user_pass'),
                              Post::val('real_name'), Post::val('jabber_id'),
                              Post::val('email_address'), Post::num('notify_type'), $group_in)) {
            Flyspray::show_error(L('usernametaken'));
            break;
        }

        if ($user->perms['is_admin']) {
            $_SESSION['SUCCESS'] = L('newusercreated');
            Flyspray::Redirect(CreateURL('admin', 'groups'));
        } else {
            $_SESSION['SUCCESS'] = L('accountcreated');
            $page->pushTpl('register.ok.tpl');
        }
        break;
        
    // ##################
    //  adding a new group
    // ##################
    case 'newgroup.newgroup':
        if (!$user->perms['manage_project'] || Post::val('project') != Post::val('belongs_to_project') ) {
            break;
        }
        
        if (!Post::val('group_name') || !Post::val('group_desc')) {
            Flyspray::show_error(L('formnotcomplete'));
            break;
        } else {
            // Check to see if the group name is available
            $sql = $db->Query("SELECT  COUNT(*)
                                 FROM  {groups}
                                WHERE  group_name = ? AND belongs_to_project = ?",
                    array(Post::val('group_name'), Post::val('project')));

            if ($db->fetchOne($sql)) {
                Flyspray::show_error(L('groupnametaken'));
                break;
            } else {
                $cols = array('belongs_to_project', 'group_name', 'group_desc', 'manage_project', 'edit_own_comments',
                        'view_tasks', 'open_new_tasks', 'modify_own_tasks', 'add_votes',
                        'modify_all_tasks', 'view_comments', 'add_comments', 'edit_assignments',
                        'edit_comments', 'delete_comments', 'create_attachments',
                        'delete_attachments', 'view_history', 'close_own_tasks',
                        'close_other_tasks', 'assign_to_self', 'view_attachments',
                        'assign_others_to_self', 'view_reports', 'group_open');
                $db->Query("INSERT INTO  {groups} ( ". join(',', $cols).")
                                 VALUES  (".join(',', array_fill(0, count($cols), '?')).")",
                                     array_map('Post_to0', $cols));

                $_SESSION['SUCCESS'] = L('newgroupadded');
            }
        }

        if (Post::val('project')) {
            Flyspray::Redirect(CreateURL('pm', 'groups', Post::val('project')));
        } else {
            Flyspray::Redirect(CreateURL('admin', 'groups'));
        }
        break;
        
    // ##################
    //  Update the global application preferences
    // ##################
    case 'globaloptions':
        if (!$user->perms['is_admin']) {
            break;
        }
        
        $settings = array('jabber_server', 'jabber_port', 'jabber_username', 'notify_registration',
                'jabber_password', 'anon_group', 'user_notify', 'admin_email',
                'lang_code', 'spam_proof', 'default_project', 'dateformat', 'jabber_ssl',
                'dateformat_extended', 'anon_reg', 'global_theme', 'smtp_server', 'page_title',
                'smtp_user', 'smtp_pass', 'funky_urls', 'reminder_daemon','cache_feeds');
        foreach ($settings as $setting) {
            $db->Query('UPDATE {prefs} SET pref_value = ? WHERE pref_name = ?',
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
        Flyspray::Redirect(CreateURL('admin','prefs'));
        break;
        
    // ##################
    // adding a new project
    // ##################
    case 'admin.newproject':
        if (!$user->perms['is_admin']) {
            break;
        }
        
        if (!Post::val('project_title')) {
            Flyspray::show_error(L('emptytitle'));
            break;
        }

        $db->Query('INSERT INTO  {projects}
                                 ( project_title, theme_style, intro_message,
                                   others_view, anon_open, project_is_active,
                                   visible_columns, lang_code)
                         VALUES  (?, ?, ?, ?, ?, 1, ?, ?)',
                  array(Post::val('project_title'), Post::val('theme_style'),
                      Post::val('intro_message'), Post::val('others_view', 0),
                      Post::val('anon_open', 0),
                      'id tasktype severity summary status dueversion progress', Post::val('lang_code')));

        $sql = $db->Query('SELECT project_id FROM {projects} ORDER BY project_id DESC', false, 1);
        $pid = $db->fetchOne($sql);

        $cols = array( 'manage_project', 'view_tasks', 'open_new_tasks',
                'modify_own_tasks', 'modify_all_tasks', 'view_comments',
                'add_comments', 'edit_comments', 'delete_comments', 'view_attachments',
                'create_attachments', 'delete_attachments', 'view_history', 'add_votes',
                'close_own_tasks', 'close_other_tasks', 'assign_to_self', 'edit_own_comments',
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
                                 ( project_id, category_name,
                                   show_in_list, category_owner, lft, rgt)
                         VALUES  ( ?, ?, 0, 0, 1, 4)", array($pid, 'root'));
                         
        $db->Query("INSERT INTO  {list_category}
                                 ( project_id, category_name,
                                   show_in_list, category_owner, lft, rgt )
                         VALUES  ( ?, ?, 1, 0, 2, 3)", array($pid, 'Backend / Core'));

        $db->Query("INSERT INTO  {list_os}
                                 ( project_id, os_name, list_position, show_in_list )
                         VALUES  (?, ?, 1, 1)", array($pid, 'All'));

        $db->Query("INSERT INTO  {list_version}
                                 ( project_id, version_name, list_position,
                                   show_in_list, version_tense )
                         VALUES  (?, ?, 1, 1, 2)", array($pid, '1.0'));

        $_SESSION['SUCCESS'] = L('projectcreated');
        Flyspray::Redirect(CreateURL('pm', 'prefs', $pid));
        break;

    // ##################
    // updating project preferences 
    // ##################
    case 'pm.updateproject':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        if (Post::val('delete_project')) {
            $be->delete_project($user, Post::val('project_id'), Post::val('move_to'));
            $_SESSION['SUCCESS'] = L('projectdeleted');
            if (Post::val('move_to')) {
                Flyspray::Redirect(CreateURL('pm', 'prefs', Post::val('move_to')));
            } else {
                Flyspray::Redirect($baseurl);
            }
        }
        
        if (!Post::val('project_title')) {
            Flyspray::show_error(L('emptytitle'));
            break;
        }

        $cols = array( 'project_title', 'theme_style', 'default_cat_owner', 'lang_code',
                'intro_message', 'project_is_active', 'others_view', 'anon_open',
                'notify_email', 'notify_jabber', 'notify_subject', 'notify_reply',
                'feed_description', 'feed_img_url', 'comment_closed', 'auto_assign');
        $args = array_map('Post_to0', $cols);
        $cols[] = 'notify_types';
        $args[] = implode(' ', Post::val('notify_types'));
        $cols[] = 'last_updated';
        $args[] = time();
        $args[] = Post::val('project_id', 0);

        $update = $db->Query("UPDATE  {projects}
                                 SET  ".join('=?, ', $cols)."=?
                               WHERE  project_id = ?", $args);

        $update = $db->Query("UPDATE {projects} SET visible_columns = ? WHERE project_id = ?",
                array(trim(Post::val('visible_columns')), Post::val('project_id')));


        $_SESSION['SUCCESS'] = L('projectupdated');
        Flyspray::Redirect(CreateURL('pm', 'prefs', $proj->id));
        break;

    // ##################
    // modifying user details/profile
    // ##################
    case 'admin.edituser':
    case 'myprofile.edituser':
        if (Post::val('delete_user') && $be->delete_user($user, Post::val('user_id'))) {
            $_SESSION['SUCCESS'] = L('userdeleted');
            Flyspray::Redirect(CreateURL('admin', 'groups'));
        }
        
        if (!Post::val('onlypmgroup')):
        if ($user->perms['is_admin'] || $user->id == Post::val('user_id')): // only admin or user himself can change
        
        if (!Post::val('real_name') || (!Post::val('email_address') && !Post::val('jabber_id'))) {
            Flyspray::show_error(L('realandnotify'));
            break;
        }
        
        if ( (!$user->perms['is_admin'] || $user->id == Post::val('user_id')) && !Post::val('oldpass')
             && (Post::val('changepass') || Post::val('confirmpass')) ) {
            Flyspray::show_error(L('nooldpass'));
            break;
        }
        if (Post::val('changepass') || Post::val('confirmpass')) {
            if (Post::val('changepass') != Post::val('confirmpass')) {
                Flyspray::show_error(L('passnomatch'));
                break;
            }
            if (Post::val('oldpass')) {
              $sql = $db->Query('SELECT user_pass FROM {users} WHERE user_id = ?', array(Post::val('user_id')));
              $oldpass =  $db->FetchRow($sql);

              if ($fs->cryptPassword(Post::val('oldpass')) != $oldpass['user_pass']){
                Flyspray::show_error(L('oldpasswrong'));
                break;
              }  
            }
            $new_hash = $fs->cryptPassword(Post::val('changepass'));
            $db->Query('UPDATE {users} SET user_pass = ? WHERE user_id = ?',
                    array($new_hash, Post::val('user_id')));

            // If the user is changing their password, better update their cookie hash
            if ($user->id == Post::val('user_id')) {
                $fs->setcookie('flyspray_passhash',
                        crypt($new_hash, $conf['general']['cookiesalt']), time()+3600*24*30);
            }
        }

        $db->Query('UPDATE  {users}
                       SET  real_name = ?, email_address = ?, notify_own = ?,
                            jabber_id = ?, notify_type = ?,
                            dateformat = ?, dateformat_extended = ?,
                            tasks_perpage = ?
                     WHERE  user_id = ?',
                array(Post::val('real_name'), Post::val('email_address'), Post::val('notify_own', 0),
                    Post::val('jabber_id', 0), Post::num('notify_type'),
                    Post::val('dateformat', 0), Post::val('dateformat_extended', 0),
                    Post::val('tasks_perpage'), Post::val('user_id')));

        endif; // end only admin or user himself can change
        
        if ($user->perms['is_admin']) {
            $db->Query('UPDATE {users} SET account_enabled = ?  WHERE user_id = ?',
                    array(Post::val('account_enabled', 0), Post::val('user_id')));

            $db->Query('UPDATE {users_in_groups} SET group_id = ?
                         WHERE group_id = ? AND user_id = ?',
                    array(Post::val('group_in'), Post::val('old_global_id'), Post::val('user_id')));
        }
        
        endif; // end non project group changes

        if ($user->perms['manage_project'] && !is_null(Post::val('project_group_in')) && Post::val('project_group_in') != Post::val('old_project_id')) {
            $sql = $db->Query('UPDATE {users_in_groups} SET group_id = ?
                                WHERE group_id = ? AND user_id = ?',
                              array(Post::val('project_group_in'), Post::val('old_project_id'), Post::val('user_id')));
            if (!$db->affectedRows($sql) && Post::val('project_group_in')) {
                $db->Query('INSERT INTO {users_in_groups} (group_id, user_id) VALUES(?, ?)',
                           array(Post::val('project_group_in'), Post::val('user_id')));
            }
        }

        $_SESSION['SUCCESS'] = L('userupdated');
        Flyspray::Redirect(Post::val('prev_page'));
        break;

    // ##################
    // updating a group definition
    // ##################
    case 'pm.editgroup':
    case 'admin.editgroup':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        if (!Post::val('group_name') || !Post::val('group_desc')) {
            Flyspray::show_error(L('groupanddesc'));
            break;
        }

        $cols = array('group_name', 'group_desc');
        
        // Add a user to a group
        if ($uid = Post::val('uid')) {
            $uids = preg_split('/[\s,;]+/', $uid, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($uids as $uid) {
                if (!is_numeric($uid)) {
                    $sql = $db->Query('SELECT user_id FROM {users} WHERE user_name = ?', array($uid));
                    $uid = $db->FetchOne($sql);
                }
                
                // If user is already a member of one of the project's groups, **move** (not add) him to the new group
                $sql = $db->Query('SELECT g.group_id
                                     FROM {users_in_groups} uig, {groups} g
                                    WHERE g.group_id = uig.group_id AND uig.user_id = ? AND belongs_to_project = ?',
                                    array($uid, $proj->id));
                if ($db->CountRows($sql)) {
                    $oldid = $db->FetchOne($sql);
                    $db->Query('UPDATE {users_in_groups} SET group_id = ? WHERE user_id = ? AND group_id = ?',
                                array(Post::val('group_id'), $uid, $oldid));
                } else {
                    $db->Query('INSERT INTO {users_in_groups} (group_id, user_id) VALUES(?, ?)',
                                array(Post::val('group_id'), $uid));
                }
            }
        }
        
        if (Post::val('delete_group') && Post::val('group_id') != '1') {
            $db->Query('DELETE FROM {groups} WHERE group_id = ?', Post::val('group_id'));
            
            if (Post::val('move_to')) {
                $db->Query('UPDATE {users_in_groups} SET group_id = ? WHERE group_id = ?',
                            array(Post::val('move_to'), Post::val('group_id')));
            }
            
            $_SESSION['SUCCESS'] = L('groupupdated');
            Flyspray::Redirect(CreateURL( (($proj->id) ? 'pm' : 'admin'), 'groups', $proj->id));
        }
        // Allow all groups to update permissions except for global Admin
        if (Post::val('group_id') != '1') {
            $cols = array_merge($cols,
                                array('manage_project', 'view_tasks', 'edit_own_comments', 
                                  'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
                                  'view_comments', 'add_comments', 'edit_comments', 'delete_comments',
                                  'view_attachments', 'create_attachments', 'delete_attachments',
                                  'view_history', 'close_own_tasks', 'close_other_tasks', 'edit_assignments',
                                  'assign_to_self', 'assign_others_to_self', 'add_to_assignees', 'view_reports',
                                  'add_votes', 'group_open'));
        }

        $args = array_map('Post_to0', $cols);
        $args[] = Post::val('group_id');
        $args[] = $proj->id;

        $db->Query("UPDATE  {groups}
                       SET  ".join('=?,', $cols)."=?
                     WHERE  group_id = ? AND belongs_to_project = ?", $args);

        $_SESSION['SUCCESS'] = L('groupupdated');
        Flyspray::Redirect(Post::val('prev_page'));
        break;
        
    // ##################
    // updating a list
    // ##################
    case 'update_list':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        $listname     = Post::val('list_name');
        $listposition = Post::val('list_position');
        $listshow     = Post::val('show_in_list');
        $listdelete   = Post::val('delete');
        $listid       = Post::val('id');

        $redirectmessage = L('listupdated');

        for($i = 0; $i < count($listname); $i++) {
            if ($listname[$i] != '') {
                $update = $db->Query("UPDATE  $list_table_name
                                         SET  $list_column_name = ?, list_position = ?, show_in_list = ?
                                       WHERE  $list_id = '{$listid[$i]}'",
                        array($listname[$i], intval($listposition[$i]), intval($listshow[$i])));
            } else {
                $redirectmessage = L('listupdated') . ' ' . L('fieldsmissing');
            }
        }

        if (is_array($listdelete)) {
            $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
            $db->Query("DELETE FROM $list_table_name WHERE $deleteids");
        }

        $_SESSION['SUCCESS'] = $redirectmessage;
        Flyspray::Redirect(Post::val('prev_page'));
        break;

    // ##################
    // adding a list item
    // ##################
    case 'pm.add_to_list':
    case 'admin.add_to_list':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        if (!Post::val('list_name')) {
            Flyspray::show_error(L('fillallfields'));
            break;
        }
        
        $position = intval(Post::val('list_position'));
        if (!$position) {
            $position = $db->FetchOne($db->Query("SELECT max(list_position)+1
                                                    FROM $list_table_name
                                                   WHERE project_id = ?",
                                                 array(Post::val('project_id', '0'))));
        }
        
        $db->Query("INSERT INTO  $list_table_name
                                 (project_id, $list_column_name, list_position, show_in_list)
                         VALUES  (?, ?, ?, ?)",
                array(Post::val('project_id', '0'), Post::val('list_name'), $position, '1'));

        $_SESSION['SUCCESS'] = L('listitemadded');
        Flyspray::Redirect(Post::val('prev_page'));
        break;

    // ##################
    // updating the version list
    // ##################
    case 'update_version_list':
        if (!$user->perms['manage_project']) {
            break;
        }
        
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
                $redirectmessage = L('listupdated') . ' ' . L('fieldsmissing');
            }
        }

        if (is_array($listdelete)) {
            $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
            $db->Query("DELETE FROM $list_table_name WHERE $deleteids");
        }

        $_SESSION['SUCCESS'] = $redirectmessage;
        Flyspray::Redirect(Post::val('prev_page'));
        break;
        
    // ##################
    // adding a version list item
    // ##################
    case 'pm.add_to_version_list':
    case 'admin.add_to_version_list':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        if (!Post::val('list_name')) {
            Flyspray::show_error(L('fillallfields'));
            break;
        }

        $db->Query("INSERT INTO  $list_table_name
                                (project_id, $list_column_name, list_position, show_in_list, version_tense)
                        VALUES  (?, ?, ?, ?, ?)",
                 array(Post::val('project_id','0'), Post::val('list_name'),
                     Post::num('list_position'), '1', Post::val('version_tense')));

        $_SESSION['SUCCESS'] = L('listitemadded');
        Flyspray::Redirect(Post::val('prev_page'));
        break;

    // ##################
    // updating the category list
    // ##################
    case 'update_category':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        $listname     = Post::val('list_name');
        $listshow     = Post::val('show_in_list');
        $listid       = Post::val('id');
        $listowner    = Post::val('category_owner');
        $listdelete   = Post::val('delete');

        $redirectmessage = L('listupdated');

        for ($i = 0; $i < count($listname); $i++) {
            if ($listname[$i] != '') {
                $update = $db->Query("UPDATE  {list_category}
                                         SET  category_name = ?,
                                              show_in_list = ?, category_owner = ?
                                       WHERE  category_id = ?",
                                  array($listname[$i], intval($listshow[$i]), intval($listowner[$i]), $listid[$i]));
            } else {
                $redirectmessage = L('listupdated') . ' ' . L('fieldsmissing');
            }
        }

        if (is_array($listdelete)) {
            $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
            $db->Query("DELETE FROM {list_category} WHERE $deleteids");
        }

        $_SESSION['SUCCESS'] = $redirectmessage;
        Flyspray::Redirect(Post::val('prev_page'));
        break;
    
    // ##################
    // adding a category list item
    // ##################
    case 'pm.add_category':
    case 'admin.add_category':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        if (!Post::val('list_name')) {
            Flyspray::show_error(L('fillallfields'));
            break;
        }

        // Get right value of last node
        $right = $db->Query('SELECT rgt FROM {list_category} WHERE category_id = ?', array(Post::val('parent_id', -1)));
        $right = $db->FetchOne($right);
        $db->Query('UPDATE {list_category} SET rgt=rgt+2 WHERE rgt >= ? AND project_id = ?', array($right, Post::val('project_id', 0)));
        $db->Query('UPDATE {list_category} SET lft=lft+2 WHERE lft >= ? AND project_id = ?', array($right, Post::val('project_id', 0)));
        
        $db->Query("INSERT INTO  {list_category}
                                 ( project_id, category_name, show_in_list, category_owner, lft, rgt )
                         VALUES  (?, ?, 1, ?, ?, ?)",
                array(Post::val('project_id', 0), Post::val('list_name'),
                      Post::val('category_owner', 0) == '' ? '0' : Post::val('category_owner', 0), $right, $right+1));

        $_SESSION['SUCCESS'] = L('listitemadded');
        Flyspray::Redirect(Post::val('prev_page'));
        break;

    // ##################
    // adding a related task entry
    // ##################
    case 'details.add_related':
        if (!$user->can_edit_task($old_details)) {
            break;
        }

        $sql = $db->Query('SELECT  attached_to_project
                             FROM  {tasks}
                            WHERE  task_id = ?',
                          array(Post::val('related_task')));
        if (!$db->CountRows($sql)) {
            Flyspray::show_error(L('relatedinvalid'));
            break;
        }

        $sql = $db->Query("SELECT related_id
                             FROM {related}
                            WHERE this_task = ? AND related_task = ?
                                  OR
                                  related_task = ? AND this_task = ?",
                          array(Post::val('id'), Post::val('related_task'),
                                Post::val('id'), Post::val('related_task')));
       
        if ($db->CountRows($sql)) {
            Flyspray::show_error(L('relatederror'));
            break;
        }
            
        $db->Query("INSERT INTO {related} (this_task, related_task) VALUES(?,?)",
                array(Post::val('id'), Post::val('related_task')));

        $fs->logEvent(Post::val('id'), 11, Post::val('related_task'));
        $fs->logEvent(Post::val('related_task'), 15, Post::val('id'));

        $notify->Create(NOTIFY_REL_ADDED, Post::val('id'), Post::val('related_task'));

        $_SESSION['SUCCESS'] = L('relatedaddedmsg');
        Flyspray::Redirect(CreateURL('details', Post::val('id').'#related'));
        break;

    // ##################
    // Removing a related task entry
    // ##################
    case 'remove_related':
        if (!$user->can_edit_task($old_details)) {
            break;
        }

        foreach (Post::val('related_id') as $related) {
            $db->Query('DELETE FROM {related} WHERE related_id = ? AND (this_task = ? OR related_task = ?)',
                       array($related, Post::num('id'), Post::num('id')));
            if ($db->AffectedRows()) {
                $fs->logEvent(Post::num('id'), 12, $related);
                $fs->logEvent($related, 16, Post::num('id'));
                $_SESSION['SUCCESS'] = L('relatedremoved');
            }
        }

        Flyspray::Redirect(CreateURL('details', Post::num('id')) . '#related');
        break;

    // ##################
    // adding a user to the notification list
    // ##################
    case 'details.add_notification':
        if (Req::val('prev_page')) {
            $ids = Req::val('ids');
            settype($ids, 'array');

            if (!empty($ids)) {
                $result = $be->AddToNotifyList($user->id, array_keys($ids));
            }
            Flyspray::Redirect(Req::val('prev_page'));
        } else {
            $result = $be->AddToNotifyList(Req::val('user_id'), Req::val('ids'));
        }

        if (!$result) {
            Flyspray::show_error(L('couldnotaddusernotif'));
            break;
        }
        
        $_SESSION['SUCCESS'] = L('notifyadded');
        Flyspray::Redirect(CreateURL('details', Req::val('id')) . '#notify');
        break;

    // ##################
    // removing a notification entry
    // ##################
    case 'remove_notification':
        if (Req::val('prev_page')) {
            $ids = Req::val('ids');
            settype($ids, 'array');
            $redirect_url = Req::val('prev_page');

            if (!empty($ids)) {
                $be->RemoveFromNotifyList($user->id, array_keys($ids));
            }
            Flyspray::Redirect(Req::val('prev_page'));
        } else {
            $be->RemoveFromNotifyList(Req::val('user_id'), Req::val('ids'));
        }

        $_SESSION['SUCCESS'] = L('notifyremoved');
        Flyspray::Redirect(CreateURL('details', Req::val('ids')) . '#notify');
        break;

    // ##################
    // editing a comment
    // ##################
    case 'editcomment':
        if (!($user->perms['edit_comments'] || $user->perms['edit_own_comments'])) {
            break;
        }
        
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
                
        $be->UploadFiles($user, Post::val('task_id'), Post::val('comment_id'));
        $be->DeleteFiles($user, Post::val('task_id'));

        $_SESSION['SUCCESS'] = L('editcommentsaved');
        Flyspray::Redirect(CreateURL('details', Req::val('task_id')));
        break;
        
    // ##################
    // deleting a comment
    // ##################
    case 'details.deletecomment':
        if (!$user->perms['delete_comments']) {
            break;
        }
        
        $result = $db->Query("SELECT  task_id, comment_text, user_id, date_added
                                FROM  {comments}
                               WHERE  comment_id = ?",
                            array(Get::val('comment_id')));
        $comment = $db->FetchRow($result);

        // Check for files attached to this comment
        $check_attachments = $db->Query("SELECT  *
                                           FROM  {attachments}
                                          WHERE  comment_id = ?",
                                        array(Req::val('comment_id')));

        if ($db->CountRows($check_attachments) && !$user->perms['delete_attachments']) {
            Flyspray::show_error(L('commentattachperms'));
            break;
        }

        $db->Query("DELETE FROM {comments} WHERE comment_id = ?",
                array(Req::val('comment_id')));

        $fs->logEvent(Req::val('task_id'), 6, $comment['user_id'],
                $comment['comment_text'], $comment['date_added']);

        while ($attachment = $db->FetchRow($check_attachments)) {
            $db->Query("DELETE from {attachments} WHERE attachment_id = ?",
                    array($attachment['attachment_id']));

            @unlink('attachments/' . $attachment['file_name']);

            $fs->logEvent($attachment['task_id'], 8, $attachment['orig_name']);
        }

        $_SESSION['SUCCESS'] = L('commentdeletedmsg');
        Flyspray::Redirect(CreateURL('details', $comment['task_id']));
        break;
        
    // ##################
    // adding a reminder
    // ##################
    case 'details.addreminder':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        $how_often  = Post::val('timeamount1', 1) * Post::val('timetype1');
        $start_time = strtotime(Post::val('timeamount2', 0));
        
        $id = Post::val('to_user_id');
        if (!is_numeric($id)) {
            $sql = $db->Query('SELECT user_id FROM {users} WHERE user_name = ?', array($id));
            $id = $db->FetchOne($sql);
        }
        
        if (!$id) {
            Flyspray::show_error(L('usernotexist'));
            break;
        }
        
        $db->Query("INSERT INTO  {reminders}
                                 ( task_id, to_user_id, from_user_id,
                                   start_time, how_often, reminder_message )
                         VALUES  (?,?,?,?,?,?)",
                array(Post::val('id'), $id, $user->id,
                    $start_time, $how_often, Post::val('reminder_message')));

        $fs->logEvent(Post::val('id'), 17, $id);

        $_SESSION['SUCCESS'] = L('reminderaddedmsg');
        Flyspray::Redirect(CreateURL('details', Req::val('id')).'#remind');
        break;
        
    // ##################
    // removing a reminder
    // ##################
    case 'deletereminder':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        foreach (Post::val('reminder_id') as $reminder_id) {
            $sql = $db->Query('SELECT to_user_id FROM {reminders} WHERE reminder_id = ?',
                              array($reminder_id));
            $reminder = $db->fetchOne($sql);
            $db->Query('DELETE FROM {reminders} WHERE reminder_id = ?',
                       array($reminder_id));

            $fs->logEvent(Post::val('task_id'), 18, $reminder);
        }

        $_SESSION['SUCCESS'] = L('reminderdeletedmsg');
        Flyspray::Redirect(CreateURL('details', Req::val('task_id')).'#remind');
        break;
        
    // ##################
    // change a bunch of users' groups
    // ##################
    case 'movetogroup':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        foreach (Post::val('users') as $user_id => $val) {
            if (Post::val('switch_to_group') == '0') {
                $db->Query('DELETE FROM  {users_in_groups}
                                  WHERE  user_id = ? AND group_id = ?',
                        array($user_id, Post::val('old_group')));
            } else {
                $db->Query('UPDATE  {users_in_groups}
                               SET  group_id = ?
                             WHERE  user_id = ? AND group_id = ?',
                         array(Post::val('switch_to_group'), $user_id, Post::val('old_group')));
            }
        }

        $_SESSION['SUCCESS'] = L('groupswitchupdated');
        Flyspray::Redirect(Post::val('prev_page'));
        break;
        
    // ##################
    // taking ownership
    // ##################
    case 'takeownership':
        if (!$user->perms['edit_assignments']) {
            break;
        }
        
        if (Req::val('prev_page')) {
            $ids = Req::val('ids');
            settype($ids, 'array');
            $redirect_url = Req::val('prev_page');

            if (!empty($ids)) {
                $be->AssignToMe($user->id, array_keys($ids));
            }
        } else {
            $be->AssignToMe($user->id, Req::val('ids'));
            $redirect_url = CreateURL('details', Req::val('ids'));
        }

        $_SESSION['SUCCESS'] = L('takenownershipmsg');
        Flyspray::Redirect($redirect_url);
        break;
        
    // ##################
    // add to assignees list
    // ##################
    case 'addtoassignees':
        if (!$user->perms['edit_assignments']) {
            break;
        }
        
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
        Flyspray::Redirect($redirect_url);
        break;

    // ##################
    // requesting task closure
    // ##################
    case 'requestclose':
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
        $notify->Create(NOTIFY_PM_REQUEST, Post::val('task_id'), null, $notify->SpecificAddresses($pms));

        $_SESSION['SUCCESS'] = L('adminrequestmade');
        Flyspray::Redirect(CreateURL('details', Req::val('task_id')));
        break;

    // ##################
    // requesting task re-opening
    // ##################
    case 'requestreopen':
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
        $notify->Create(NOTIFY_PM_REQUEST, Post::val('task_id'), null, $notify->SpecificAddresses($pms));

        $_SESSION['SUCCESS'] = L('adminrequestmade');
        Flyspray::Redirect(CreateURL('details', Req::val('task_id')));
        break;

    // ##################
    // denying a PM request
    // ##################
    case 'denypmreq':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        $result = $db->Query("SELECT  task_id
                                FROM  {admin_requests}
                               WHERE  request_id = ?",
                              array(Req::val('req_id')));
        $req_details = $db->FetchRow($result);

        // Mark the PM request as 'resolved'
        $db->Query("UPDATE  {admin_requests}
                       SET  resolved_by = ?, time_resolved = ?, deny_reason = ?
                     WHERE  request_id = ?",
                    array($user->id, time(), Req::val('deny_reason'), Req::val('req_id')));

        $fs->logEvent($req_details['task_id'], 28, Req::val('deny_reason'));
        $notify->Create(NOTIFY_PM_DENY_REQUEST, $req_details['task_id'], Req::val('deny_reason'));

        $_SESSION['SUCCESS'] = L('pmreqdeniedmsg');
        Flyspray::Redirect(Req::val('prev_page'));
        break;

    // ##################
    // adding a dependency
    // ##################
    case 'details.newdep':
        if (!$user->can_edit_task($old_details)) {
            break;
        }
        
        if (!Post::val('dep_task_id')) {
            Flyspray::show_error(L('formnotcomplete'));
            break;
        }
                            
        // First check that the user hasn't tried to add this twice
        $sql1 = $db->Query("SELECT  COUNT(*) FROM {dependencies}
                             WHERE  task_id = ? AND dep_task_id = ?",
                array(Post::val('id'), Post::val('dep_task_id')));

        // or that they are trying to reverse-depend the same task, creating a mutual-block
        $sql2 = $db->Query("SELECT  COUNT(*) FROM {dependencies}
                             WHERE  task_id = ? AND dep_task_id = ?",
                array(Post::val('dep_task_id'), Post::val('id')));

        // Check that the dependency actually exists!
        $sql3 = $db->Query("SELECT COUNT(*) FROM {tasks} WHERE task_id = ?",
                array(Post::val('dep_task_id')));

        if ($db->fetchOne($sql1) || $db->fetchOne($sql2) || !$db->fetchOne($sql3)
                // Check that the user hasn't tried to add the same task as a dependency
                || Post::val('id') == Post::val('dep_task_id'))
        {
            Flyspray::show_error(L('dependaddfailed'));
            break;
        }

        $notify->Create(NOTIFY_DEP_ADDED, Post::val('id'), Post::val('dep_task_id'));
        $notify->Create(NOTIFY_REV_DEP, Post::val('dep_task_id'), Post::val('id'));

        // Log this event to the task history, both ways
        $fs->logEvent(Post::val('id'), 22, Post::val('dep_task_id'));
        $fs->logEvent(Post::val('dep_task_id'), 23, Post::val('id'));

        $db->Query('INSERT INTO  {dependencies} (task_id, dep_task_id)
                         VALUES  (?,?)',
                    array(Post::val('id'), Post::val('dep_task_id')));

        $_SESSION['SUCCESS'] = L('dependadded');

        Flyspray::Redirect(CreateURL('details', Req::val('id')));
        break;

    // ##################
    // removing a dependency
    // ##################
    case 'removedep':
        if (!$user->can_edit_task($old_details)) {
            break;
        }
        
        $result = $db->Query('SELECT  * FROM {dependencies}
                               WHERE  depend_id = ?',
                            array(Get::val('depend_id')));
        $dep_info = $db->FetchRow($result);

        $notify->Create(NOTIFY_DEP_REMOVED, $dep_info['task_id'], $dep_info['dep_task_id']);
        $notify->Create(NOTIFY_REV_DEP_REMOVED, $dep_info['dep_task_id'], $dep_info['task_id']);

        $fs->logEvent($dep_info['task_id'], 24, $dep_info['dep_task_id']);
        $fs->logEvent($dep_info['dep_task_id'], 25, $dep_info['task_id']);

        $db->Query('DELETE FROM {dependencies} WHERE depend_id = ?',
                   array(Get::val('depend_id')));

        $_SESSION['SUCCESS'] = L('depremovedmsg');
        Flyspray::Redirect(CreateURL('details', $dep_info['task_id']));
        break;

    // ##################
    // user requesting a password change
    // ##################
    case 'lostpw.sendmagic':
        // Check that the username exists
        $sql = $db->Query('SELECT * FROM {users} WHERE user_name = ?',
                          array(Post::val('user_name')));

        // If the username doesn't exist, throw an error
        if (!$db->CountRows($sql)) {
            Flyspray::show_error(L('usernotexist'));
            break;
        }

        $user_details = $db->FetchRow($sql);
        $magic_url    = md5(microtime());

        // Insert the random "magic url" into the user's profile
        $db->Query('UPDATE {users}
                       SET magic_url = ?
                     WHERE user_id = ?',
                array($magic_url, $user_details['user_id']));

        $notify->Create(NOTIFY_PW_CHANGE, null, array($baseurl, $magic_url), $notify->SpecificAddresses(array($user_details), true));

        $_SESSION['SUCCESS'] = L('magicurlsent');
        Flyspray::Redirect('./');
        break;

    // ##################
    // Change the user's password
    // ##################
    case 'lostpw.chpass':
        // Check that the user submitted both the fields, and they are the same
        if (!Post::val('pass1') || !Post::val('magic_url')) {
            Flyspray::show_error(L('erroronform'));
            break;
        }

        if (Post::val('pass1') != Post::val('pass2')) {
            Flyspray::show_error(L('passnomatch'));
            break;
        }

        $new_pass_hash = $fs->cryptPassword(Post::val('pass1'));
        $db->Query("UPDATE  {users} SET user_pass = ?, magic_url = ''
                     WHERE  magic_url = ?",
                array($new_pass_hash, Post::val('magic_url')));

        $_SESSION['SUCCESS'] = L('passchanged');
        Flyspray::Redirect('./');
        break;

    // ##################
    // making a task private
    // ##################
    case 'makeprivate':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        $db->Query("UPDATE  {tasks} SET mark_private = '1'
                 WHERE  task_id = ?", array(Req::num('id')));

        $fs->logEvent(Get::num('id'), 0, 1, 0, 'mark_private');

        $_SESSION['SUCCESS'] = L('taskmadeprivatemsg');
        Flyspray::Redirect(CreateURL('details', Req::num('id')));
        break;

    // ##################
    // making a task public
    // ##################
    case 'makepublic':
        if (!$user->perms['manage_project']) {
            break;
        }
        
        $db->Query("UPDATE  {tasks}
                       SET  mark_private = '0'
                     WHERE  task_id = ?", array(Req::num('id')));

        $fs->logEvent(Get::num('id'), 0, 0, 1, 'mark_private');

        $_SESSION['SUCCESS'] = L('taskmadepublicmsg');
        Flyspray::Redirect(CreateURL('details', Req::num('id')));
        break;
        
    // ##################
    // Adding a vote for a task
    // ##################
    case 'details.addvote':
        if ($be->add_vote($user, Req::num('id'))) {                         
            $_SESSION['SUCCESS'] = L('voterecorded');
            Flyspray::Redirect(CreateURL('details', Req::num('id')));
        } else {
            Flyspray::show_error(L('votefailed'));
            break;
        }
    default:
}

// this happens if the user has insufficient permissions.
// in general he won't  get into such a situation because such parts of the interface are hidden
if (!isset($_SESSION['SUCCESS']) && !isset($_SESSION['ERROR'])) {
    $_SESSION['ERROR'] = L('databasemodfailed');
    if (Req::has('task_id') || Req::has('id')) {
        Flyspray::Redirect(CreateURL('details', Req::num('task_id', Req::num('id'))));
    } else {
        Flyspray::Redirect($baseurl);
    }
}

if ($do != 'modify') {
    require_once BASEDIR . "/scripts/$do.php";
}

?>