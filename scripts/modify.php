<?php
/*
   This script performs all database modifications/
*/

$fs->get_language_pack('modify');

// Include the notifications class
include_once ( "$basedir/includes/notify.inc.php" );
$notify = new Notifications;

if ($lt = Post::val('list_type')) {
    $list_table_name  = '{list_'.addslashes($lt).'}';
    $list_column_name = addslashes($lt)."_name";
    $list_id = addslashes($lt)."_id";
}

function make_seed()
{
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}

function Post_to0($key) { return Post::val($key, 0); }

$now = date('U');

$old_details = $fs->GetTaskDetails(Req::val('task_id'));

// Adding a new task  {{{ 
if (Post::val('action') == 'newtask'
    && ($user->perms['open_new_tasks'] || $proj->prefs['anon_open'] == '1'))
{

    if (Post::val('item_summary') && Post::val('detailed_desc')) {
        $item_summary  = Post::val('item_summary');
        $detailed_desc = Post::val('detailed_desc');

        $param_names = array('task_type', 'item_status',
                'assigned_to', 'product_category', 'product_version',
                'closedby_version', 'operating_system', 'task_severity',
                'task_priority');

        $sql_values = array(Post::val('project_id'), $now, $now, $item_summary,
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

        $add_item = $db->Query("INSERT INTO  {tasks}
                                             ( attached_to_project, date_opened,
                                               last_edited_time, item_summary, detailed_desc,
                                               opened_by, percent_complete, $sql_params )
                                     VALUES  ($sql_placeholder)", $sql_values);


        // Now, let's get the task_id back, so that we can send a direct link
        // URL in the notification message
        $result = $db->Query("SELECT  task_id, item_summary, product_category
                                FROM  {tasks}
                               WHERE  item_summary = ?  AND detailed_desc = ?
                            ORDER BY  task_id DESC", array($item_summary, $detailed_desc), 1);
        $task_details = $db->FetchArray($result);

        // Log that the task was opened
        $fs->logEvent($task_details['task_id'], 1);

        // If the user uploaded one or more files
        if ($user->perms['create_attachments']) {
            $files_added = $be->UploadFiles($user->id, $task_details['task_id'], $_FILES);
        }

        $result = $db->Query("SELECT  *
                                FROM  {list_category}
                               WHERE  category_id = ?", array(Post::val('product_category')));
        $cat_details = $db->FetchArray($result);

        // We need to figure out who is the category owner for this task
        if (!empty($cat_details['category_owner'])) {
            $owner = $cat_details['category_owner'];
        }
        elseif (!empty($cat_details['parent_id'])) {
            $result = $db->Query("SELECT  category_owner
                                    FROM  {list_category}
                                   WHERE  category_id = ?", array($cat_details['parent_id']));
            $parent_cat_details = $db->FetchArray($result);

            // If there's a parent category owner, send to them
            if (!empty($parent_cat_details['category_owner'])) {
                $owner = $parent_cat_details['category_owner'];
            }
        }

        // Otherwise send it to the default category owner
        if (empty($owner)) {
            $owner = $proj->prefs['default_cat_owner'];
        }

        if (!empty($owner)) {
            // Category owners now get auto-added to the notification list for new tasks
            $insert = $db->Query("INSERT INTO  {notifications} (task_id, user_id)
                                       VALUES  (?, ?)", array($task_details['task_id'], $owner));

            $fs->logEvent($task_details['task_id'], 9, $owner);

            // Create the Notification
            $notify->Create('1', $task_details['task_id']);
        }

        // If the reporter wanted to be added to the notification list
        if (Post::val('notifyme') == '1' && $user->id != $owner) {
            $be->AddToNotifyList($user->id, array($task_details['task_id']));
        }

        // Status and redirect
        $_SESSION['SUCCESS'] = $modify_text['newtaskadded'];
        $fs->redirect($fs->CreateURL('details', $task_details['task_id']));

    }
    else {
        // If they didn't fill in both the summary and detailed description, show an error
        echo "<div class=\"redirectmessage\"><p>{$modify_text['summaryanddetails']}</p>";
        echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    };


} // }}}
// Modifying an existing task {{{
elseif (Post::val('action') == 'update' && ($user->perms['modify_all_tasks']
              || ($user->perms['modify_own_tasks'] && $user->id == $old_details['assigned_to'])))
{

    if (Post::val('item_summary') && Post::val('detailed_desc')) {

        // Check to see if this task has already been modified before we clicked "save"...
        // If so, we need to confirm that the we really wants to save our changes
        if (Post::val('edit_start_time') < $old_details['last_edited_time']) {
            echo $modify_text['alreadyedited'];
        ?>
        <br /><br />
        <span>
          <form name="form1" action="index.php" method="post">
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="task_id" value="<?php echo Post::val('task_id');?>" />
            <input type="hidden" name="edit_start_time" value="999999999999" />
            <input type="hidden" name="attached_to_project" value="<?php echo Post::val('attached_to_project');?>" />
            <input type="hidden" name="task_type" value="<?php echo Post::val('task_type');?>" />
            <input type="hidden" name="item_summary" value="<?php echo htmlspecialchars(Post::val('item_summary'),ENT_COMPAT,'utf-8');?>" />
            <input type="hidden" name="detailed_desc" value="<?php echo htmlspecialchars(Post::val('detailed_desc'),ENT_COMPAT,'utf-8');?>" />
            <input type="hidden" name="item_status" value="<?php echo Post::val('item_status');?>" />
            <input type="hidden" name="assigned_to" value="<?php echo Post::val('assigned_to');?>" />
            <input type="hidden" name="product_category" value="<?php echo Post::val('product_category');?>" />
            <input type="hidden" name="closedby_version" value="<?php echo Post::val('closedby_version');?>" />
            <input type="hidden" name="due_date" value="<?php echo Post::val('due_date');?>" />
            <input type="hidden" name="operating_system" value="<?php echo Post::val('operating_system');?>" />
            <input type="hidden" name="task_severity" value="<?php echo Post::val('task_severity');?>" />
            <input type="hidden" name="task_priority" value="<?php echo Post::val('task_priority');?>" />
            <input type="hidden" name="percent_complete" value="<?php echo Post::val('percent_complete');?>" />
            <input type="submit" class="adminbutton" value="<?php echo $modify_text['saveanyway']; ?>" />
          </form>
        </span>
        &nbsp;&nbsp;&nbsp;
        <span>
          <form action="index.php" method="get">
            <input type="hidden" name="do" value="details" />
            <input type="hidden" name="id" value="<?php echo Post::val('task_id');?>" />
            <input type="submit" class="adminbutton" value="<?php echo $modify_text['cancel'];?>" />
          </form>
        </span>
        <?php
        } else {

            $result = $db->Query("SELECT * FROM {tasks} WHERE task_id = ?", array(Post::val('task_id')));
            $old_details_history = $db->FetchRow($result);

            $item_summary  = Post::val('item_summary');
            $detailed_desc = Post::val('detailed_desc');

            if ($due_date = Post::val('due_date', 0)) {
                $due_date = strtotime("{Post::val('due_date')} +23 hours 59 minutes 59 seconds");
            }

            $add_item = $db->Query("UPDATE  {tasks}
                                       SET  attached_to_project = ?, task_type = ?, item_summary = ?,
                                            detailed_desc = ?, item_status = ?, assigned_to = ?,
                                            product_category = ?, closedby_version = ?, operating_system = ?,
                                            task_severity = ?, task_priority = ?, last_edited_by = ?,
                                            last_edited_time = ?, due_date = ?, percent_complete = ?
                                     WHERE  task_id = ?",
                            array(Post::val('attached_to_project'), Post::val('task_type'), $item_summary,
                                $detailed_desc, Post::val('item_status'), Post::val('assigned_to'),
                                Post::val('product_category'), Post::val('closedby_version', 0), Post::val('operating_system'), 
                                Post::val('task_severity'), Post::val('task_priority'), intval($user->id),
                                $now, $due_date, Post::val('percent_complete'), Post::val('task_id'))
                    );

            // Get the details of the task we just updated
            // To generate the changed-task message
            $new_details = $fs->GetTaskDetails(Post::val('task_id'));
            $result = $db->Query("SELECT * FROM {tasks} WHERE task_id = ?", array(Post::val('task_id')));
            $new_details_history = $db->FetchRow($result);

            // Now we compare old and new, mark the changed fields
            $field = array(
                    "{$modify_text['project']}"          =>  'project_title',
                    "{$modify_text['summary']}"          =>  'item_summary',
                    "{$modify_text['tasktype']}"         =>  'tasktype_name',
                    "{$modify_text['category']}"         =>  'category_name',
                    "{$modify_text['status']}"           =>  'status_name',
                    "{$modify_text['operatingsystem']}"  =>  'os_name',
                    "{$modify_text['severity']}"         =>  'severity_name',
                    "{$modify_text['priority']}"         =>  'priority_name',
                    "{$modify_text['reportedversion']}"  =>  'reported_version_name',
                    "{$modify_text['dueinversion']}"     =>  'due_in_version_name',
                    "{$modify_text['percentcomplete']}"  =>  'percent_complete',
                    "{$modify_text['details']}"          =>  'detailed_desc',
                    "{$modify_text['duedate']}"          =>  'due_date',
                    "assigned_to"                        =>  'assigned_to',
                );

            foreach ($field as $key => $val) {
                if ($old_details[$val] != $new_details[$val]) {
                    $send_me = 'YES';
                }
            }

            foreach ($old_details_history as $key => $val) {
                if ($key != 'last_edited_time' && $key != 'last_edited_by' && $key != 'assigned_to'
                        && !is_numeric($key) && $old_details_history[$key] != $new_details_history[$key])
                {
                    // Log the changed fields in the task history
                    $fs->logEvent(Post::val('task_id'), 0, $new_details_history[$key], $old_details_history[$key], $key);
                }
            }

            if ($send_me == 'YES') {
                $notify->Create('2', $new_details['task_id']);
            }

            if (Post::val('old_assigned') != Post::val('assigned_to')) {
                // Log to task history
                $fs->logEvent(Post::val('task_id'), 14, Post::val('assigned_to'), Post::val('old_assigned'));

                // Notify the new assignee what happened
                if ($new_details['assigned_to'] != $user->id) {
                    $to   = $notify->SpecificAddresses(array(Post::val('assigned_to')));
                    $msg  = $notify->GenerateMsg('14', Post::val('task_id'));
                    $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
                    $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);
                }
            }

            $_SESSION['SUCCESS'] = $modify_text['taskupdated'];
            $fs->redirect($fs->CreateURL('details', Post::val('task_id')));
        }
    } else {
        // If they didn't fill in both the summary and detailed description, show an error
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['summaryanddetails']}</em></p>";
        echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    }
} // }}}
// closing a task {{{
elseif (Post::val('action') == 'close'
        && ( $user->perms['close_own_tasks'] && $old_details['assigned_to'] == $user->id
                || $user->perms['close_other_tasks'] ) )
{

    if (Post::val('resolution_reason')) {
        $db->Query("UPDATE  {tasks}
                SET  date_closed = ?, closed_by = ?, closure_comment = ?,
                is_closed = '1', resolution_reason = ?
                WHERE  task_id = ?",
                array($now, intval($user->id), Post::val('closure_comment', 0),
                    Post::val('resolution_reason'),
                    Post::val('task_id')));

        if (Post::val('mark100') == '1') {
            $db->Query("UPDATE {tasks} SET percent_complete = '100' WHERE task_id = ?",
                    array(Post::val('task_id')));

            $fs->logEvent(Post::val('task_id'), '0', '100', $old_details['percent_complete'], 'percent_complete');
        }

        // Get the resolution name for the notifications
        $result = $db->Query("SELECT resolution_name FROM {list_resolution} WHERE resolution_id = ?", array(Post::val('resolution_reason')));
        $get_res = $db->FetchArray($result);

        // Get the item summary for the notifications
        $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array(Post::val('task_id')));
        list($item_summary) = $db->FetchArray($result);

        // Create notification
        $notify->Create('3', Post::val('task_id'));

        // Log this to the task's history
        $fs->logEvent(Post::val('task_id'), 2, Post::val('resolution_reason'), Post::val('closure_comment'));

        // If there's an admin request related to this, close it
        if ($fs->AdminRequestCheck(1, Post::val('task_id')) == '1') {
            $db->Query("UPDATE  {admin_requests}
                    SET  resolved_by = ?, time_resolved = ?
                    WHERE  task_id = ? AND request_type = ?",
                    array($user->id, date('U'), Post::val('task_id'), 1));
        }

        $_SESSION['SUCCESS'] = $modify_text['taskclosed'];
        $fs->redirect($fs->CreateURL('details', Post::val('task_id')));

    } else {
        // If the user didn't select a closure reason
        $_SESSION['ERROR'] = $modify_text['noclosereason'];
        $fs->redirect($fs->CreateURL('details', Post::val('task_id')));
    }
} // }}}
// re-opening an task {{{
elseif ( Get::val('action') == 'reopen'
        && ( $user->perms['close_own_tasks'] && $old_details['assigned_to'] == $user->id
                || $user->perms['close_other_tasks']) )
{
    $db->Query("UPDATE  {tasks}
                   SET  resolution_reason = '0', closure_comment = '0',
                        last_edited_time = ?, last_edited_by = ?, is_closed = '0'
                 WHERE  task_id = ?",
                array($now, $user->id, Get::val('task_id')));

    $notify->Create('4', Get::val('task_id'));

    if ($fs->AdminRequestCheck(2, Get::val('task_id')) == '1') {
        // If there's an admin request related to this, close it
        $db->Query("UPDATE  {admin_requests}
                       SET  resolved_by = ?, time_resolved = ?
                     WHERE  task_id = ? AND request_type = ?",
                  array($user->id, date('U'), Get::val('task_id'), 2));
    }

    $fs->logEvent(Get::val('task_id'), 13);

    $_SESSION['SUCCESS'] = $modify_text['taskreopened'];
    $fs->redirect($fs->CreateURL('details', Get::val('task_id')));
} // }}}
// adding a comment {{{
elseif (Post::val('action') == 'addcomment' && $user->perms['add_comments'])
{

    if (Post::val('comment_text')) {
        $comment = Post::val('comment_text');

        $db->Query("INSERT INTO  {comments}
                                 (task_id, date_added, user_id, comment_text)
                         VALUES  ( ?, ?, ?, ? )",
                array(Post::val('task_id'), $now, intval($user->id), $comment));

        $result = $db->Query("SELECT  comment_id FROM {comments}
                               WHERE  task_id = ?
                            ORDER BY  comment_id DESC",
                array(Post::val('task_id')), 1);
        $comment = $db->FetchRow($result);

        $fs->logEvent(Post::val('task_id'), 4, $comment['comment_id']);

        if (Post::val('notifyme') == '1') {
            // If the user wanted to watch this task for changes
            $be->AddToNotifyList($user->id, array(Post::val('task_id')));
        }

        if ($user->perms['create_attachments']) {
            // If the user uploaded one or more files
            $files_added = $be->UploadFiles($user->id,
                    $old_details['task_id'], $_FILES, $comment['comment_id']);
        }

        // Send the notification
        if ($files_added) {
            $notify->Create('7', Post::val('task_id'), 'files');
        } else {
            $notify->Create('7', Post::val('task_id'));
        }

        $_SESSION['SUCCESS'] = $modify_text['commentadded'];
        $fs->redirect($fs->CreateURL('details', Post::val('task_id')));
    } else {
        // If they pressed submit without actually typing anything
        $_SESSION['ERROR'] = $modify_text['nocommententered'];
        $fs->redirect($fs->CreateURL('details', Post::val('task_id')));
    }
} // }}}
// sending a new user a confirmation code {{{
elseif (Post::val('action') == 'sendcode')
{ 
    if (Post::val('user_name') && Post::val('real_name')
            && ((Post::val('email_address') && Post::val('notify_type') == '1')
                OR (Post::val('jabber_id') && Post::val('notify_type') == '2')))
    {
        // Check to see if the username is available
        $check_username = $db->Query("SELECT * FROM {users} WHERE user_name = ?", array(Post::val('user_name')));
        if ($db->CountRows($check_username)) {
            echo "<p class=\"admin\">{$register_text['usernametaken']}<br>";
            echo "<a href=\"javascript:history.back();\">{$register_text['goback']}</a></p>";
        } else {
            // Delete registration codes older than 24 hours
            $now = date('U');
            $yesterday = $now - '86400';
            $remove = $db->Query("DELETE FROM {registrations} WHERE reg_time < ?", array($yesterday));

            // Generate a random bunch of numbers for the confirmation code
            mt_srand(make_seed());
            $randval = mt_rand();

            // Convert those numbers to a seemingly random string using crypt
            $confirm_code = crypt($randval, $conf['general']['cookiesalt']);

            // Generate a looonnnnggg random string to send as an URL to complete this registration
            $magic_url = md5(microtime());

            // Insert everything into the database
            $save_code = $db->Query("INSERT INTO  {registrations}
                                                  ( reg_time, confirm_code, user_name, real_name,
                                                    email_address, jabber_id, notify_type,
                                                    magic_url )
                                          VALUES  (?,?,?,?,?,?,?,?)",
                                array($now, $confirm_code, Post::val('user_name'), Post::val('real_name'),
                                      Post::val('email_address'), Post::val('jabber_id'), Post::val('notify_type'),
                                      $magic_url));

            $subject = $modify_text['noticefrom'] . ' Flyspray';

            $message = <<<EOF
{$register_text['noticefrom']} {$fs->prefs['project_title']}

{$modify_text['addressused']}

{$conf['general']['baseurl']}index.php?do=register&magic=$magic_url

{$modify_text['confirmcodeis']}

{$confirm_code}
EOF;

            // Check how they want to receive their code
            if (Post::val('notify_type') == '1') {
                $notify->SendEmail(Post::val('email_address'), $subject, $message);
            }
            elseif (Post::val('notify_type') == '2') {
                $notify->StoreJabber(array(Post::val('jabber_id')), $subject, htmlspecialchars($message),ENT_COMPAT,'utf-8');
            }

            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['codesent']}</em></p></div>";
        }
    } else {
        // If the form wasn't filled out correctly, show an error
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['erroronform']}</em></p>";
        echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";
    }
} // }}}
// new user self-registration with a confirmation code {{{
elseif (Post::val('action') == "registeruser" && $fs->prefs['anon_reg'] == '1')
{

    if (Post::val('user_pass') && Post::val('user_pass2') 
            && Post::val('confirmation_code'))
    {
       if (Post::val('user_pass') == Post::val('user_pass2')) {

           // Check that the user entered the right confirmation code
           $code_check = $db->Query("SELECT * FROM {registrations} WHERE magic_url = ?", array(Post::val('magic_url')));
           $reg_details = $db->FetchArray($code_check);

           if ($reg_details['confirm_code'] == Post::val('confirmation_code')) {
               // Encrypt their password
               $pass_hash = $fs->cryptPassword(Post::val('user_pass'));

               // Add the user to the database
               $add_user = $db->Query("INSERT INTO  {users}
                                                    ( user_name, user_pass,
                                                      real_name, jabber_id,
                                                      email_address, notify_type,
                                                      account_enabled, tasks_perpage)
                                            VALUES  (?, ?, ?, ?, ?, ?, ?, ?)",
                                        array($reg_details['user_name'], $pass_hash,
                                            $reg_details['real_name'], $reg_details['jabber_id'],
                                            $reg_details['email_address'], $reg_details['notify_type'],
                                            '1', '25'));

               // Get this user's id for the record
               $result = $db->Query("SELECT * FROM {users} WHERE user_name = ?", array($reg_details['user_name']));
               $user_details = $db->FetchArray($result);

               // Now, create a new record in the users_in_groups table
               $set_global_group = $db->Query("INSERT INTO  {users_in_groups} (user_id, group_id)
                                                    VALUES  (?, ?)", array($user_details['user_id'], $fs->prefs['anon_group']));

               // Let the user know what just happened
               echo "<div class=\"redirectmessage\"><p><em>{$modify_text['accountcreated']}</em></p>";
               echo "<p>{$modify_text['loginbelow']}</p>";
               echo "<p>{$modify_text['newuserwarning']}</p></div>";
           } else {
               // If they didn't enter the right confirmation code
               echo "<div class=\"redirectmessage\"><p><em>{$modify_text['confirmwrong']}</em></p>";
               echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
           }
       } else {
           // If passwords didn't match
           echo "<div class=\"redirectmessage\"><p><em>{$modify_text['nomatchpass']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
       }
    } else {
        // If they didn't fill in all the fields
        echo "<div class=\"redirectessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    }
} // }}}
// user self-registration without confirmation code (Or, by an admin) {{{
elseif (Post::val('action') == "newuser"
           && ($user->perms['is_admin'] OR ($fs->prefs['anon_reg'] == '1' && $fs->prefs['spam_proof'] != '1')))
{

    if ( Post::val('user_name') && Post::val('user_pass') && Post::val('user_pass2')
            && Post::val('real_name') && (Post::val('email_address') OR Post::val('jabber_id')))
    {

        // Check to see if the username is available
        $check_username = $db->Query("SELECT * FROM {users} WHERE user_name = ?", array(Post::val('user_name')));
        if ($db->CountRows($check_username)) {
            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['usernametaken']}</em></p>";
            echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
        } else {

            if ((Post::val('user_pass') == Post::val('user_pass2')) && Post::val('user_pass')) {

                $pass_hash = $fs->cryptPassword(Post::val('user_pass'));

                if ($user->perms['is_admin']) {
                    $group_in = Post::val('group_in');
                } else {
                    $group_in = $fs->prefs['anon_group'];
                }

                $add_user = $db->Query("INSERT INTO  {users}
                                                     ( user_name, user_pass,
                                                       real_name, jabber_id, 
                                                       email_address, notify_type,
                                                       account_enabled, tasks_perpage)
                                             VALUES  ( ?, ?, ?, ?, ?, ?, ?, ?)",
                                    array(Post::val('user_name'), $pass_hash,
                                          Post::val('real_name'), Post::val('jabber_id'),
                                          Post::val('email_address'), Post::val('notify_type'),
                                          '1', '25'));

                // Get this user's id for the record
                $result = $db->Query("SELECT * FROM {users} WHERE user_name = ?", array(Post::val('user_name')));
                $user_details = $db->FetchArray($result);

                // Now, create a new record in the users_in_groups table
                $set_global_group = $db->Query("INSERT INTO  {users_in_groups} (user_id, group_id)
                                                     VALUES  ( ?, ?)",
                                                    array($user_details['user_id'], $group_in));

                if (!$user->perms['is_admin']) {
                    echo "<p>{$modify_text['loginbelow']}</p>";
                    echo "<p>{$modify_text['newuserwarning']}</p></div>";
                } else {
                    $_SESSION['SUCCESS'] = $modify_text['newusercreated'];
                    $fs->redirect($fs->CreateURL('admin', 'groups'));
                }
            } else {
                echo "<div class=\"redirectmessage\"><p><em>{$modify_text['nomatchpass']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
            }
        }
    } else {
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    }
} // }}}
// adding a new group {{{
elseif (Post::val('action') == "newgroup"
          && ((Post::val('belongs_to_project') && $user->perms['manage_project'])
          || $user->perms['is_admin']) )
{

    if (Post::val('group_name') && Post::val('group_desc')) {
        // Check to see if the group name is available
        $check_groupname = $db->Query("SELECT  *
                                         FROM  {groups}
                                        WHERE  group_name = ?  AND belongs_to_project = ?",
                                        array(Post::val('group_name'), Post::val('project')));

        if ($db->CountRows($check_groupname)) {
            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupnametaken']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
        } else {
            $cols = array('project', 'group_name', 'group_desc', 'manage_project',
                    'view_tasks', 'open_new_tasks', 'modify_own_tasks',
                    'modify_all_tasks', 'view_comments', 'add_comments',
                    'edit_comments', 'delete_comments', 'create_attachments',
                    'delete_attachments', 'view_history', 'close_own_tasks',
                    'close_other_tasks', 'assign_to_self',
                    'assign_others_to_self', 'view_reports', 'group_open');
            // XXX kludge : project in POST is belongs_to_project in the DB
            $db->Query("INSERT INTO  {groups} ( belongs_to_".join(',', $cols).")
                             VALUES  (".join(',', array_fill(0, count($cols), '?')).")",
                                 array_map('Post_to0', $cols));

            $_SESSION['SUCCESS'] = $modify_text['newgroupadded'];
            if (Post::val('project')) {
                $fs->redirect($fs->CreateURL('pm', 'groups', Post::val('project')));
            } else {
                $fs->redirect($fs->CreateURL('admin', 'groups'));
            }
        }
    } else {
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    }
} // }}}
// Update the global application preferences {{{
elseif (Post::val('action') == "globaloptions" && $user->perms['is_admin'])
{
    $settings = array('jabber_server', 'jabber_port', 'jabber_username',
            'jabber_password', 'anon_group', 'user_notify', 'admin_email',
            'lang_code', 'spam_proof', 'default_project', 'dateformat',
            'dateformat_extended', 'anon_reg', 'global_theme', 'smtp_server',
            'smtp_user', 'smtp_pass', 'funky_urls', 'reminder_daemon','cache_feeds');
    foreach ($settings as $setting) {
        $update = $db->Query("UPDATE {prefs} SET pref_value = ? WHERE pref_name = '$setting'",
                array(Post::val($setting)));
    }

    // Process the list of groups into a format we can store
    $assigned_groups = join(' ', array_keys(Post::val('assigned_groups', array())));
    $update = $db->Query("UPDATE {prefs} SET pref_value = ? WHERE pref_name = 'assigned_groups'", array($assigned_groups));

    $update = $db->Query("UPDATE {prefs} SET pref_value = ? WHERE pref_name = 'visible_columns'",
            array(trim(Post::val('visible_columns'))));

    $_SESSION['SUCCESS'] = $modify_text['optionssaved'];
    $fs->redirect($fs->CreateURL('admin','prefs'));
} // }}}
// adding a new project {{{
elseif (Post::val('action') == "newproject" && $user->perms['is_admin']) {

    if (Post::val('project_title') != '') {

        $insert = $db->Query("INSERT INTO  {projects}
                                           ( project_title, theme_style, show_logo,
                                             intro_message, others_view, anon_open,
                                             project_is_active, visible_columns)
                                   VALUES  (?, ?, ?, ?, ?, ?, ?, ?)",
                            array(Post::val('project_title'), Post::val('theme_style'), Post::val('show_logo', 0),
                                Post::val('intro_message'), Post::val('others_view', 0), Post::val('anon_open', 0),
                                '1', 'id tasktype severity summary status dueversion progress'));

        $result = $db->Query("SELECT project_id FROM {projects} ORDER BY project_id DESC", false, 1);
        $newproject = $db->FetchArray($result);

        $cols = array( 'manage_project', 'view_tasks', 'open_new_tasks',
                'modify_own_tasks', 'modify_all_tasks', 'view_comments',
                'add_comments', 'edit_comments', 'delete_comments',
                'create_attachments', 'delete_attachments', 'view_history',
                'close_own_tasks', 'close_other_tasks', 'assign_to_self',
                'assign_others_to_self', 'view_reports', 'group_open');
        $args = array_fill(0, count($cols), '1');
        array_unshift($args, 'Project Managers',
                'Permission to do anything related to this project.',
                intval($newproject['project_id']));
            
        $add_group = $db->Query("INSERT INTO  {groups}
                                              ( group_name, group_desc, belongs_to_project,
                                                ".join(',', $cols).")
                                      VALUES  ( ?, ?, ?".join(',', array_fill(0, count($cols), '?')).")",
                                      $args);

        $insert = $db->Query("INSERT INTO  {list_category}
                                           ( project_id, category_name, list_position,
                                             show_in_list, category_owner )
                                   VALUES  ( ?, ?, ?, ?, ?)",
                                   array($newproject['project_id'], 'Backend / Core', '1', '1', '0'));

        $insert = $db->Query("INSERT INTO  {list_os}
                                           ( project_id, os_name, list_position, show_in_list )
                                   VALUES  (?,?,?,?)",
                                   array($newproject['project_id'], 'All', '1', '1'));

        $insert = $db->Query("INSERT INTO  {list_version}
                                           ( project_id, version_name, list_position,
                                             show_in_list, version_tense )
                                   VALUES  (?, ?, ?, ?, ?)",
                                   array($newproject['project_id'], '1.0', '1', '1', '2'));

        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['projectcreated']}";
        echo "<br><br><a href=\"" . $fs->CreateURL('pm', 'prefs', $newproject['project_id']) . "\">{$modify_text['customiseproject']}</a></em></p></div>";
    } else {
        echo "<div class=\"errormessage\"><p><em>{$modify_text['emptytitle']}</em></p></div>";
    }
} // }}}
// updating project preferences {{{
elseif (Post::val('action') == 'updateproject' && $user->perms['manage_project']) {

    if (Post::val('project_title')) {
        $cols = array( 'project_title', 'theme_style', 'show_logo',
                'default_cat_owner', 'intro_message',
                'project_is_active', 'others_view', 'anon_open', 'notify_email',
                'notify_email_when', 'notify_jabber', 'notify_jabber_when', 'feed_description', 'feed_img_url');
        $args = array_map('Post_to0', $cols);
        $args[] = Post::val('project_id', 0);

        $update = $db->Query("UPDATE  {projects}
                                 SET  ".join('=?, ', $cols)."=?
                               WHERE  project_id = ?", $args);

        $update = $db->Query("UPDATE {projects} SET visible_columns = ? WHERE project_id = ?",
                array(trim(Post::val('visible_columns')), Post::val('project_id')));


        $_SESSION['SUCCESS'] = $modify_text['projectupdated'];
        $fs->redirect($fs->CreateURL('pm', 'prefs', $proj->id));
    } else {
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['emptytitle']}</em></p></div>";
    }

} // }}}
// uploading an attachment {{{
elseif (Post::val('action') == "addattachment" && $user->perms['create_attachments'])
{
    mt_srand(make_seed());
    $randval = mt_rand();
    $file_name = Post::val('task_id')."_$randval";

    if ($_FILES['userfile']['name']) {
        @move_uploaded_file($_FILES['userfile']['tmp_name'], "attachments/$file_name");
        @chmod("attachments/$file_name", 0644);

        if (file_exists("attachments/$file_name")) {
            // Only add the listing to the database if the file was actually uploaded successfully
            $file_desc = Post::val('file_desc');
            $add_to_db = $db->Query("INSERT INTO  {attachments}
                                                  ( task_id, orig_name,
                                                    file_name, file_desc,
                                                    file_type, file_size,
                                                    added_by, date_added)
                                          VALUES  ( ?, ?, ?, ?, ?, ?, ?, ?)",
                                          array(Post::val('task_id'), $_FILES['userfile']['name'],
                                              $file_name, $file_desc,
                                              $_FILES['userfile']['type'], $_FILES['userfile']['size'],
                                              inval($user->id), $now));

            $notify->Create('8', Post::val('task_id'));

            $result = $db->Query("SELECT attachment_id FROM {attachments} WHERE task_id = ? ORDER BY attachment_id DESC", array(Post::val('task_id')), 1);
            $row = $db->FetchRow($result);
            $fs->logEvent(Post::val('task_id'), 7, $row['attachment_id']);

            // Success message!
            $_SESSION['SUCCESS'] = $modify_text['fileuploaded'];
            $fs->redirect($fs->CreateURL('details', Post::val('task_id')));
        } else {
            // If the file didn't actually get saved, better show an error to that effect
            $_SESSION['ERROR'] = $modify_text['fileerror'];
            $fs->redirect($fs->CreateURL('details', Post::val('task_id')));
        }
    } else {
        $_SESSION['ERROR'] = $modify_text['selectfileerror'];
        $fs->redirect($fs->CreateURL('details', Post::val('task_id')));
    }
} // }}}
// Start of modifying user details {{{
elseif (Post::val('action') == "edituser"
          && ($user->perms['is_admin'] || $user->id == Post::val('user_id')))
{
    if (Post::val('real_name') && (Post::val('email_address') OR Post::val('jabber_id'))) {

        if (Post::val('changepass') || Post::val('confirmpass')) {
            if (Post::val('changepass') == Post::val('confirmpass')) {
                $new_pass = Post::val('changepass');
                $new_pass_hash = $fs->cryptPassword($new_pass);
                $update_pass = $db->Query("UPDATE {users} SET user_pass = '$new_pass_hash' WHERE user_id = ?", array(Post::val('user_id')));

                // If the user is changing their password, better update their cookie hash
                if ($user->id == Post::val('user_id')) {
                    $fs->setcookie('flyspray_passhash', crypt($new_pass_hash, $conf['general']['cookiesalt']), time()+60*60*24*30);
                }
            } else {
                echo "<div class=\"redirectmessage\"><p><em>{$modify_text['passnomatch']}</em></p>";
                echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
                $password_problem = true;
            }
        }

        if (empty($password_problem)) {
            $update = $db->Query("UPDATE  {users}
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
                $update = $db->Query("UPDATE {users} SET account_enabled = ?  WHERE user_id = ?",
                        array(Post::val('account_enabled'), Post::val('user_id')));

                $update = $db->Query("UPDATE {users_in_groups} SET group_id = ?
                                       WHERE record_id = ?",
                        array(Post::val('group_in'), Post::val('record_id')));
            }

            $_SESSION['SUCCESS'] = $modify_text['userupdated'];
            $fs->redirect(Post::val('prev_page'));
        }
    } else {
        $_SESSION['ERROR'] = $modify_text['realandnotify'];
        $fs->redirect(Post::val('prev_page'));
    }
} // }}}
// updating a group definition {{{
elseif (Post::val('action') == "editgroup"
          && ($user->perms['is_admin'] || $user->perms['manage_project']))
{
    if (Post::val('group_name') && Post::val('group_desc')) {

        $cols = array( 'group_name', 'group_desc', 'manage_project',
                'view_tasks', 'open_new_tasks', 'modify_own_tasks',
                'modify_all_tasks', 'view_comments', 'add_comments',
                'edit_comments', 'delete_comments', 'view_attachments',
                'create_attachments', 'delete_attachments', 'view_history',
                'close_own_tasks', 'close_other_tasks', 'assign_to_self',
                'assign_others_to_self', 'view_reports', 'group_open');
        $args = array_map('Post_to0', $cols);
        $args[] = Post::val('group_id');

        $update = $db->Query("UPDATE  {groups}
                                 SET  ".join('=?,', $cols)."=?
                               WHERE  group_id = ?", $args);

        // Get the group definition that this group belongs to
        $result = $db->Query("SELECT * FROM {groups} WHERE group_id = ?", array(Post::val('group_id')));
        $group_details = $db->FetchArray($result);

        $_SESSION['SUCCESS'] = $modify_text['groupupdated'];
        $fs->redirect(Post::val('prev_page'));

    } else {
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupanddesc']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    }
} // }}}
// updating a list {{{
elseif (Post::val('action') == "update_list"
          && ($user->perms['is_admin'] || $user->perms['manage_project']))
{

    $listname     = Post::val('list_name');
    $listposition = Post::val('list_position');
    $listshow     = Post::val('show_in_list');
    $listdelete   = Post::val('delete');
    $listid       = Post::val('id');

    $redirectmessage = $modify_text['listupdated'];

    for($i = 0; $i < count($listname); $i++) {
        if (is_numeric($listposition[$i])) {
            $update = $db->Query("UPDATE  $list_table_name
                                     SET  $list_column_name = ?, list_position = ?, show_in_list = ?
                                   WHERE  $list_id = '{$listid[$i]}'",
                    array($listname[$i], $listposition[$i], intval($listshow[$i])));
        } else {
            $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
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
elseif (Post::val('action') == "add_to_list" && $user->perms['manage_project'])
{
    if (Post::val('list_name') && Post::val('list_position')) {
        $db->Query("INSERT INTO  $list_table_name
                                 (project_id, $list_column_name, list_position, show_in_list)
                         VALUES  (?, ?, ?, ?)",
                array(Post::val('project_id', '0'), Post::val('list_name'), Post::val('list_position'), '1'));

        // Redirect
        $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
        $fs->redirect(Post::val('prev_page'));
    } else {
        $_SESSION['ERROR'] = $modify_text['fillallfields'];
        $fs->redirect(Post::val('prev_page'));
    }
} // }}}
// updating the version list {{{
elseif (Post::val('action') == "update_version_list"
        && ($user->perms['is_admin'] || $user->perms['manage_project']))
{
    $listname     = Post::val('list_name');
    $listposition = Post::val('list_position');
    $listshow     = Post::val('show_in_list');
    $listtense    = Post::val('version_tense');
    $listdelete   = Post::val('delete');
    $listid       = Post::val('id');

    $redirectmessage = $modify_text['listupdated'];

    for($i = 0; $i < count($listname); $i++) {
        if (is_numeric($listposition[$i])) {

            $update = $db->Query("UPDATE  $list_table_name
                                     SET  $list_column_name = ?, list_position = ?,
                                          show_in_list = ?, version_tense = ?
                                   WHERE  $list_id = '{$listid[$i]}'",
                    array($listname[$i], $listposition[$i],
                        intval($listshow[$i]), intval($listtense[$i])));
        } else {
            $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
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
elseif (Post::val('action') == "add_to_version_list" && $user->perms['manage_project'])
{
   if (Post::val('list_name') && Post::val('list_position')) {
       $update = $db->Query("INSERT INTO  $list_table_name
                                          (project_id, $list_column_name, list_position, show_in_list, version_tense)
                                  VALUES  (?, ?, ?, ?, ?)",
                           array(Post::val('project_id'), Post::val('list_name'), Post::val('list_position'), '1', Post::val('version_tense')));

       $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
       $fs->redirect(Post::val('prev_page'));
   } else {
       $_SESSION['ERROR'] = $modify_text['fillallfields'];
       $fs->redirect(Post::val('prev_page'));
   }
} // }}}
// updating the category list {{{
elseif (Post::val('action') == "update_category"
          && ($user->perms['is_admin'] || $user->perms['manage_project']))
{
    $listname     = Post::val('list_name');
    $listposition = Post::val('list_position');
    $listshow     = Post::val('show_in_list');
    $listid       = Post::val('id');
    $listowner    = Post::val('category_owner');
    $listdelete   = Post::val('delete');

    $redirectmessage = $modify_text['listupdated'];

    for ($i = 0; $i < count($listname); $i++) {
        if (is_numeric($listposition[$i])) {
            $update = $db->Query("UPDATE  {list_category}
                                     SET  category_name = ?, list_position = ?,
                                          show_in_list = ?, category_owner = ?
                                   WHERE  category_id = ?",
                              array($listname[$i], $listposition[$i],
                                  intval($listshow[$i]), intval($listowner[$i]), $listid[$i]));
        } else {
            $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
        }
    }

    if (is_array($listdelete)) {
        $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
        $db->Query("DELETE FROM {list_category} WHERE $deleteids");
    }

    $_SESSION['SUCCESS'] = $redirectmessage;
    $fs->redirect(Post::val('prev_page'));
} // }}}
// Start of adding a category list item {{{ TODO finish the review
elseif (Post::val('action') == "add_category"
          && ($user->perms['is_admin'] || $user->perms['manage_project']))
{
  if (Post::val('list_name') && Post::val('list_position')) {
      $update = $db->Query("INSERT INTO {list_category}
                                (project_id, category_name, list_position,
                                show_in_list, category_owner, parent_id)
                                VALUES (?, ?, ?, ?, ?, ?)",
                        array(
			Post::val('project_id', 0),
			Post::val('list_name'),
                        Post::val('list_position'),
                        '1',
                        Post::val('category_owner', 0),
                        Post::val('parent_id', 0)));

      $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
      $fs->redirect(Post::val('prev_page'));

} else {
    $_SESSION['ERROR'] = $modify_text['fillallfields'];
    $fs->redirect(Post::val('prev_page'));
};
// End of adding a category list item

//////////////////////////////////////////
// Start of adding a related task entry //
//////////////////////////////////////////

} elseif (Post::val('action') == 'add_related'
          && ($user->perms['modify_all_tasks']
               || ($user->perms['modify_own_tasks'] && $old_details['assigned_to'] == $user->id))) {

  if (is_numeric(Post::val('related_task'))) {
    $check = $db->Query("SELECT * FROM {related}
        WHERE this_task = ?
        AND related_task = ?",
        array(Post::val('this_task'), Post::val('related_task')));
    $check2 = $db->Query("SELECT attached_to_project FROM {tasks}
        WHERE task_id = ?",
        array(Post::val('related_task')));

    if ($db->CountRows($check) > 0)
    {
        $_SESSION['ERROR'] = $modify_text['relatederror'];
        $fs->redirect($fs->CreateURL('details', Post::val('this_task').'#related'));

    } elseif (!$db->CountRows($check2))
    {
        $_SESSION['ERROR'] = $modify_text['relatedinvalid'];
        $fs->redirect($fs->CreateURL('details', Post::val('this_task').'#related'));
    } else
    {
        list($relatedproject) = $db->FetchRow($check2);
        if ($proj->id == $relatedproject || Post::has('allprojects')) {
            $insert = $db->Query("INSERT INTO {related} (this_task, related_task) VALUES(?,?)", array(Post::val('this_task'), Post::val('related_task')));

            $fs->logEvent(Post::val('this_task'), 11, Post::val('related_task'));
            $fs->logEvent(Post::val('related_task'), 15, Post::val('this_task'));

            $notify->Create('9', Post::val('this_task'));


            $_SESSION['SUCCESS'] = $modify_text['relatedadded'];
            $fs->redirect($fs->CreateURL('details', Post::val('this_task').'#related'));

        } else {
            ?>
            <div class="redirectmessage">
                <p><em><?php echo $modify_text['relatedproject'];?></em></p>
                <form action="index.php" method="post">
                    <input type="hidden" name="do" value="modify">
                    <input type="hidden" name="action" value="add_related">
                    <input type="hidden" name="this_task" value="<?php echo Post::val('this_task');?>">
                    <input type="hidden" name="related_task" value="<?php echo Post::val('related_task');?>">
                    <input type="hidden" name="allprojects" value="1">
                    <input class="adminbutton" type="submit" value="<?php echo $modify_text['addanyway'];?>">
                </form>
                <form action="index.php" method="get">
                    <input type="hidden" name="do" value="details">
                    <input type="hidden" name="id" value="<?php echo Post::val('this_task');?>">
                    <input type="hidden" name="area" value="related">
                    <input class="adminbutton" type="submit" value="<?php echo $modify_text['cancel'];?>">
                </form>
            </div>
            <?php
        };
    };
  } else {
    $_SESSION['ERROR'] = $modify_text['relatedinvalid'];
    $fs->redirect($fs->CreateURL('details', Post::val('this_task').'#related'));
  };

// End of adding a related task entry

///////////////////////////////////
// Removing a related task entry //
///////////////////////////////////

} elseif (Post::val('action') == "remove_related"
          && ($user->perms['modify_all_jobs'] || $user->perms['modify_own_tasks'])) { // FIX THIS PERMISSION!!

  $remove = $db->Query("DELETE FROM {related} WHERE related_id = ?", array(Post::val('related_id')));

  $fs->logEvent(Post::val('id'), 12, Post::val('related_task'));
  $fs->logEvent(Post::val('related_task'), 16, Post::val('id'));

  $_SESSION['SUCCESS'] = $modify_text['relatedremoved'];
  $fs->redirect($fs->CreateURL('details', Post::val('id')));

// End of removing a related task entry

/////////////////////////////////////////////////////
// Start of adding a user to the notification list //
/////////////////////////////////////////////////////

} elseif ( Req::val('action') == "add_notification" )
{

   if ( Req::val('prev_page') )
   {
      $ids = Req::val('ids');
      $tasks = array();
      $redirect_url = Req::val('prev_page');

      if ( is_array($ids) && !empty($ids) )
      {
         foreach ( $ids AS $key => $val )
            array_push($tasks, $key);

         $be->AddToNotifyList($user->id, $tasks);
      } else
      {
         $be->AddToNotifyList(Req::val('user_id'), array(Req::val('ids')));
      }

   } else
   {
      $be->AddToNotifyList(Req::val('user_id'), array(Req::val('ids')));
      $redirect_url = $fs->CreateURL('details', Req::val('ids'));
   }

   $_SESSION['SUCCESS'] = $modify_text['notifyadded'];
   $fs->redirect($redirect_url.'#notify');

// End of adding a user to the notification list

////////////////////////////////////////////
// Start of removing a notification entry //
////////////////////////////////////////////

} elseif (Req::val('action') == "remove_notification")
{
   if ( Req::val('prev_page') )
   {
      $ids = Req::val('ids');
      $tasks = array();
      $redirect_url = Req::val('prev_page');

      if (!empty($ids))
      {
         foreach ($ids AS $key => $val)
            array_push($tasks, $key);

         $be->RemoveFromNotifyList($user->id, $tasks);
      }

   } else
   {
      $be->RemoveFromNotifyList(Req::val('user_id'), array(Req::val('ids')));
      $redirect_url = $fs->CreateURL('details', Req::val('ids'));
   }

   $_SESSION['SUCCESS'] = $modify_text['notifyremoved'];
   $fs->redirect($redirect_url.'#notify');

// End of removing a notification entry

////////////////////////////////
// Start of editing a comment //
////////////////////////////////

} elseif (Post::val('action') == "editcomment"
          && $user->perms['edit_comments'])
{
   $update = $db->Query("UPDATE {comments}
                         SET comment_text = ?  WHERE comment_id = ?",
                         array(Post::val('comment_text'), Post::val('comment_id')));

   $fs->logEvent(Post::val('task_id'), 5, Post::val('comment_text'), Post::val('previous_text'), Post::val('comment_id'));

   $_SESSION['SUCCESS'] = $modify_text['editcommentsaved'];
   $fs->Redirect($fs->CreateURL('details', Req::val('task_id')));

// End of editing a comment

/////////////////////////////////
// Start of deleting a comment //
/////////////////////////////////

} elseif (Get::val('action') == "deletecomment" && $user->perms['delete_comments'])
{
   $result = $db->Query("SELECT comment_text, user_id, date_added
                                        FROM {comments}
                                        WHERE comment_id = ?",
                                        array(Get::val('comment_id'))
                           );
   $comment = $db->FetchRow($result);

   // Check for files attached to this comment
   $check_attachments = $db->Query("SELECT * FROM {attachments}
                                    WHERE comment_id = ?",
                                    array(Req::val('comment_id'))
                                  );

   if($db->CountRows($check_attachments) && !$user->perms['delete_attachments'])
   {
      $_SESSION['ERROR'] = $modify_text['commentattachperms'];
      $fs->redirect($fs->CreateURL('details', Req::val('task_id')));

   } else
   {
      $db->Query("DELETE FROM {comments}
                  WHERE comment_id = ?",
                  array(Req::val('comment_id'))
                );

      $fs->logEvent(Req::val('task_id'), 6, $comment['user_id'], $comment['comment_text'], $comment['date_added']);

      while ($attachment = $db->FetchRow($check_attachments))
      {
         // Delete the attachment
         $db->Query("DELETE from {attachments}
                     WHERE attachment_id = ?",
                     array($attachment['attachment_id'])
                   );

         // Log to task history
         $fs->logEvent($attachment['task_id'], 8, $attachment['orig_name']);
      }

      $_SESSION['SUCCESS'] = $modify_text['commentdeleted'];
      $fs->redirect($fs->CreateURL('details', Req::val('task_id')));

   // End of permission check
   }

// End of deleting a comment

/////////////////////////////////////
// Start of deleting an attachment //
/////////////////////////////////////

} elseif (Req::val('action') == 'deleteattachment'
          && $user->perms['delete_attachments'])
{
   // if an attachment needs to be deleted do it right now
   $result = $db->Query("SELECT * FROM {attachments}
                         WHERE attachment_id = ?",
                         array(Req::val('id'))
                       );
   $row = $db->FetchArray($result);

   @unlink("attachments/" . $row['file_name']);
   $db->Query("DELETE FROM {attachments}
               WHERE attachment_id = ?",
               array(Req::val('id'))
             );

  $fs->logEvent($row['task_id'], 8, $row['orig_name']);

  $_SESSION['SUCCESS'] = $modify_text['attachmentdeleted'];
  $fs->redirect($fs->CreateURL('details', $row['task_id']));

// End of deleting an attachment

////////////////////////////////
// Start of adding a reminder //
////////////////////////////////

} elseif (Post::val('action') == "addreminder"
          && ($user->perms['manage_project'] || $user->perms['is_admin'])) {

  $now = date('U');

  $how_often = Post::val('timeamount1') * Post::val('timetype1');
  //echo "how often = $how_often<br>";
  //echo "now = $now<br>";

  $start_time = (Post::val('timeamount2') * Post::val('timetype2')) + $now;
  //echo "start time = $start_time";

  $insert = $db->Query("INSERT INTO {reminders} (task_id, to_user_id, from_user_id, start_time, how_often, reminder_message) VALUES(?,?,?,?,?,?)", array(Post::val('task_id'), Post::val('to_user_id'), $user->id, $start_time, $how_often, Post::val('reminder_message')));

  $fs->logEvent(Post::val('task_id'), 17, Post::val('to_user_id'));

  $_SESSION['SUCCESS'] = $modify_text['reminderadded'];
  $fs->redirect($fs->CreateURL('details', Req::val('task_id')).'#remind');

// End of adding a reminder

//////////////////////////////////
// Start of removing a reminder //
//////////////////////////////////
} elseif (Post::val('action') == "deletereminder"
          && ($user->perms['manage_project'] || $user->perms['is_admin'])) {

  $result = $db->Query("SELECT to_user_id FROM {reminders} WHERE reminder_id = ?", array(Post::val('reminder_id')));
  $reminder = $db->FetchRow($result);
  $db->Query("DELETE FROM {reminders} WHERE reminder_id = ?",
                    array(Post::val('reminder_id')));

  $fs->logEvent(Post::val('task_id'), 18, $reminder['to_user_id']);

  $_SESSION['SUCCESS'] = $modify_text['reminderdeleted'];
  $fs->redirect($fs->CreateURL('details', Req::val('task_id')).'#remind');

// End of removing a reminder

/////////////////////////////////////////////////
// Start of adding a bunch of users to a group //
/////////////////////////////////////////////////
} elseif (Post::val('action') == "addtogroup"
          && ($user->perms['manage_project'] || $user->perms['is_admin'])) {

  // If no users were selected, throw an error
   if (!is_array(Post::val('user_list')))
   {
      $_SESSION['ERROR'] = $modify_text['nouserselected'];
      $fs->redirect(Post::val('prev_page'));

   // If users were select, keep going
   } else {

      // Cycle through the users passed to us
      //while (list($key, $val) = each(Post::val('user_list'))) {
      foreach (Post::val('user_list') AS $key => $val)
      {
         // Create entries for them that point to the requested group
         $create = $db->Query("INSERT INTO {users_in_groups}
                               (user_id, group_id)
                               VALUES(?, ?)",
                               array($val, Post::val('add_to_group'))
                             );
      }

   $_SESSION['SUCCESS'] = $modify_text['groupswitchupdated'];
   $fs->redirect(Post::val('prev_page'));

   }

// End of adding a bunch of users to a group


///////////////////////////////////////////////
// Start of change a bunch of users' groups //
//////////////////////////////////////////////
} elseif (Post::val('action') == 'movetogroup'
          && ($user->perms['manage_project'] || $user->perms['is_admin']))
{
   // Cycle through the array of user ids
   foreach (Post::val('users') AS $user_id => $val)
   {
      // To be removed from a project entirely
      if (Post::val('switch_to_group') == '0')
      {
         $db->Query("DELETE FROM {users_in_groups}
                     WHERE user_id = ? AND group_id = ?",
                     array($user_id, Post::val('old_group'))
                   );

      // Otherwise moved to another project/global group
      } else
      {
         $db->Query("UPDATE {users_in_groups}
                     SET group_id = ?
                     WHERE user_id = ? AND group_id = ?",
                     array(Post::val('switch_to_group'), $user_id, Post::val('old_group')));
      }
   }

  $_SESSION['SUCCESS'] = $modify_text['groupswitchupdated'];
  $fs->redirect(Post::val('prev_page'));

  // End of changing a bunch of users' groups

///////////////////////////////
// Start of taking ownership //
///////////////////////////////

} elseif (Req::val('action') == 'takeownership')
{
   if ( Req::val('prev_page') )
   {
      $ids = Req::val('ids');
      $tasks = array();
      $redirect_url = Req::val('prev_page');

      if (!empty($ids))
      {
         foreach ($ids AS $key => $val)
            array_push($tasks, $key);

         $be->AssignToMe($user->id, $tasks);
      }

   } else
   {
      $be->AssignToMe($user->id, array(Req::val('ids')));
      $redirect_url = $redirect_url = $fs->CreateURL('details', Req::val('ids'));
   }

   $_SESSION['SUCCESS'] = $modify_text['takenownership'];
   $fs->redirect($redirect_url);

// End of taking ownership


//////////////////////////////////////
// Start of requesting task closure //
//////////////////////////////////////

} elseif (Post::val('action') == 'requestclose')
{
   // Retrieve details on the task we want to close
   $task_details = $fs->GetTaskDetails(Post::val('task_id'));

   // Log the admin request
   $fs->AdminRequest(1, $task_details['attached_to_project'], Post::val('task_id'), $user->id, Post::val('reason_given'));

   // Log this event to the task history
   $fs->logEvent(Post::val('task_id'), 20, Post::val('reason_given'));

   // Now, get the project managers' details for this project
   $get_pms = $db->Query("SELECT u.user_id
                          FROM {users} u
                          LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id
                          LEFT JOIN {groups} g ON uig.group_id = g.group_id
                          WHERE g.belongs_to_project = ?
                          AND g.manage_project = '1'",
                          array($proj->id)
                        );

   $pms = array();

   // Add each PM to the array
   while ($row = $db->FetchArray($get_pms))
   {
      array_push($pms, $row['user_id']);
   }

   // Call the functions to create the address arrays, and send notifications
   $to  = $notify->SpecificAddresses($pms);
   $msg = $notify->GenerateMsg('12', Post::val('task_id'));
   $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
   $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);

   $_SESSION['SUCCESS'] = $modify_text['adminrequestmade'];
   $fs->redirect($fs->CreateURL('details', Req::val('task_id')));


// End of requesting task closure

/////////////////////////////////////////
// Start of requesting task re-opening //
/////////////////////////////////////////

} elseif (Post::val('action') == 'requestreopen')
{
   // Log the admin request
   $fs->AdminRequest(2, $proj->id, Post::val('task_id'), $user->id, Post::val('reason_given'));

   // Log this event to the task history
   $fs->logEvent(Post::val('task_id'), 21, Post::val('reason_given'));

   // Check if the user is on the notification list
   $check_notify = $db->Query("SELECT * FROM {notifications}
                               WHERE task_id = ?
                               AND user_id = ?",
                               array(Post::val('task_id'), $user->id)
                             );

   if (!$db->CountRows($check_notify))
   {
      // Add the requestor to the task notification list, so that they know when it has been re-opened
      $be->AddToNotifyList($user->id, array(Post::val('task_id')));

      $fs->logEvent(Post::val('task_id'), 9, $user->id);
   }

   // Now, get the project managers details for this project
   $get_pms = $db->Query("SELECT u.user_id
                          FROM {users} u
                          LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id
                          LEFT JOIN {groups} g ON uig.group_id = g.group_id
                          WHERE g.belongs_to_project = ?
                          AND g.manage_project = '1'",
                          array($proj->id)
                        );

   $pms = array();

   // Add each PM to the array
   while ($row = $db->FetchArray($get_pms))
   {
      array_push($pms, $row['user_id']);
   }

   // Call the functions to create the address arrays, and send notifications
   $to  = $notify->SpecificAddresses($pms);
   $msg = $notify->GenerateMsg('12', Post::val('task_id'));
   $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
   $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);


   $_SESSION['SUCCESS'] = $modify_text['adminrequestmade'];
   $fs->redirect($fs->CreateURL('details', Req::val('task_id')));

// End of requesting task re-opening


///////////////////////////////////
// Start of denying a PM request //
///////////////////////////////////

} elseif (Req::val('action') == 'denypmreq' && $user->perms['manage_project'])
{
   // Get info on the pm request
   $result = $db->Query("SELECT task_id
                         FROM {admin_requests}
                         WHERE request_id = ?",
                         array(Req::val('req_id'))
                        );
   $req_details = $db->FetchArray($result);

   // Mark the PM request as 'resolved'
   $db->Query("UPDATE {admin_requests}
               SET resolved_by = ?, time_resolved = ?, deny_reason = ?
               WHERE request_id = ?",
               array($user->id, date('U'), Req::val('deny_reason'), Req::val('req_id')));


   // Log this event to the task's history
   $fs->logEvent($req_details['task_id'], 28, Req::val('deny_reason'));

   // Send notifications
   $notify->Create('13', $req_details['task_id']);

   // Redirect
   $_SESSION['SUCCESS'] = $modify_text['pmreqdenied'];
   $fs->redirect(Req::val('prev_page'));

// End of denying a PM request


//////////////////////////////////
// Start of adding a dependency //
//////////////////////////////////

} elseif (Post::val('action') == 'newdep'
        && (($user->perms['modify_own_tasks'] && $old_details['assigned_to'] == $user->id)
            || $user->perms['modify_all_tasks'])
        && Post::val('dep_task_id'))
{
  // First check that the user hasn't tried to add this twice
  $check_dep = $db->Query("SELECT * FROM {dependencies}
                             WHERE task_id = ? AND dep_task_id = ?",
                             array(Post::val('task_id'), Post::val('dep_task_id')));

  // or that they are trying to reverse-depend the same task, creating a mutual-block
  $check_dep2 = $db->Query("SELECT * FROM {dependencies}
                             WHERE task_id = ? AND dep_task_id = ?",
                             array(Post::val('dep_task_id'), Post::val('task_id')));

  // Check that the dependency actually exists!
  $check_dep3 = $db->Query("SELECT * FROM {tasks}
                              WHERE task_id = ?",
                              array(Post::val('dep_task_id'))
                            );

   $notify->Create('5', Post::val('task_id'));

//    $to  = $notify->Address(Post::val('task_id'));
//    $msg = $notify->Create('5', Post::val('task_id'));
//    $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
//    $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);


   if (!$db->CountRows($check_dep)
       && !$db->CountRows($check_dep2)
       && $db->CountRows($check_dep3)
       // Check that the user hasn't tried to add the same task as a dependency
       && Post::val('task_id') != Post::val('dep_task_id')) {

    // Log this event to the task history, both ways
    $fs->logEvent(Post::val('task_id'), 22, Post::val('dep_task_id'));
    $fs->logEvent(Post::val('dep_task_id'), 23, Post::val('task_id'));

    // Add the dependency to the database
    $add_dep = $db->Query("INSERT INTO {dependencies}
                             (task_id, dep_task_id)
                             VALUES(?,?)",
                             array(Post::val('task_id'), Post::val('dep_task_id')));




    // Redirect
   $_SESSION['SUCCESS'] = $modify_text['dependadded'];
   $fs->redirect($fs->CreateURL('details', Req::val('task_id')));

  // If the user tried to add the wrong task as a dependency
  } else {

    // If the user tried to add the 'wrong' task as a dependency,
    // show error and redirect
   $_SESSION['ERROR'] = $modify_text['dependaddfailed'];
   $fs->redirect($fs->CreateURL('details', Req::val('task_id')));

  };

// End of adding a dependency


////////////////////////////////////
// Start of removing a dependency //
////////////////////////////////////

} elseif (Get::val('action') == 'removedep'
          && (($user->perms['modify_own_tasks'] && $old_details['assigned_to'] == $user->id)
             || $user->perms['modify_all_tasks'])) {

  // We need some info about this dep for the task history
  $result = $db->Query("SELECT * FROM {dependencies}
                        WHERE depend_id = ?",
                        array(Get::val('depend_id')));
  $dep_info = $db->FetchArray($result);

   $notify->Create('6', $dep_info['task_id']);

//    $to  = $notify->Address($dep_info['task_id']);
//    $msg = $notify->Create('6', $dep_info['task_id']);
//    $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
//    $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);

   // Log this event to the task's history
   $fs->logEvent($dep_info['task_id'], 24, $dep_info['dep_task_id']);
   $fs->logEvent($dep_info['dep_task_id'], 25, $dep_info['task_id']);

   // Do the removal
   $remove = $db->Query("DELETE FROM {dependencies}
                         WHERE depend_id = ?",
                         array(Get::val('depend_id')));

   // Generate status message and redirect
   $_SESSION['SUCCESS'] = $modify_text['depremoved'];
   $fs->redirect($fs->CreateURL('details', $dep_info['task_id']));

// End of removing a dependency


//////////////////////////////////////////////////
// Start of a user requesting a password change //
//////////////////////////////////////////////////

} elseif (Post::val('action') == 'sendmagic') {

   // Check that the username exists
   $check_details = $db->Query("SELECT * FROM {users}
                                WHERE user_name = ?",
                                array(Post::val('user_name')));

   // If the username doesn't exist, throw an error
   if (!$db->CountRows($check_details))
   {
      $_SESSION['ERROR'] = $modify_text['usernotexist'];
      $fs->redirect($fs->CreateURL('lostpw', null));

   // ...otherwise get on with it
   } else
   {
      $user_details = $db->FetchArray($check_details);

      // Generate a looonnnnggg random string to send as an URL
      $magic_url = md5(microtime());

      // Insert the random "magic url" into the user's profile
      $update = $db->Query("UPDATE {users}
                            SET magic_url = ?
                            WHERE user_id = ?",
                            array($magic_url, $user_details['user_id'])
                          );

      // Create notification message
      $subject = $modify_text['noticefrom'] . ' ' . $proj->prefs['project_title'];

      $message = "{$modify_text['noticefrom']} {$proj->prefs['project_title']} \n
{$modify_text['magicurlmessage']} \n
{$conf['general']['baseurl']}index.php?do=lostpw&amp;magic=$magic_url\n";
      // End of generating a message

      // Send the brief notification message

      $to  = $notify->SpecificAddresses(array($user_details['user_id']));
      $mail = $notify->SendEmail($to[0], $subject, $message);
      $jabb = $notify->StoreJabber($to[1], $subject, $message);

      // Let the user know what just happened
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['magicurlsent']}</em></p></div>";

  // End of checking if the username exists
  };

// End of a user requesting a password change


////////////////////////////////
// Change the user's password //
////////////////////////////////

} elseif (Post::val('action') == 'chpass')
{
   // Check that the user submitted both the fields, and they are the same
   if (Post::val('pass1') != ''
     && Post::val('pass2') != ''
     && Post::val('magic_url') != ''
     && Post::val('pass2') == Post::val('pass2'))
   {
      // Get the user's details from the magic url
      $result = $db->Query("SELECT * FROM {users}
                            WHERE magic_url = ?",
                            array(Post::val('magic_url'))
                          );
      $user_details = $db->FetchArray($result);

      // Encrypt the new password
      $new_pass_hash = $fs->cryptPassword(Post::val('pass1'));

      // Change the password and clear the magic_url field
      $update = $db->Query("UPDATE {users} SET
                            user_pass = ?,
                            magic_url = ''
                            WHERE magic_url = ?",
                            array($new_pass_hash, Post::val('magic_url'))
                          );

      // Let the user know what just happened
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['passchanged']}</em></p>";
      echo "<p>{$modify_text['loginbelow']}</p></div>";

   // If the fields were submitted incorrectly, show an error
   } else
   {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['erroronform']}</em></p>";
      echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

   // End of checking fields were submitted correctly
   }

// End of changing the user's password


////////////////////////////////////
// Start of making a task private //
////////////////////////////////////

} elseif (Get::val('action') == 'makeprivate' && $user->perms['manage_project'])
{
   $update = $db->Query("UPDATE {tasks}
                         SET mark_private = '1'
                         WHERE task_id = ?",
                         array(Get::val('id'))
                       );

   // Log to task history
   $fs->logEvent(Get::val('id'), 26);

   $_SESSION['SUCCESS'] = $modify_text['taskmadeprivate'];
   $fs->redirect($fs->CreateURL('details', Req::val('id')));

// End of making a task private


///////////////////////////////////
// Start of making a task public //
///////////////////////////////////

} elseif (Get::val('action') == 'makepublic' && $user->perms['manage_project'])
{
   $update = $db->Query("UPDATE {tasks}
                         SET mark_private = '0'
                         WHERE task_id = ?",
                         array(Get::val('id'))
                       );

   // Log to task history
   $fs->logEvent(Get::val('id'), 27);

   $_SESSION['SUCCESS'] = $modify_text['taskmadepublic'];
   $fs->redirect($fs->CreateURL('details', Req::val('id')));

// End of making a task public


/////////////////////
// End of actions! //
/////////////////////
}

?>
