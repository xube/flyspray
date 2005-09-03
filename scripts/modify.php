<?php
/*
   This script performs all database modifications/
*/

$fs->get_language_pack($lang, 'modify');


// Include the notifications class
include_once ( "$basedir/includes/notify.inc.php" );
$notify = new Notifications;


// FIXME: only temporary workaround
$_POST['default_cat_owner'] = $db->emptyToZero($_POST['default_cat_owner']);
$_POST['category_owner']    = $db->emptyToZero($_POST['category_owner']);

$list_table_name = "{$dbprefix}list_".addslashes($_POST['list_type']);
$list_column_name = addslashes($_POST['list_type'])."_name";
$list_id = addslashes($_POST['list_type'])."_id";

$now = date('U');

if ( !empty($_REQUEST['task_id']) )
   $old_details = $fs->GetTaskDetails($_REQUEST['task_id']);


////////////////////////////////
// Start of adding a new task //
////////////////////////////////

if ($_POST['action'] == 'newtask'
    && (@$permissions['open_new_tasks'] == '1'
    OR $project_prefs['anon_open'] == '1'))
{
   // If they entered something in both the summary and detailed description
   if (!empty($_POST['item_summary']) && !empty($_POST['detailed_desc']))
   {
      $item_summary = $_POST['item_summary'];
      $detailed_desc = $_POST['detailed_desc'];

      $param_names = array('task_type', 'item_status',
        'assigned_to', 'product_category', 'product_version',
        'closedby_version', 'operating_system', 'task_severity',
        'task_priority');

      $sql_values = array($_POST['project_id'], $now, $now, $item_summary,
                $detailed_desc,
                $db->emptyToZero($_COOKIE['flyspray_userid']),
                '0');

      $sql_params = array();
      foreach ($param_names as $param_name)
      {
         if (!empty($_POST[$param_name]))
         {
            array_push($sql_params, $param_name);
            array_push($sql_values, $_POST[$param_name]);
         }
      }

      // Process the due_date
      if (isset($_POST['due_date']) && !empty($_POST['due_date']))
      {
         $due_date = strtotime("{$_POST['due_date']} +23 hours 59 minutes 59 seconds");
      } else
      {
         $due_date = '0';
      }
      array_push($sql_params, 'due_date');
      array_push($sql_values, $due_date);

      $sql_params = join(', ', $sql_params);
      $sql_placeholder = join(', ', array_fill(1, count($sql_values), '?'));

      $add_item = $db->Query("INSERT INTO {$dbprefix}tasks
                              (attached_to_project,
                              date_opened,
                              last_edited_time,
                              item_summary,
                              detailed_desc,
                              opened_by,
                              percent_complete,
                              $sql_params)
                              VALUES ($sql_placeholder)",
                                      $sql_values);

    // Now, let's get the task_id back, so that we can send a direct link
    // URL in the notification message
    $task_details = $db->FetchArray($db->Query("SELECT task_id, item_summary, product_category
                                                FROM {$dbprefix}tasks
                                                WHERE item_summary = ?
                                                AND detailed_desc = ?
                                                ORDER BY task_id DESC",
                                                array($item_summary, $detailed_desc), 1));

   // Log that the task was opened
   $fs->logEvent($task_details['task_id'], 1);

      // If the user uploaded one or more files
      if ($permissions['create_attachments'] == '1')
      {
         $files_added = $be->UploadFiles($current_user['user_id'],
                                         $task_details['task_id'],
                                         $_FILES
                                        );
      }

   $cat_details = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}list_category
                                              WHERE category_id = ?",
                                              array($_POST['product_category'])
                                            )
                                 );

   // We need to figure out who is the category owner for this task
   if (!empty($cat_details['category_owner']))
   {
      $owner = $cat_details['category_owner'];

   } elseif (!empty($cat_details['parent_id']))
   {
      $parent_cat_details = $db->FetchArray($db->Query("SELECT category_owner
                                                         FROM {$dbprefix}list_category
                                                         WHERE category_id = ?",
                                                         array($cat_details['parent_id'])
                                                      )
                                            );

      // If there's a parent category owner, send to them
      if (!empty($parent_cat_details['category_owner']))
         $owner = $parent_cat_details['category_owner'];
   }

   // Otherwise send it to the default category owner
   if (empty($owner))
      $owner = $project_prefs['default_cat_owner'];

   if (!empty($owner))
   {
      // Category owners now get auto-added to the notification list for new tasks
      $insert = $db->Query("INSERT INTO {$dbprefix}notifications
                           (task_id, user_id)
                           VALUES(?, ?)",
                           array($task_details['task_id'], $owner)
                          );

      $fs->logEvent($task_details['task_id'], 9, $owner);

      // Create the Notification
      $notify->Create('1', $task_details['task_id']);

   // End of checking if there's a category owner set, and notifying them.
   }

   // If the reporter wanted to be added to the notification list
   if ($_POST['notifyme'] == '1' && ($_COOKIE['flyspray_userid'] != $owner))
      $be->AddToNotifyList($current_user['user_id'], array($task_details['task_id']));

   // Status and redirect
   $_SESSION['SUCCESS'] = $modify_text['newtaskadded'];
   $fs->redirect($fs->CreateURL('details', $task_details['task_id']));

?>

<?php
  // If they didn't fill in both the summary and detailed description, show an error
  } else {
    echo "<div class=\"redirectmessage\"><p>{$modify_text['summaryanddetails']}</p>";
    echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };

// End of adding a new task.

/////////////////////////////////////////
// Start of modifying an existing task //
/////////////////////////////////////////

} elseif ($_POST['action'] == "update"
          && ($permissions['modify_all_tasks'] == '1'
              OR ($permissions['modify_own_tasks'] == '1'
                  && $current_user['user_id'] == $old_details['assigned_to'])))
{

   // If they entered something in both the summary and detailed description
   if (!empty($_POST['item_summary'])
      && !empty($_POST['detailed_desc']))
   {

   // Check to see if this task has already been modified before we clicked "save"...
   // If so, we need to confirm that the we really wants to save our changes
   if ($_POST['edit_start_time'] < $old_details['last_edited_time'])
   {
      echo $modify_text['alreadyedited'];
      ?>

      <br><br>
      <span>
      <form name="form1" action="index.php" method="post">
         <input type="hidden" name="do" value="modify">
         <input type="hidden" name="action" value="update">
         <input type="hidden" name="task_id" value="<?php echo $_POST['task_id'];?>">
         <input type="hidden" name="edit_start_time" value="999999999999">
         <input type="hidden" name="attached_to_project" value="<?php echo $_POST['attached_to_project'];?>">
         <input type="hidden" name="task_type" value="<?php echo $_POST['task_type'];?>">

         <!-- A bit dodgy, part 1 -->
         <input type="text" style="display:none;" name="item_summary" value="<?php echo htmlspecialchars($_POST['item_summary'],ENT_COMPAT,'utf-8');?>">
         <textarea style="display:none" name="detailed_desc"><?php echo htmlspecialchars($_POST['detailed_desc'],ENT_COMPAT,'utf-8');?></textarea>

         <input type="hidden" name="item_status" value="<?php echo $_POST['item_status'];?>">
         <input type="hidden" name="assigned_to" value="<?php echo $_POST['assigned_to'];?>">
         <input type="hidden" name="product_category" value="<?php echo $_POST['product_category'];?>">
         <input type="hidden" name="closedby_version" value="<?php echo $_POST['closedby_version'];?>">
         <input type="hidden" name="due_date" value="<?php echo $_POST['due_date'];?>">
         <input type="hidden" name="operating_system" value="<?php echo $_POST['operating_system'];?>">
         <input type="hidden" name="task_severity" value="<?php echo $_POST['task_severity'];?>">
         <input type="hidden" name="task_priority" value="<?php echo $_POST['task_priority'];?>">
         <input type="hidden" name="percent_complete" value="<?php echo $_POST['percent_complete'];?>">
         <input type="submit" class="adminbutton" value="<?php echo $modify_text['saveanyway']; ?>">
      </form>
      </span>
      &nbsp;&nbsp;&nbsp;
      <span>
      <form action="index.php" method="get">
         <input type="hidden" name="do" value="details">
         <input type="hidden" name="id" value="<?php echo $_POST['task_id'];?>">
         <input type="submit" class="adminbutton" value="<?php echo $modify_text['cancel'];?>">
      </form>
      </span>

   <?php
   } else {

      $old_details_history = $db->FetchRow($db->Query("SELECT * FROM {$dbprefix}tasks WHERE task_id = ?", array($_POST['task_id'])));

      $item_summary = $_POST['item_summary'];
      $detailed_desc = $_POST['detailed_desc'];

      // A bit dodgy, part 2.
      if ($_POST['edit_start_time'] == "999999999999")
      {
         $item_summary = stripslashes($_POST['item_summary']);
         $detailed_desc = stripslashes($_POST['detailed_desc']);
      }

      if (!empty($_POST['due_date']))
      {
         $due_date = strtotime("{$_POST['due_date']} +23 hours 59 minutes 59 seconds");
      } else
      {
         $due_date = '0';
      }

      $add_item = $db->Query("UPDATE {$dbprefix}tasks SET
                              attached_to_project = ?,
                              task_type = ?,
                              item_summary = ?,
                              detailed_desc = ?,
                              item_status = ?,
                              assigned_to = ?,
                              product_category = ?,
                              closedby_version = ?,
                              operating_system = ?,
                              task_severity = ?,
                              task_priority = ?,
                              last_edited_by = ?,
                              last_edited_time = ?,
                              due_date = ?,
                              percent_complete = ?

                              WHERE task_id = ?",
                              array($_POST['attached_to_project'], $_POST['task_type'],
                              $item_summary, $detailed_desc, $_POST['item_status'],
                              $_POST['assigned_to'], $_POST['product_category'],
                              $db->emptyToZero($_POST['closedby_version']),
                              $_POST['operating_system'], $_POST['task_severity'],
                              $_POST['task_priority'], $_COOKIE['flyspray_userid'],
                              $now,
                              $due_date,
                              $_POST['percent_complete'],
                              $_POST['task_id'])
                           );

      // Get the details of the task we just updated
      // To generate the changed-task message
      $new_details = $fs->GetTaskDetails($_POST['task_id']);
      $new_details_history = $db->FetchRow($db->Query("SELECT * FROM {$dbprefix}tasks WHERE task_id = ?", array($_POST['task_id'])));

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

      while (list($key, $val) = each($field))
      {
         if ($old_details[$val] != $new_details[$val])
            $send_me = 'YES';
      }

      // Log the changed fields in the task history
      while (list($key, $val) = each($old_details_history))
      {
         if ($key != 'last_edited_time' && $key != 'last_edited_by' && $key != 'assigned_to'
             && !is_numeric($key)
             && $old_details_history[$key] != $new_details_history[$key])
         {
            $fs->logEvent($_POST['task_id'], 0, $new_details_history[$key], $old_details_history[$key], $key);
         }
      }


      if ($send_me == 'YES')
      {
         $notify->Create('2', $new_details['task_id']);
      }

      // Check to see if the assignment has changed
      if ($_POST['old_assigned'] != $_POST['assigned_to'])
      {
         // Log to task history
         $fs->logEvent($_POST['task_id'], 14, $_POST['assigned_to'], $_POST['old_assigned']);

         // Notify the new assignee what happened
         if ($new_details['assigned_to'] != $current_user['user_id'])
         {
            $to  = $notify->SpecificAddresses(array($_POST['assigned_to']));
            $msg = $notify->GenerateMsg('14', $_POST['task_id']);
            $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
            $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);
         }
      }

      $_SESSION['SUCCESS'] = $modify_text['taskupdated'];
      $fs->redirect($fs->CreateURL('details', $_POST['task_id']));

   // End of checking if this task was modified while we were editing it.
   };

   // If they didn't fill in both the summary and detailed description, show an error
   } else {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['summaryanddetails']}</em></p>";
      echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
   };

// End of updating an task

/////////////////////////////
// Start of closing a task //
/////////////////////////////

} elseif(isset($_POST['action']) && $_POST['action'] == 'close'
         && ( (@$permissions['close_own_tasks'] == '1'
          && ($old_details['assigned_to'] == $current_user['user_id'])
          OR @$permissions['close_other_tasks'] == '1') ) )
{
   if (!empty($_POST['resolution_reason']))
   {
      $db->Query("UPDATE {$dbprefix}tasks SET
                  date_closed = ?,
                  closed_by = ?,
                  closure_comment = ?,
                  is_closed = '1',
                  resolution_reason = ?
                  WHERE task_id = ?",
                  array($now,
                        $_COOKIE['flyspray_userid'],
                        $db->emptyToZero($_POST['closure_comment']),
                        $_POST['resolution_reason'],
                        $_POST['task_id'])
                );

      if (isset($_POST['mark100']) && $_POST['mark100'] == '1')
      {
         $db->Query("UPDATE {$dbprefix}tasks SET
                     percent_complete = '100'
                     WHERE task_id = ?",
                     array($_POST['task_id'])
                   );

         $fs->logEvent($_POST['task_id'], '0', '100', $old_details['percent_complete'], 'percent_complete');
      }

      // Get the resolution name for the notifications
      $get_res = $db->FetchArray($db->Query("SELECT resolution_name FROM {$dbprefix}list_resolution WHERE resolution_id = ?", array($_POST['resolution_reason'])));

      // Get the item summary for the notifications
      list($item_summary) = $db->FetchArray($db->Query("SELECT item_summary FROM {$dbprefix}tasks WHERE task_id = ?", array($_POST['task_id'])));
      $item_summary = stripslashes($item_summary);

      // Create notification
      $notify->Create('3', $_POST['task_id']);

      // Log this to the task's history
      $fs->logEvent($_POST['task_id'], 2, $_POST['resolution_reason'], $_POST['closure_comment']);

      // If there's an admin request related to this, close it
      if ($fs->AdminRequestCheck(1, $_POST['task_id']) == '1') {
         $db->Query("UPDATE {$dbprefix}admin_requests
                    SET resolved_by = ?, time_resolved = ?
                    WHERE task_id = ? AND request_type = ?",
                    array($current_user['user_id'], date('U'), $_POST['task_id'], 1));
      };

      $_SESSION['SUCCESS'] = $modify_text['taskclosed'];
      $fs->redirect($fs->CreateURL('details', $_POST['task_id']));

   // If the user didn't select a closure reason
   } else
   {
      $_SESSION['ERROR'] = $modify_text['noclosereason'];
      $fs->redirect($fs->CreateURL('details', $_POST['task_id']));
   };

// End of closing a task

/////////////////////////////////
// Start of re-opening an task //
/////////////////////////////////

} elseif ( isset($_GET['action']) && $_GET['action'] == "reopen"
          && ( (@$permissions['close_own_tasks'] == '1'
          && ($old_details['assigned_to'] == $current_user['user_id'])
          OR @$permissions['close_other_tasks'] == '1') ) )
{
    $db->Query("UPDATE {$dbprefix}tasks SET
                resolution_reason = '0',
                closure_comment = '0',
                last_edited_time = ?,
                last_edited_by = ?,
                is_closed = '0'
                WHERE task_id = ?",
                array($now, $current_user['user_id'], $_GET['task_id']));

   $notify->Create('4', $_GET['task_id']);

   // If there's an admin request related to this, close it
   if ($fs->AdminRequestCheck(2, $_GET['task_id']) == '1')
   {
      $db->Query("UPDATE {$dbprefix}admin_requests
                  SET resolved_by = ?, time_resolved = ?
                  WHERE task_id = ? AND request_type = ?",
                  array($current_user['user_id'], date('U'), $_GET['task_id'], 2));
   }

   $fs->logEvent($_GET['task_id'], 13);

   $_SESSION['SUCCESS'] = $modify_text['taskreopened'];
   $fs->redirect($fs->CreateURL('details', $_GET['task_id']));

// End of re-opening an task

///////////////////////////////
// Start of adding a comment //
///////////////////////////////

} elseif ($_POST['action'] == 'addcomment'
          && $permissions['add_comments'] == '1')
{

   if (!empty($_POST['comment_text']))
   {
      $comment = $_POST['comment_text'];

      $db->Query("INSERT INTO {$dbprefix}comments
                  (task_id, date_added, user_id, comment_text)
                  VALUES ( ?, ?, ?, ? )",
                  array($_POST['task_id'], $now, $_COOKIE['flyspray_userid'], $comment));

      $comment = $db->FetchRow($db->Query("SELECT comment_id FROM {$dbprefix}comments
                                           WHERE task_id = ?
                                           ORDER BY comment_id DESC",
                                           array($_POST['task_id']), 1
                                         )
                              );

      $fs->logEvent($_POST['task_id'], 4, $comment['comment_id']);

      // If the user wanted to watch this task for changes
      if ( isset($_POST['notifyme']) && $_POST['notifyme'] == '1' )
         $be->AddToNotifyList($current_user['user_id'], array($_POST['task_id']));

      // If the user uploaded one or more files
      if ($permissions['create_attachments'] == '1')
      {
         $files_added = $be->UploadFiles($current_user['user_id'],
                                         $old_details['task_id'],
                                         $_FILES,
                                         $comment['comment_id']
                                        );
      }

      // Send the notification
      if ($files_added == true)
      {
         $notify->Create('7', $_POST['task_id'], 'files');
      } else
      {
         $notify->Create('7', $_POST['task_id']);
      }

      $_SESSION['SUCCESS'] = $modify_text['commentadded'];
      $fs->redirect($fs->CreateURL('details', $_POST['task_id']));

   // If they pressed submit without actually typing anything
   } else
   {
      $_SESSION['ERROR'] = $modify_text['nocommententered'];
      $fs->redirect($fs->CreateURL('details', $_POST['task_id']));
   }


// End of adding a comment


/////////////////////////////////////////////////////
// Start of sending a new user a confirmation code //
/////////////////////////////////////////////////////

} elseif ($_POST['action'] == 'sendcode')
{
   if (!empty($_POST['user_name'])
         && !empty($_POST['real_name'])
         && (($_POST['email_address'] != '' && $_POST['notify_type'] == '1')
               OR ($_POST['jabber_id'] != '' && $_POST['notify_type'] == '2'))
   )
   {


      // Check to see if the username is available
      $check_username = $db->Query("SELECT * FROM {$dbprefix}users WHERE user_name = ?",
                                     array($_POST['user_name']));
      if ($db->CountRows($check_username))
      {
        echo "<p class=\"admin\">{$register_text['usernametaken']}<br>";
        echo "<a href=\"javascript:history.back();\">{$register_text['goback']}</a></p>";
      } else
      {
         // Delete registration codes older than 24 hours
         $now = date('U');
         $yesterday = $now - '86400';
         $remove = $db->Query("DELETE FROM {$dbprefix}registrations WHERE reg_time < ?",
                                array($yesterday));

         // Generate a random bunch of numbers for the confirmation code
         function make_seed()
         {
            list($usec, $sec) = explode(' ', microtime());
            return (float) $sec + ((float) $usec * 100000);
         }
         mt_srand(make_seed());
         $randval = mt_rand();

         // Convert those numbers to a seemingly random string using crypt
         $confirm_code = crypt($randval, $cookiesalt);

         // Generate a looonnnnggg random string to send as an URL to complete this registration
         $magic_url = md5(microtime());

         // Insert everything into the database
         $save_code = $db->Query("INSERT INTO {$dbprefix}registrations
                                  (reg_time,
                                  confirm_code,
                                  user_name,
                                  real_name,
                                  email_address,
                                  jabber_id,
                                  notify_type,
                                  magic_url)
                                  VALUES (?,?,?,?,?,?,?,?)",
                                array($now,
                                      $confirm_code,
                                      $_POST['user_name'],
                                      $_POST['real_name'],
                                      $_POST['email_address'],
                                      $_POST['jabber_id'],
                                      $_POST['notify_type'],
                                      $magic_url
                                      )
                              );

$subject = $modify_text['noticefrom'] . ' Flyspray';

$message = "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}\n
{$modify_text['addressused']}\n
{$conf['general']['baseurl']}index.php?do=register&magic=$magic_url \n
{$modify_text['confirmcodeis']}\n
{$confirm_code}";

      // Check how they want to receive their code
      if ($_POST['notify_type'] == '1')
      {
         $notify->SendEmail($_POST['email_address'], $subject, $message);

      } elseif ($_POST['notify_type'] == '2')
      {
         $notify->StoreJabber(array($_POST['jabber_id']), $subject,
            htmlentities($message),ENT_COMPAT,'utf-8');

      }

      // Let the user know what just happened
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['codesent']}</em></p></div>";

    // End of checking if the username is available
    }

  // If the form wasn't filled out correctly, show an error
  } else
  {
    // Error!
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['erroronform']}</em></p>";
    echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

  // End of checking that the form was completed correctly
  }


// End of sending a new user a confirmation code


//////////////////////////////////////////////////////////////////
// Start of new user self-registration with a confirmation code //
//////////////////////////////////////////////////////////////////

} elseif ($_POST['action'] == "registeruser" && $flyspray_prefs['anon_reg'] == '1')
{

   // If they filled in all the required fields
   if ($_POST['user_pass'] != ''
      && $_POST['user_pass2'] != ''
    )
   {

      // If the passwords matched
      if (($_POST['user_pass'] == $_POST['user_pass2'])
           && $_POST['user_pass'] != ''
           && $_POST['confirmation_code'] != '')
      {


         // Check that the user entered the right confirmation code
         $code_check = $db->Query("SELECT * FROM {$dbprefix}registrations WHERE magic_url = ?", array($_POST['magic_url']));
         $reg_details = $db->FetchArray($code_check);

         // If the code is correct
         if ($reg_details['confirm_code'] == $_POST['confirmation_code'])
         {
            // Encrypt their password
            $pass_hash = $fs->cryptPassword($_POST['user_pass']);

            // Add the user to the database
            $add_user = $db->Query("INSERT INTO {$dbprefix}users
                                      (user_name,
                                       user_pass,
                                       real_name,
                                       jabber_id,
                                       email_address,
                                       notify_type,
                                       account_enabled,
                                       tasks_perpage)
                                       VALUES(?, ?, ?, ?, ?, ?, ?, ?)",
                                       array($reg_details['user_name'],
                                             $pass_hash,
                                             $reg_details['real_name'],
                                             $reg_details['jabber_id'],
                                             $reg_details['email_address'],
                                             $reg_details['notify_type'],
                                             '1',
                                             '25')
                                   );


            // Get this user's id for the record
            $user_details = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}users WHERE user_name = ?", array($reg_details['user_name'])));

            // Now, create a new record in the users_in_groups table
            $set_global_group = $db->Query("INSERT INTO {$dbprefix}users_in_groups
                                          (user_id,
                                          group_id)
                                          VALUES(?, ?)",
                                          array($user_details['user_id'], $flyspray_prefs['anon_group']));

            // Let the user know what just happened
            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['accountcreated']}</em></p>";
            echo "<p>{$modify_text['loginbelow']}</p>";
            echo "<p>{$modify_text['newuserwarning']}</p></div>";


         // If they didn't enter the right confirmation code
         } else
         {
            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['confirmwrong']}</em></p>";
            echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
         };


      // If passwords didn't match
      } else
      {
         echo "<div class=\"redirectmessage\"><p><em>{$modify_text['nomatchpass']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
      };

   // If they didn't fill in all the fields
   } else {
      echo "<div class=\"redirectessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
   };

// End of registering a new user

///////////////////////////////////////////////////////////////
// Start of user self-registration without confirmation code //
// Or, by an admin                                           //
///////////////////////////////////////////////////////////////

} elseif ($_POST['action'] == "newuser"
           && (@$permissions['is_admin'] == '1'
                OR ($flyspray_prefs['anon_reg'] == '1'
                    && $flyspray_prefs['spam_proof'] != '1')))
{

  // If they filled in all the required fields
  if (!empty($_POST['user_name'])
    && !empty($_POST['user_pass'])
    && !empty($_POST['user_pass2'])
    && !empty($_POST['real_name'])
    && (!empty($_POST['email_address']) OR !empty($_POST['jabber_id']))
    ) {

    // Check to see if the username is available
    $check_username = $db->Query("SELECT * FROM {$dbprefix}users WHERE user_name = ?", array($_POST['user_name']));
    if ($db->CountRows($check_username)) {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['usernametaken']}</em></p>";
      echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    } else {

      // If the passwords matched, add the user
      if (($_POST['user_pass'] == $_POST['user_pass2']) && $_POST['user_pass'] != '') {

        $pass_hash = $fs->cryptPassword($_POST['user_pass']);

        if (@$permissions['is_admin'] == '1') {
          $group_in = $_POST['group_in'];
        } else {
          $group_in = $flyspray_prefs['anon_group'];
        };

        $add_user = $db->Query("INSERT INTO {$dbprefix}users
                                    (user_name,
                                     user_pass,
                                     real_name,
                                     jabber_id,
                                     email_address,
                                     notify_type,
                                     account_enabled,
                                     tasks_perpage)
                                     VALUES( ?, ?, ?, ?, ?, ?, ?, ?)",
                                    array($_POST['user_name'],
                                          $pass_hash,
                                          $_POST['real_name'],
                                          $_POST['jabber_id'],
                                          $_POST['email_address'],
                                          $_POST['notify_type'],
                                          '1',
                                          '25')
                                 );

        // Get this user's id for the record
        $user_details = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}users WHERE user_name = ?", array($_POST['user_name'])));

        // Now, create a new record in the users_in_groups table
        $set_global_group = $db->Query("INSERT INTO {$dbprefix}users_in_groups
                                          (user_id,
                                          group_id)
                                          VALUES( ?, ?)",
                                          array($user_details['user_id'], $group_in));

        if (@$permissions['is_admin'] != '1') {
          echo "<p>{$modify_text['loginbelow']}</p>";
          echo "<p>{$modify_text['newuserwarning']}</p></div>";
        } else
        {
          $_SESSION['SUCCESS'] = $modify_text['newusercreated'];
          $fs->redirect($fs->CreateURL('admin', 'groups'));
        }


      } else
      {
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['nomatchpass']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
      }

    }

  } else
  {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  }

// End of adding a new user by an admin

/////////////////////////////////
// Start of adding a new group //
/////////////////////////////////

} elseif ($_POST['action'] == "newgroup"
          && ((!empty($_POST['belongs_to_project']) && $permissions['manage_project'] == '1')
          OR $permissions['is_admin'] == '1') )
{
   // If they filled in all the required fields
   if (!empty($_POST['group_name']) && !empty($_POST['group_desc']))
   {
      // Check to see if the group name is available
      $check_groupname = $db->Query("SELECT * FROM {$dbprefix}groups
                                     WHERE group_name = ?
                                     AND belongs_to_project = ?",
                                     array($_POST['group_name'],
                                           $_POST['project'])
                                   );

      if ($db->CountRows($check_groupname))
      {
         echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupnametaken']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
      } else
      {
         $db->Query("INSERT INTO {$dbprefix}groups
                     (group_name,
                      group_desc,
                      belongs_to_project,
                      manage_project,
                      view_tasks,
                      open_new_tasks,
                      modify_own_tasks,
                      modify_all_tasks,
                      view_comments,
                      add_comments,
                      edit_comments,
                      delete_comments,
                      create_attachments,
                      delete_attachments,
                      view_history,
                      close_own_tasks,
                      close_other_tasks,
                      assign_to_self,
                      assign_others_to_self,
                      view_reports,
                      group_open)
                      VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                      array($_POST['group_name'], $_POST['group_desc'],
                      $db->emptyToZero($_POST['project']),
                      $db->emptyToZero($_POST['manage_project']),
                      $db->emptyToZero($_POST['view_tasks']),
                      $db->emptyToZero($_POST['open_new_tasks']),
                      $db->emptyToZero($_POST['modify_own_tasks']),
                      $db->emptyToZero($_POST['modify_all_tasks']),
                      $db->emptyToZero($_POST['view_comments']),
                      $db->emptyToZero($_POST['add_comments']),
                      $db->emptyToZero($_POST['edit_comments']),
                      $db->emptyToZero($_POST['delete_comments']),
                      $db->emptyToZero($_POST['create_attachments']),
                      $db->emptyToZero($_POST['delete_attachments']),
                      $db->emptyToZero($_POST['view_history']),
                      $db->emptyToZero($_POST['close_own_tasks']),
                      $db->emptyToZero($_POST['close_other_tasks']),
                      $db->emptyToZero($_POST['assign_to_self']),
                      $db->emptyToZero($_POST['assign_others_to_self']),
                      $db->emptyToZero($_POST['view_reports']),
                      $db->emptyToZero($_POST['group_open']))
                    );

         $_SESSION['SUCCESS'] = $modify_text['newgroupadded'];
         if (empty($_POST['project']) )
         {
            $fs->redirect($fs->CreateURL('admin', 'groups'));
         } else
         {
            $fs->redirect($fs->CreateURL('pm', 'groups', $_POST['project']));
         }
      }

   } else {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
   }
// End of adding a new group

///////////////////////////////////////////////
// Update the global application preferences //
///////////////////////////////////////////////

} elseif ($_POST['action'] == "globaloptions"
          && $permissions['is_admin'] == '1')
{
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'jabber_server'", array($_POST['jabber_server']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'jabber_port'", array($_POST['jabber_port']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'jabber_username'", array($_POST['jabber_username']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'jabber_password'", array($_POST['jabber_password']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'anon_group'", array($_POST['anon_group']));

   /*$base_url = trim($_POST['base_url']);

   if (substr($base_url,-1,1) != '/')
   {
      $base_url .= '/';
   }

   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'base_url'", array($base_url));
*/
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'user_notify'", array($_POST['user_notify']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'admin_email'", array($_POST['admin_email']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'lang_code'", array($_POST['lang_code']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'spam_proof'", array($_POST['spam_proof']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'default_project'", array($_POST['default_project']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'dateformat'", array($_POST['dateformat']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'dateformat_extended'", array($_POST['dateformat_extended']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'anon_reg'", array($_POST['anon_reg']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'global_theme'", array($_POST['global_theme']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'smtp_server'", array($_POST['smtp_server']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'smtp_user'", array($_POST['smtp_user']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'smtp_pass'", array($_POST['smtp_pass']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'funky_urls'", array($_POST['funky_urls']));
   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'reminder_daemon'", array($_POST['reminder_daemon']));

   // Process the list of groups into a format we can store
   foreach ($_POST['assigned_groups'] as $group_id => $val)
      $assigned_groups .= $group_id . ' ';

   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'assigned_groups'", array($assigned_groups));


   // Process the list of visible columns into format we can store
   foreach ($_POST['visible_columns'] as $column => $val)
      $columnlist .= $column . ' ';

   $update = $db->Query("UPDATE {$dbprefix}prefs SET pref_value = ? WHERE pref_name = 'visible_columns'", array($columnlist));


   $_SESSION['SUCCESS'] = $modify_text['optionssaved'];
   $fs->redirect($FS->CreateURL('admin','prefs'));

// End of updating application preferences

///////////////////////////////////
// Start of adding a new project //
///////////////////////////////////

} elseif ($_POST['action'] == "newproject"
          && $permissions['is_admin'] == '1') {

  if ($_POST['project_title'] != '') {

    $insert = $db->Query("INSERT INTO {$dbprefix}projects
                              (project_title,
                              theme_style,
                              show_logo,
                              inline_images,

                              intro_message,
                              others_view,
                              anon_open,
                              project_is_active,
                              visible_columns)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            array($_POST['project_title'],
                              $_POST['theme_style'],
                              $db->emptyToZero($_POST['show_logo']),
                              $db->emptyToZero($_POST['inline_images']),

                              $_POST['intro_message'],
                              $db->emptyToZero($_POST['others_view']),
                              $db->emptyToZero($_POST['anon_open']),
                              '1',
                              'id tasktype severity summary status dueversion progress',
                              ));

    $newproject = $db->FetchArray($db->Query("SELECT project_id FROM {$dbprefix}projects ORDER BY project_id DESC", false, 1));

      $add_group = $db->Query("INSERT INTO {$dbprefix}groups
                                      (group_name,
                                      group_desc,
                                      belongs_to_project,
                                      manage_project,
                                      view_tasks,
                                      open_new_tasks,
                                      modify_own_tasks,
                                      modify_all_tasks,
                                      view_comments,
                                      add_comments,
                                      edit_comments,
                                      delete_comments,
                                      create_attachments,
                                      delete_attachments,
                                      view_history,
                                      close_own_tasks,
                                      close_other_tasks,
                                      assign_to_self,
                                      assign_others_to_self,
                                      view_reports,
                                      group_open)
                                      VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                      array('Project Managers', 'Permission to do anything related to this project.' ,
                                            $db->emptyToZero($newproject['project_id']),
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1',
                                            '1')
                                     );

    $insert = $db->Query("INSERT INTO {$dbprefix}list_category
                             (project_id, category_name, list_position,
                             show_in_list, category_owner)
                             VALUES ( ?, ?, ?, ?, ?)",
                             array($newproject['project_id'],
                            'Backend / Core', '1', '1', '0'));

    $insert = $db->Query("INSERT INTO {$dbprefix}list_os
                             (project_id, os_name, list_position,
                             show_in_list)
                             VALUES (?,?,?,?)",
                             array($newproject['project_id'], 'All', '1', '1'));

    $insert = $db->Query("INSERT INTO {$dbprefix}list_version
                             (project_id, version_name, list_position,
                             show_in_list, version_tense)
                             VALUES (?, ?, ?, ?, ?)",
                        array($newproject['project_id'], '1.0', '1', '1', '2'));

    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['projectcreated']}";
    echo "<br><br><a href=\"" . $fs->CreateURL('pm', 'prefs', $newproject['project_id']) . "\">{$modify_text['customiseproject']}</a></em></p></div>";

  } else {

    echo "<div class=\"errormessage\"><p><em>{$modify_text['emptytitle']}</em></p></div>";

  };

// End of adding a new project

///////////////////////////////////////////
// Start of updating project preferences //
///////////////////////////////////////////

} elseif ($_POST['action'] == 'updateproject' && $permissions['manage_project'] == '1')
{
   if (!empty($_POST['project_title']))
   {
      $update = $db->Query("UPDATE {$dbprefix}projects SET
                            project_title = ?,
                            theme_style = ?,
                            show_logo = ?,
                            inline_images = ?,
                            default_cat_owner = ?,
                            intro_message = ?,
                            project_is_active = ?,
                            others_view = ?,
                            anon_open = ?,
                            notify_email = ?,
                            notify_email_when = ?,
                            notify_jabber = ?,
                            notify_jabber_when = ?
                            WHERE project_id = ?",
                            array($_POST['project_title'],
                                    $_POST['theme_style'],
                                    $db->emptyToZero($_POST['show_logo']),
                                    $db->emptyToZero($_POST['inline_images']),
                                    $db->emptyToZero($_POST['default_cat_owner']),
                                    $_POST['intro_message'],
                                    $db->emptyToZero($_POST['project_is_active']),
                                    $db->emptyToZero($_POST['others_view']),
                                    $db->emptyToZero($_POST['anon_open']),
                                    $_POST['notify_email'],
                                    $db->emptyToZero($_POST['notify_email_type']),
                                    $_POST['notify_jabber'],
                                    $db->emptyToZero($_POST['notify_jabber_type']),
                                    $_POST['project_id']));

      // Process the list of visible columns into a format we can store
      foreach ($_POST['visible_columns'] as $column => $val)
         $columnlist .= $column . ' ';

      $update = $db->Query("UPDATE {$dbprefix}projects SET visible_columns = ? WHERE project_id = ?", array($columnlist, $_POST['project_id']));


      $_SESSION['SUCCESS'] = $modify_text['projectupdated'];
      $fs->redirect($fs->CreateURL('pm', 'prefs', $project_id));

   } else
   {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['emptytitle']}</em></p></div>";
   }


// End of updating project preferences

//////////////////////////////////////
// Start of uploading an attachment //
//////////////////////////////////////

} elseif ($_POST['action'] == "addattachment"
          && $permissions['create_attachments'] == '1')
{
   // This function came from the php function page for mt_srand()
   // seed with microseconds to create a random filename
   function make_seed()
   {
      list($usec, $sec) = explode(' ', microtime());
      return (float) $sec + ((float) $usec * 100000);
   }

   mt_srand(make_seed());
   $randval = mt_rand();
   $file_name = $_POST['task_id']."_$randval";

   // If there is a file attachment to be uploaded, upload it
   if ($_FILES['userfile']['name'])
   {
      // Then move the uploaded file into the attachments directory and remove exe permissions
      @move_uploaded_file($_FILES['userfile']['tmp_name'], "attachments/$file_name");
      @chmod("attachments/$file_name", 0644);

      // Only add the listing to the database if the file was actually uploaded successfully
      if (file_exists("attachments/$file_name"))
      {
         $file_desc = $_POST['file_desc'];
         $add_to_db = $db->Query("INSERT INTO {$dbprefix}attachments
                                  (task_id, orig_name, file_name, file_desc,
                                  file_type, file_size, added_by, date_added)
                                  VALUES ( ?, ?, ?, ?, ?, ?, ?, ?)",
                                  array($_POST['task_id'],
                                       $_FILES['userfile']['name'],
                                       $file_name, $file_desc,
                                       $_FILES['userfile']['type'],
                                       $_FILES['userfile']['size'],
                                       $_COOKIE['flyspray_userid'],
                                       $now)
                                 );

         $notify->Create('8', $_POST['task_id']);

         $row = $db->FetchRow($db->Query("SELECT attachment_id FROM {$dbprefix}attachments WHERE task_id = ? ORDER BY attachment_id DESC", array($_POST['task_id']), 1));
         $fs->logEvent($_POST['task_id'], 7, $row['attachment_id']);

         // Success message!
         $_SESSION['SUCCESS'] = $modify_text['fileuploaded'];
         $fs->redirect($fs->CreateURL('details', $_POST['task_id']));

      // If the file didn't actually get saved, better show an error to that effect
      } else
      {
         $_SESSION['ERROR'] = $modify_text['fileerror'];
         $fs->redirect($fs->CreateURL('details', $_POST['task_id']));
      }

   // If there wasn't a file uploaded with a description, show an error
   } else
   {
      $_SESSION['ERROR'] = $modify_text['selectfileerror'];
      $fs->redirect($fs->CreateURL('details', $_POST['task_id']));
   }
// End of uploading an attachment

/////////////////////////////////////
// Start of modifying user details //
/////////////////////////////////////

} elseif ($_POST['action'] == "edituser"
          && ($permissions['is_admin'] == '1'
              OR ($current_user['user_id'] == $_POST['user_id'])))
{
   // If they filled in all the required fields
   if (!empty($_POST['real_name'])
       && (!empty($_POST['email_address'])
          OR !empty($_POST['jabber_id']))
      )
   {
      //If the user entered matching password and confirmation
      //we can change the selected user's password
      $password_problem = false;
      if ($_POST['changepass'] || $_POST['confirmpass'])
      {
         //check that the entered passwords match
         if ($_POST['changepass'] == $_POST['confirmpass'])
         {
            $new_pass = $_POST['changepass'];
            $new_pass_hash = $fs->cryptPassword($new_pass);
            $update_pass = $db->Query("UPDATE {$dbprefix}users SET user_pass = '$new_pass_hash' WHERE user_id = ?", array($_POST['user_id']));

            // If the user is changing their password, better update their cookie hash
            if ($_COOKIE['flyspray_userid'] == $_POST['user_id'])
            {
               setcookie('flyspray_passhash', crypt("$new_pass_hash", $cookiesalt), time()+60*60*24*30, "/");
            }

         } else
         {
            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['passnomatch']}</em></p>";
            echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
            $password_problem = true;
         }
      }

      if ($password_problem == false)
      {
         $update = $db->Query("UPDATE {$dbprefix}users SET
                                 real_name = ?,
                                 email_address = ?,
                                 jabber_id = ?,
                                 notify_type = ?,
                                 dateformat = ?,
                                 dateformat_extended = ?,
                                 tasks_perpage = ?
                                 WHERE user_id = ?",
                                 array(
                                       $_POST['real_name'],
                                       $_POST['email_address'],
                                       $_POST['jabber_id'],
                                       $db->emptyToZero($_POST['notify_type']),
                                       $_POST['dateformat'],
                                       $_POST['dateformat_extended'],
                                       $_POST['tasks_perpage'],
                                       $_POST['user_id']
                                      )
                              );

         if ($permissions['is_admin'] == '1' && !empty($_POST['group_in']))
         {
            $update = $db->Query("UPDATE {$dbprefix}users SET
                                  account_enabled = ?
                                  WHERE user_id = ?",
                                  array(
                                        $db->emptyToZero($_POST['account_enabled']),
                                        $_POST['user_id']
                                        )
                                );

            $update = $db->Query("UPDATE {$dbprefix}users_in_groups SET
                                  group_id = ?
                                  WHERE record_id = ?",
                                  array($_POST['group_in'], $_POST['record_id'])
                              );

         }

         $_SESSION['SUCCESS'] = $modify_text['userupdated'];
         $fs->redirect($_POST['prev_page']);
      };

   } else
   {
      $_SESSION['ERROR'] = $modify_text['realandnotify'];
      $fs->redirect($_POST['prev_page']);
   }
   // End of modifying user details

//////////////////////////////////////////
// Start of updating a group definition //
//////////////////////////////////////////

} elseif ($_POST['action'] == "editgroup"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1'))
{
   if ($_POST['group_name'] != ''
     && $_POST['group_desc'] != '')
   {
      $update = $db->Query("UPDATE {$dbprefix}groups SET
                             group_name = ?,
                             group_desc = ?,
                             manage_project = ?,
                             view_tasks = ?,
                             open_new_tasks = ?,
                             modify_own_tasks = ?,
                             modify_all_tasks = ?,
                             view_comments = ?,
                             add_comments = ?,
                             edit_comments = ?,
                             delete_comments = ?,
                             view_attachments = ?,
                             create_attachments = ?,
                             delete_attachments = ?,
                             view_history = ?,
                             close_own_tasks = ?,
                             close_other_tasks = ?,
                             assign_to_self = ?,
                             assign_others_to_self = ?,
                             view_reports = ?,
                             group_open = ?
                             WHERE group_id = ?",
                             array($_POST['group_name'], $_POST['group_desc'],
                                   $db->emptyToZero($_POST['manage_project']),
                                   $db->emptyToZero($_POST['view_tasks']),
                                   $db->emptyToZero($_POST['open_new_tasks']),
                                   $db->emptyToZero($_POST['modify_own_tasks']),
                                   $db->emptyToZero($_POST['modify_all_tasks']),
                                   $db->emptyToZero($_POST['view_comments']),
                                   $db->emptyToZero($_POST['add_comments']),
                                   $db->emptyToZero($_POST['edit_comments']),
                                   $db->emptyToZero($_POST['delete_comments']),
                                   $db->emptyToZero($_POST['view_attachments']),
                                   $db->emptyToZero($_POST['create_attachments']),
                                   $db->emptyToZero($_POST['delete_attachments']),
                                   $db->emptyToZero($_POST['view_history']),
                                   $db->emptyToZero($_POST['close_own_tasks']),
                                   $db->emptyToZero($_POST['close_other_tasks']),
                                   $db->emptyToZero($_POST['assign_to_self']),
                                   $db->emptyToZero($_POST['assign_others_to_self']),
                                   $db->emptyToZero($_POST['view_reports']),
                                   $db->emptyToZero($_POST['group_open']),
                                   $_POST['group_id']
                                  )
                            );


      // Get the group definition that this group belongs to
      $group_details = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}groups WHERE group_id = ?", array($_POST['group_id'])));

      $_SESSION['SUCCESS'] = $modify_text['groupupdated'];
      $fs->redirect($_POST['prev_page']);

   } else
   {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupanddesc']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
   }
// End of updating group definition

//////////////////////////////
// Start of updating a list //
//////////////////////////////

} elseif ($_POST['action'] == "update_list"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  $listname = $_POST['list_name'];
  $listposition = $_POST['list_position'];
  $listshow = $_POST['show_in_list'];
  $listdelete = $_POST['delete'];
  $listid = $_POST['id'];

  $redirectmessage = $modify_text['listupdated'];

  for($i = 0; $i < count($listname); $i++) {
      $listname[$i] = stripslashes($listname[$i]);
      if($listname[$i] != ''
          && is_numeric($listposition[$i])
          ) {
          $update = $db->Query("UPDATE $list_table_name SET
                                    $list_column_name = ?,
                                    list_position = ?,
                                    show_in_list = ?
          WHERE $list_id = '{$listid[$i]}'",
          array($listname[$i], $listposition[$i],
                $db->emptyToZero($listshow[$i])
                ));
      }
      else {
          $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
      };
  };

  if (is_array($listdelete)) {
      $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
      $db->Query("DELETE FROM $list_table_name WHERE $deleteids");
  }

   $_SESSION['SUCCESS'] = $redirectmessage;
   $fs->redirect($_POST['prev_page']);


// End of updating a list

/////////////////////////////////
// Start of adding a list item //
/////////////////////////////////

} elseif ($_POST['action'] == "add_to_list"
   && $permissions['manage_project'] == '1')
{

   if (!empty($_POST['list_name'])
    && !empty($_POST['list_position']) )
   {
      // If the user is requesting a project-level addition
      if (!empty($_POST['project_id']))
      {
         $db->Query("INSERT INTO $list_table_name
                     (project_id, $list_column_name, list_position, show_in_list)
                     VALUES (?, ?, ?, ?)",
                     array($_POST['project_id'], $_POST['list_name'], $_POST['list_position'], '1'));

         // Redirect
         $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
         $fs->redirect($_POST['prev_page']);

      // If the user is requesting a global list addition
      } else
      {
         $db->Query("INSERT INTO $list_table_name
                    ($list_column_name, list_position, show_in_list, project_id)
                    VALUES (?, ?, ?, ?)",
                    array($_POST['list_name'], $_POST['list_position'], '1', '0'));

         // Redirect
         $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
         $fs->redirect($_POST['prev_page']);
      }


   } else
   {
      $_SESSION['ERROR'] = $modify_text['fillallfields'];
      $fs->redirect($_POST['prev_page']);
   }
// End of adding a list item

////////////////////////////////////////
// Start of updating the version list //
////////////////////////////////////////

} elseif ($_POST['action'] == "update_version_list"
 && ($permissions['is_admin'] == '1'
     OR $permissions['manage_project'] == '1'))
{

  $listname = $_POST['list_name'];
  $listposition = $_POST['list_position'];
  $listshow = $_POST['show_in_list'];
  $listtense = $_POST['version_tense'];
  $listdelete = $_POST['delete'];
  $listid = $_POST['id'];

  $redirectmessage = $modify_text['listupdated'];

   for($i = 0; $i < count($listname); $i++)
   {
      $listname[$i] = stripslashes($listname[$i]);
      if($listname[$i] != ''
          && is_numeric($listposition[$i]))
      {
         $update = $db->Query("UPDATE $list_table_name SET
                               $list_column_name = ?,
                               list_position = ?,
                               show_in_list = ?,
                               version_tense = ?
                               WHERE $list_id = '{$listid[$i]}'",
                               array($listname[$i], $listposition[$i],
                               $db->emptyToZero($listshow[$i]),
                               $listtense[$i])
                             );
      } else
      {
          $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
      }
   }

  if (is_array($listdelete)) {
      $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
      $db->Query("DELETE FROM $list_table_name WHERE $deleteids");
  }

  $_SESSION['SUCCESS'] = $redirectmessage;
  $fs->redirect($_POST['prev_page']);

// End of updating the version list

/////////////////////////////////////////
// Start of adding a version list item //
/////////////////////////////////////////

} elseif ($_POST['action'] == "add_to_version_list"
          && $permissions['manage_project'] == '1')
{
   if ($_POST['list_name'] != ''
    && $_POST['list_position'] != '')
   {
      $update = $db->Query("INSERT INTO $list_table_name
                           (project_id, $list_column_name, list_position, show_in_list, version_tense)
                           VALUES (?, ?, ?, ?, ?)",
                           array($_POST['project_id'], $_POST['list_name'], $_POST['list_position'], '1', $_POST['version_tense']));

      $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
      $fs->redirect($_POST['prev_page']);

   } else
   {
      $_SESSION['ERROR'] = $modify_text['fillallfields'];
      $fs->redirect($_POST['prev_page']);
   }
// End of adding a version list item



////////////////////////////////////////////
// Start of updating the category list    //
// Category lists are slightly different, //
// requiring their own update section     //
////////////////////////////////////////////

} elseif ($_POST['action'] == "update_category"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  $listname = $_POST['list_name'];
  $listposition = $_POST['list_position'];
  $listshow = $_POST['show_in_list'];
  $listid = $_POST['id'];
  $listowner = $_POST['category_owner'];
  $listdelete = $_POST['delete'];

  $redirectmessage = $modify_text['listupdated'];

  for($i = 0; $i < count($listname); $i++) {
      $listname[$i] = stripslashes($listname[$i]);
      if ($listname[$i] != ''
          && is_numeric($listposition[$i])
          ) {
          $update = $db->Query("UPDATE {$dbprefix}list_category SET
                                    category_name = ?,
                                    list_position = ?,
                                    show_in_list = ?,
                                    category_owner = ?
                              WHERE category_id = ?",
                              array($listname[$i], $listposition[$i],
                              $db->emptyToZero($listshow[$i]),
                              $db->emptyToZero($listowner[$i]),
                              $listid[$i]));
      }
      else {
          $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
      };
  };

  if (is_array($listdelete)) {
      $deleteids = "$list_id = " . join(" OR $list_id =", array_keys($listdelete));
      $db->Query("DELETE FROM {$dbprefix}list_category WHERE $deleteids");
  }

  $_SESSION['SUCCESS'] = $redirectmessage;
  $fs->redirect($_POST['prev_page']);

// End of updating the category list

//////////////////////////////////////////
// Start of adding a category list item //
//////////////////////////////////////////

} elseif ($_POST['action'] == "add_category"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      $update = $db->Query("INSERT INTO {$dbprefix}list_category
                                (project_id, category_name, list_position,
                                show_in_list, category_owner, parent_id)
                                VALUES (?, ?, ?, ?, ?, ?)",
                        array($_POST['project_id'], $_POST['list_name'],
                        $_POST['list_position'],
                        '1',
                        $_POST['category_owner'],
                        $db->emptyToZero($_POST['parent_id'])));

      $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
      $fs->redirect($_POST['prev_page']);

} else {
    $_SESSION['ERROR'] = $modify_text['fillallfields'];
    $fs->redirect($_POST['prev_page']);
};
// End of adding a category list item

//////////////////////////////////////////
// Start of adding a related task entry //
//////////////////////////////////////////

} elseif ($_POST['action'] == 'add_related'
          && ($permissions['modify_all_tasks'] == '1'
               OR ($permissions['modify_own_tasks'] == '1' && $old_details['assigned_to'] == $current_user['user_id']))) {

  if (is_numeric($_POST['related_task'])) {
    $check = $db->Query("SELECT * FROM {$dbprefix}related
        WHERE this_task = ?
        AND related_task = ?",
        array($_POST['this_task'], $_POST['related_task']));
    $check2 = $db->Query("SELECT attached_to_project FROM {$dbprefix}tasks
        WHERE task_id = ?",
        array($_POST['related_task']));

    if ($db->CountRows($check) > 0)
    {
        $_SESSION['ERROR'] = $modify_text['relatederror'];
        $fs->redirect($fs->CreateURL('details', $_POST['this_task']));

    } elseif (!$db->CountRows($check2))
    {
        $_SESSION['ERROR'] = $modify_text['relatedinvalid'];
        $fs->redirect($fs->CreateURL('details', $_POST['this_task']));
    } else
    {
        list($relatedproject) = $db->FetchRow($check2);
        if ($project_id == $relatedproject || isset($_POST['allprojects'])) {
            $insert = $db->Query("INSERT INTO {$dbprefix}related (this_task, related_task) VALUES(?,?)", array($_POST['this_task'], $_POST['related_task']));

            $fs->logEvent($_POST['this_task'], 11, $_POST['related_task']);
            $fs->logEvent($_POST['related_task'], 15, $_POST['this_task']);

            $notify->Create('9', $_POST['this_task']);


            $_SESSION['SUCCESS'] = $modify_text['relatedadded'];
            $fs->redirect($fs->CreateURL('details', $_POST['this_task']));

        } else {
            ?>
            <div class="redirectmessage">
                <p><em><?php echo $modify_text['relatedproject'];?></em></p>
                <form action="index.php" method="post">
                    <input type="hidden" name="do" value="modify">
                    <input type="hidden" name="action" value="add_related">
                    <input type="hidden" name="this_task" value="<?php echo $_POST['this_task'];?>">
                    <input type="hidden" name="related_task" value="<?php echo $_POST['related_task'];?>">
                    <input type="hidden" name="allprojects" value="1">
                    <input class="adminbutton" type="submit" value="<?php echo $modify_text['addanyway'];?>">
                </form>
                <form action="index.php" method="get">
                    <input type="hidden" name="do" value="details">
                    <input type="hidden" name="id" value="<?php echo $_POST['this_task'];?>">
                    <input type="hidden" name="area" value="related">
                    <input class="adminbutton" type="submit" value="<?php echo $modify_text['cancel'];?>">
                </form>
            </div>
            <?php
        };
    };
  } else {
    $_SESSION['ERROR'] = $modify_text['relatedinvalid'];
    $fs->redirect($fs->CreateURL('details', $_POST['this_task']));
  };

// End of adding a related task entry

///////////////////////////////////
// Removing a related task entry //
///////////////////////////////////

} elseif ($_POST['action'] == "remove_related"
          && ($permissions['modify_all_jobs'] == '1'
               OR ($permissions['modify_own_tasks'] == '1'))) { // FIX THIS PERMISSION!!

  $remove = $db->Query("DELETE FROM {$dbprefix}related WHERE related_id = ?", array($_POST['related_id']));

  $fs->logEvent($_POST['id'], 12, $_POST['related_task']);
  $fs->logEvent($_POST['related_task'], 16, $_POST['id']);

  $_SESSION['SUCCESS'] = $modify_text['relatedremoved'];
  $fs->redirect($fs->CreateURL('details', $_POST['id']));

// End of removing a related task entry

/////////////////////////////////////////////////////
// Start of adding a user to the notification list //
/////////////////////////////////////////////////////

} elseif ( isset($_REQUEST['action']) && $_REQUEST['action'] == "add_notification" )
{

   if ( isset($_REQUEST['prev_page']) )
   {
      $ids = $_REQUEST['ids'];
      $tasks = array();
      $redirect_url = $_REQUEST['prev_page'];

      if ( is_array($ids) && !empty($ids) )
      {
         foreach ( $ids AS $key => $val )
            array_push($tasks, $key);

         $be->AddToNotifyList($current_user['user_id'], $tasks);
      } else
      {
         $be->AddToNotifyList($_REQUEST['user_id'], array($_REQUEST['ids']));
      }

   } else
   {
      $be->AddToNotifyList($_REQUEST['user_id'], array($_REQUEST['ids']));
      $redirect_url = $fs->CreateURL('details', $_REQUEST['ids']);
   }

   $_SESSION['SUCCESS'] = $modify_text['notifyadded'];
   $fs->redirect($redirect_url);

// End of adding a user to the notification list

////////////////////////////////////////////
// Start of removing a notification entry //
////////////////////////////////////////////

} elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "remove_notification")
{
   if ( isset($_REQUEST['prev_page']) )
   {
      $ids = $_REQUEST['ids'];
      $tasks = array();
      $redirect_url = $_REQUEST['prev_page'];

      if (!empty($ids))
      {
         foreach ($ids AS $key => $val)
            array_push($tasks, $key);

         $be->RemoveFromNotifyList($current_user['user_id'], $tasks);
      }

   } else
   {
      $be->RemoveFromNotifyList($_REQUEST['user_id'], array($_REQUEST['ids']));
      $redirect_url = $fs->CreateURL('details', $_REQUEST['ids']);
   }

   $_SESSION['SUCCESS'] = $modify_text['notifyremoved'];
   $fs->redirect($redirect_url);

// End of removing a notification entry

////////////////////////////////
// Start of editing a comment //
////////////////////////////////

} elseif ($_POST['action'] == "editcomment"
          && $permissions['edit_comments'] == '1')
{
   $update = $db->Query("UPDATE {$dbprefix}comments
                         SET comment_text = ?  WHERE comment_id = ?",
                         array($_POST['comment_text'], $_POST['comment_id']));

   $fs->logEvent($_POST['task_id'], 5, $_POST['comment_text'], $_POST['previous_text'], $_POST['comment_id']);

   $_SESSION['SUCCESS'] = $modify_text['editcommentsaved'];
   $fs->Redirect($fs->CreateURL('details', $_REQUEST['task_id']));

// End of editing a comment

/////////////////////////////////
// Start of deleting a comment //
/////////////////////////////////

} elseif ($_GET['action'] == "deletecomment"
&& $permissions['delete_comments'] == '1')
{
   $comment = $db->FetchRow($db->Query("SELECT comment_text, user_id, date_added
                                        FROM {$dbprefix}comments
                                        WHERE comment_id = ?",
                                        array($_GET['comment_id'])
                                      )
                           );

   // Check for files attached to this comment
   $check_attachments = $db->Query("SELECT * FROM {$dbprefix}attachments
                                    WHERE comment_id = ?",
                                    array($_REQUEST['comment_id'])
                                  );

   if($db->CountRows($check_attachments) && $permissions['delete_attachments'] != '1')
   {
      $_SESSION['ERROR'] = $modify_text['commentattachperms'];
      $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));

   } else
   {
      $db->Query("DELETE FROM {$dbprefix}comments
                  WHERE comment_id = ?",
                  array($_REQUEST['comment_id'])
                );

      $fs->logEvent($_REQUEST['task_id'], 6, $comment['user_id'], $comment['comment_text'], $comment['date_added']);

      while ($attachment = $db->FetchRow($check_attachments))
      {
         // Delete the attachment
         $db->Query("DELETE from {$dbprefix}attachments
                     WHERE attachment_id = ?",
                     array($attachment['attachment_id'])
                   );

         // Log to task history
         $fs->logEvent($attachment['task_id'], 8, $attachment['orig_name']);
      }

      $_SESSION['SUCCESS'] = $modify_text['commentdeleted'];
      $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));

   // End of permission check
   }

// End of deleting a comment

/////////////////////////////////////
// Start of deleting an attachment //
/////////////////////////////////////

} elseif ($_REQUEST['action'] == 'deleteattachment'
          && $permissions['delete_attachments'] == '1')
{
   // if an attachment needs to be deleted do it right now
   $row = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}attachments
                                      WHERE attachment_id = ?",
                                      array($_REQUEST['id'])
                                    )
                         );

   @unlink("attachments/" . $row['file_name']);
   $db->Query("DELETE FROM {$dbprefix}attachments
               WHERE attachment_id = ?",
               array($_REQUEST['id'])
             );

  $fs->logEvent($row['task_id'], 8, $row['orig_name']);

  $_SESSION['SUCCESS'] = $modify_text['attachmentdeleted'];
  $fs->redirect($fs->CreateURL('details', $row['task_id']));

// End of deleting an attachment

////////////////////////////////
// Start of adding a reminder //
////////////////////////////////

} elseif ($_POST['action'] == "addreminder"
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1')) {

  $now = date('U');

  $how_often = $_POST['timeamount1'] * $_POST['timetype1'];
  //echo "how often = $how_often<br>";
  //echo "now = $now<br>";

  $start_time = ($_POST['timeamount2'] * $_POST['timetype2']) + $now;
  //echo "start time = $start_time";

  $insert = $db->Query("INSERT INTO {$dbprefix}reminders (task_id, to_user_id, from_user_id, start_time, how_often, reminder_message) VALUES(?,?,?,?,?,?)", array($_POST['task_id'], $_POST['to_user_id'], $current_user['user_id'], $start_time, $how_often, $_POST['reminder_message']));

  $fs->logEvent($_POST['task_id'], 17, $_POST['to_user_id']);

  $_SESSION['SUCCESS'] = $modify_text['reminderadded'];
  $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));

// End of adding a reminder

//////////////////////////////////
// Start of removing a reminder //
//////////////////////////////////
} elseif ($_POST['action'] == "deletereminder"
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1')) {

  $reminder = $db->FetchRow($db->Query("SELECT to_user_id FROM {$dbprefix}reminders WHERE reminder_id = ?", array($_POST['reminder_id'])));
  $db->Query("DELETE FROM {$dbprefix}reminders WHERE reminder_id = ?",
                    array($_POST['reminder_id']));

  $fs->logEvent($_POST['task_id'], 18, $reminder['to_user_id']);

  $_SESSION['SUCCESS'] = $modify_text['reminderdeleted'];
  $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));

// End of removing a reminder

/////////////////////////////////////////////////
// Start of adding a bunch of users to a group //
/////////////////////////////////////////////////
} elseif ($_POST['action'] == "addtogroup"
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1')) {

  // If no users were selected, throw an error
   if (!is_array($_POST['user_list']))
   {
      $_SESSION['ERROR'] = $modify_text['nouserselected'];
      $fs->redirect($_POST['prev_page']);

   // If users were select, keep going
   } else {

      // Cycle through the users passed to us
      //while (list($key, $val) = each($_POST['user_list'])) {
      foreach ($_POST['user_list'] AS $key => $val)
      {
         // Create entries for them that point to the requested group
         $create = $db->Query("INSERT INTO {$dbprefix}users_in_groups
                               (user_id, group_id)
                               VALUES(?, ?)",
                               array($val, $_POST['add_to_group'])
                             );
      }

   $_SESSION['SUCCESS'] = $modify_text['groupswitchupdated'];
   $fs->redirect($_POST['prev_page']);

   }

// End of adding a bunch of users to a group


///////////////////////////////////////////////
// Start of change a bunch of users' groups //
//////////////////////////////////////////////
} elseif ($_POST['action'] == 'movetogroup'
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1'))
{
   // Cycle through the array of user ids
   foreach ($_POST['users'] AS $user_id => $val)
   {
      // To be removed from a project entirely
      if ($_POST['switch_to_group'] == '0')
      {
         $db->Query("DELETE FROM {$dbprefix}users_in_groups
                     WHERE user_id = ? AND group_id = ?",
                     array($user_id, $_POST['old_group'])
                   );

      // Otherwise moved to another project/global group
      } else
      {
         $db->Query("UPDATE {$dbprefix}users_in_groups
                     SET group_id = ?
                     WHERE user_id = ? AND group_id = ?",
                     array($_POST['switch_to_group'], $user_id, $_POST['old_group']));
      }
   }

  $_SESSION['SUCCESS'] = $modify_text['groupswitchupdated'];
  $fs->redirect($_POST['prev_page']);

  // End of changing a bunch of users' groups

///////////////////////////////
// Start of taking ownership //
///////////////////////////////

} elseif ($_REQUEST['action'] == 'takeownership')
{
   if ( isset($_REQUEST['prev_page']) )
   {
      $ids = $_REQUEST['ids'];
      $tasks = array();
      $redirect_url = $_REQUEST['prev_page'];

      if (!empty($ids))
      {
         foreach ($ids AS $key => $val)
            array_push($tasks, $key);

         $be->AssignToMe($current_user['user_id'], $tasks);
      }

   } else
   {
      $be->AssignToMe($current_user['user_id'], array($_REQUEST['ids']));
      $redirect_url = $redirect_url = $fs->CreateURL('details', $_REQUEST['ids']);
   }

   $_SESSION['SUCCESS'] = $modify_text['takenownership'];
   $fs->redirect($redirect_url);

// End of taking ownership


//////////////////////////////////////
// Start of requesting task closure //
//////////////////////////////////////

} elseif ($_POST['action'] == 'requestclose')
{
   // Retrieve details on the task we want to close
   $task_details = $fs->GetTaskDetails($_POST['task_id']);

   // Log the admin request
   $fs->AdminRequest(1, $task_details['attached_to_project'], $_POST['task_id'], $current_user['user_id'], $_POST['reason_given']);

   // Log this event to the task history
   $fs->logEvent($_POST['task_id'], 20, $_POST['reason_given']);

   // Now, get the project managers' details for this project
   $get_pms = $db->Query("SELECT u.user_id
                          FROM {$dbprefix}users u
                          LEFT JOIN {$dbprefix}users_in_groups uig ON u.user_id = uig.user_id
                          LEFT JOIN {$dbprefix}groups g ON uig.group_id = g.group_id
                          WHERE g.belongs_to_project = ?
                          AND g.manage_project = '1'",
                          array($project_id)
                        );

   $pms = array();

   // Add each PM to the array
   while ($row = $db->FetchArray($get_pms))
   {
      array_push($pms, $row['user_id']);
   }

   // Call the functions to create the address arrays, and send notifications
   $to  = $notify->SpecificAddresses($pms);
   $msg = $notify->GenerateMsg('12', $_POST['task_id']);
   $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
   $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);

   $_SESSION['SUCCESS'] = $modify_text['adminrequestmade'];
   $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));


// End of requesting task closure

/////////////////////////////////////////
// Start of requesting task re-opening //
/////////////////////////////////////////

} elseif ($_POST['action'] == 'requestreopen')
{
   // Log the admin request
   $fs->AdminRequest(2, $project_id, $_POST['task_id'], $current_user['user_id'], $_POST['reason_given']);

   // Log this event to the task history
   $fs->logEvent($_POST['task_id'], 21, $_POST['reason_given']);

   // Check if the user is on the notification list
   $check_notify = $db->Query("SELECT * FROM {$dbprefix}notifications
                               WHERE task_id = ?
                               AND user_id = ?",
                               array($_POST['task_id'], $current_user['user_id'])
                             );

   if (!$db->CountRows($check_notify))
   {
      // Add the requestor to the task notification list, so that they know when it has been re-opened
      $be->AddToNotifyList($current_user['user_id'], array($_POST['task_id']));

      $fs->logEvent($_POST['task_id'], 9, $current_user['user_id']);
   }

   // Now, get the project managers details for this project
   $get_pms = $db->Query("SELECT u.user_id
                          FROM {$dbprefix}users u
                          LEFT JOIN {$dbprefix}users_in_groups uig ON u.user_id = uig.user_id
                          LEFT JOIN {$dbprefix}groups g ON uig.group_id = g.group_id
                          WHERE g.belongs_to_project = ?
                          AND g.manage_project = '1'",
                          array($project_id)
                        );

   $pms = array();

   // Add each PM to the array
   while ($row = $db->FetchArray($get_pms))
   {
      array_push($pms, $row['user_id']);
   }

   // Call the functions to create the address arrays, and send notifications
   $to  = $notify->SpecificAddresses($pms);
   $msg = $notify->GenerateMsg('12', $_POST['task_id']);
   $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
   $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);


   $_SESSION['SUCCESS'] = $modify_text['adminrequestmade'];
   $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));

// End of requesting task re-opening


///////////////////////////////////
// Start of denying a PM request //
///////////////////////////////////

} elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'denypmreq' && $permissions['manage_project'] == '1')
{
   // Get info on the pm request
   $req_details = $db->FetchArray($db->Query("SELECT task_id
                                              FROM {$dbprefix}admin_requests
                                              WHERE request_id = ?",
                                              array($_REQUEST['req_id'])
                                             )
                                 );

   // Mark the PM request as 'resolved'
   $db->Query("UPDATE {$dbprefix}admin_requests
               SET resolved_by = ?, time_resolved = ?, deny_reason = ?
               WHERE request_id = ?",
               array($current_user['user_id'], date('U'), $_REQUEST['deny_reason'], $_REQUEST['req_id']));


   // Log this event to the task's history
   $fs->logEvent($req_details['task_id'], 28, $_REQUEST['deny_reason']);

   // Send notifications
   $notify->Create('13', $req_details['task_id']);

   // Redirect
   $_SESSION['SUCCESS'] = $modify_text['pmreqdenied'];
   $fs->redirect($_REQUEST['prev_page']);

// End of denying a PM request


//////////////////////////////////
// Start of adding a dependency //
//////////////////////////////////

} elseif ($_POST['action'] == 'newdep'
   && (($permissions['modify_own_tasks'] == '1'
   && $old_details['assigned_to'] == $current_user['user_id'])
   OR $permissions['modify_all_tasks'] == '1')
   && !empty($_POST['dep_task_id']))
{
  // First check that the user hasn't tried to add this twice
  $check_dep = $db->Query("SELECT * FROM {$dbprefix}dependencies
                             WHERE task_id = ? AND dep_task_id = ?",
                             array($_POST['task_id'], $_POST['dep_task_id']));

  // or that they are trying to reverse-depend the same task, creating a mutual-block
  $check_dep2 = $db->Query("SELECT * FROM {$dbprefix}dependencies
                             WHERE task_id = ? AND dep_task_id = ?",
                             array($_POST['dep_task_id'], $_POST['task_id']));

  // Check that the dependency actually exists!
  $check_dep3 = $db->Query("SELECT * FROM {$dbprefix}tasks
                              WHERE task_id = ?",
                              array($_POST['dep_task_id'])
                            );

   $notify->Create('5', $_POST['task_id']);

//    $to  = $notify->Address($_POST['task_id']);
//    $msg = $notify->Create('5', $_POST['task_id']);
//    $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
//    $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);


   if (!$db->CountRows($check_dep)
       && !$db->CountRows($check_dep2)
       && $db->CountRows($check_dep3)
       // Check that the user hasn't tried to add the same task as a dependency
       && $_POST['task_id'] != $_POST['dep_task_id']) {

    // Log this event to the task history, both ways
    $fs->logEvent($_POST['task_id'], 22, $_POST['dep_task_id']);
    $fs->logEvent($_POST['dep_task_id'], 23, $_POST['task_id']);

    // Add the dependency to the database
    $add_dep = $db->Query("INSERT INTO {$dbprefix}dependencies
                             (task_id, dep_task_id)
                             VALUES(?,?)",
                             array($_POST['task_id'], $_POST['dep_task_id']));




    // Redirect
   $_SESSION['SUCCESS'] = $modify_text['dependadded'];
   $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));

  // If the user tried to add the wrong task as a dependency
  } else {

    // If the user tried to add the 'wrong' task as a dependency,
    // show error and redirect
   $_SESSION['ERROR'] = $modify_text['dependaddfailed'];
   $fs->redirect($fs->CreateURL('details', $_REQUEST['task_id']));

  };

// End of adding a dependency


////////////////////////////////////
// Start of removing a dependency //
////////////////////////////////////

} elseif ($_GET['action'] == 'removedep'
          && (($permissions['modify_own_tasks'] == '1'
               && $old_details['assigned_to'] == $current_user['user_id'])
             OR $permissions['modify_all_tasks'] =='1')) {

  // We need some info about this dep for the task history
  $dep_info = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}dependencies
                                          WHERE depend_id = ?",
                                          array($_GET['depend_id'])));

   $notify->Create('6', $dep_info['task_id']);

//    $to  = $notify->Address($dep_info['task_id']);
//    $msg = $notify->Create('6', $dep_info['task_id']);
//    $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
//    $jabb = $notify->StoreJabber($to[1], $msg[0], $msg[1]);

   // Log this event to the task's history
   $fs->logEvent($dep_info['task_id'], 24, $dep_info['dep_task_id']);
   $fs->logEvent($dep_info['dep_task_id'], 25, $dep_info['task_id']);

   // Do the removal
   $remove = $db->Query("DELETE FROM {$dbprefix}dependencies
                         WHERE depend_id = ?",
                         array($_GET['depend_id']));

   // Generate status message and redirect
   $_SESSION['SUCCESS'] = $modify_text['depremoved'];
   $fs->redirect($fs->CreateURL('details', $dep_info['task_id']));

// End of removing a dependency


//////////////////////////////////////////////////
// Start of a user requesting a password change //
//////////////////////////////////////////////////

} elseif ($_POST['action'] == 'sendmagic') {

   // Check that the username exists
   $check_details = $db->Query("SELECT * FROM {$dbprefix}users
                                WHERE user_name = ?",
                                array($_POST['user_name']));

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
      $update = $db->Query("UPDATE {$dbprefix}users
                            SET magic_url = ?
                            WHERE user_id = ?",
                            array($magic_url, $user_details['user_id'])
                          );

      // Create notification message
      $subject = $modify_text['noticefrom'] . ' ' . $project_prefs['project_title'];

      $message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
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

} elseif ($_POST['action'] == 'chpass')
{
   // Check that the user submitted both the fields, and they are the same
   if ($_POST['pass1'] != ''
     && $_POST['pass2'] != ''
     && $_POST['magic_url'] != ''
     && $_POST['pass2'] == $_POST['pass2'])
   {
      // Get the user's details from the magic url
      $user_details = $db->FetchArray($db->Query("SELECT * FROM {$dbprefix}users
                                                  WHERE magic_url = ?",
                                                  array($_POST['magic_url'])
                                                )
                                     );

      // Encrypt the new password
      $new_pass_hash = $fs->cryptPassword($_POST['pass1']);

      // Change the password and clear the magic_url field
      $update = $db->Query("UPDATE {$dbprefix}users SET
                            user_pass = ?,
                            magic_url = ''
                            WHERE magic_url = ?",
                            array($new_pass_hash, $_POST['magic_url'])
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

} elseif ($_GET['action'] == 'makeprivate'
  && $permissions['manage_project'] == '1')
{
   $update = $db->Query("UPDATE {$dbprefix}tasks
                         SET mark_private = '1'
                         WHERE task_id = ?",
                         array($_GET['id'])
                       );

   // Log to task history
   $fs->logEvent($_GET['id'], 26);

   $_SESSION['SUCCESS'] = $modify_text['taskmadeprivate'];
   $fs->redirect($fs->CreateURL('details', $_REQUEST['id']));

// End of making a task private


///////////////////////////////////
// Start of making a task public //
///////////////////////////////////

} elseif ($_GET['action'] == 'makepublic'
  && $permissions['manage_project'] == '1')
{
   $update = $db->Query("UPDATE {$dbprefix}tasks
                         SET mark_private = '0'
                         WHERE task_id = ?",
                         array($_GET['id'])
                       );

   // Log to task history
   $fs->logEvent($_GET['id'], 27);

   $_SESSION['SUCCESS'] = $modify_text['taskmadepublic'];
   $fs->redirect($fs->CreateURL('details', $_REQUEST['id']));

// End of making a task public


/////////////////////
// End of actions! //
/////////////////////
}

?>
