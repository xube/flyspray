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

$list_table_name = "flyspray_list_".addslashes($_POST['list_type']);
$list_column_name = addslashes($_POST['list_type'])."_name";
$list_id = addslashes($_POST['list_type'])."_id";

$now = date(U);

if (!empty($_POST['task_id'])) {
  $old_details = $fs->GetTaskDetails($_POST['task_id']);
}

////////////////////////////////
// Start of adding a new task //
////////////////////////////////

if ($_POST['action'] == 'newtask'
    && ($permissions['open_new_tasks'] == '1'
    OR $project_prefs['anon_open'] == "1")) {

  // If they entered something in both the summary and detailed description
  if ($_POST['item_summary'] != ''
    && $_POST['detailed_desc'] != '')
  {

    $item_summary = $_POST['item_summary'];
    $detailed_desc = $_POST['detailed_desc'];

    $param_names = array('task_type', 'item_status',
        'assigned_to', 'product_category', 'product_version',
        'closedby_version', 'operating_system', 'task_severity',
        'task_priority', 'closure_comment');
    $sql_values = array($_POST['project_id'], $now, $now, $item_summary,
                $detailed_desc,
                $db->emptyToZero($_COOKIE['flyspray_userid']),
                '0');
    $_POST['closure_comment'] = ' ';
    $sql_params = array();
    foreach ($param_names as $param_name) {
        if (!empty($_POST[$param_name])) {
            array_push($sql_params, $param_name);
            array_push($sql_values, $_POST[$param_name]);
        }
    }
    $sql_params = join(', ', $sql_params);
    $sql_placeholder = join(', ', array_fill(1, count($sql_values), '?'));

    $add_item = $db->Query("INSERT INTO flyspray_tasks
    (attached_to_project, date_opened, last_edited_time, item_summary,
    detailed_desc, opened_by, percent_complete, $sql_params)
    VALUES ($sql_placeholder)", $sql_values);

    // Now, let's get the task_id back, so that we can send a direct link
    // URL in the notification message
    $get_task_info = $db->FetchArray($db->Query("SELECT task_id, item_summary FROM flyspray_tasks
                                                WHERE item_summary = ?
                                                AND detailed_desc = ?
                                                ORDER BY task_id DESC",
                                                array($item_summary, $detailed_desc), 1));
    //$task_id = $get_task_info['task_id'];

    // If the reporter wanted to be added to the notification list
    if ($_POST['notifyme'] == '1') {
      $insert = $db->Query("INSERT INTO flyspray_notifications
                              (task_id, user_id)
                               VALUES('{$get_task_info['task_id']}',
                                      '{$current_user['user_id']}')");
      $fs->logEvent($get_task_info['task_id'], 9, $current_user['user_id']);
    };

    // Check if the new task was assigned to anyone
    if ($_POST['assigned_to'] != '' && $_POST['assigned_to'] != '0') {
        $fs->logEvent($get_task_info['task_id'], 14, $_POST['assigned_to'], '0');
        if ($_POST['assigned_to'] != $current_user['user_id']) {

// Create the brief notification message
$subject = "{$modify_text['flyspraytask']} #{$get_task_info['task_id']} - {$get_task_info['item_summary']}";
$message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasopened']}\n
{$modify_text['newtask']}: {$_POST['item_summary']} \n
{$modify_text['moreinfonew']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$get_task_info['task_id']}";

            // ...And send it off to let the person know about their task
            $result = $notify->Basic($_POST['assigned_to'], $subject, $message);
            echo $result;
        };
    };

    // OK, we also need to notify the category owner
    // First, see if there's an owner for this category
    $send_to = '';
    $cat_details = $db->FetchArray($db->Query("SELECT category_name, category_owner, parent_id
                                       FROM flyspray_list_category
                                       WHERE category_id = ?",
                                       array($_POST['product_category'])));

    // If this category has an owner, address the notification to them
    if ($cat_details['category_owner'] != '0') {
      $send_to = $cat_details['category_owner'];
    } elseif ($cat_details['parent_id'] != '0') {
      // If not, see if we can get the parent category owner
      $parent_cat_details = $db->FetchArray($db->Query('SELECT category_owner
                                                   FROM flyspray_list_category
                                                   WHERE category_id = ?',
                                                   array($cat_details['parent_id'])));

      // If there's a parent category owner, send to them
      if ($parent_cat_details['category_owner'] != '0') {
        $send_to = $parent_cat_details['category_owner'];
      };
    };

    // Otherwise send it to the default category owner
    if ($send_to == '') {
        $send_to = $project_prefs['default_cat_owner'];
    };

    // Create the notification message
$subject = "{$modify_text['flyspraytask']} #{$get_task_info['task_id']} - {$get_task_info['item_summary']}";
$message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$modify_text['newtaskcategory']} - \"{$cat_details['category_name']}\"
{$modify_text['categoryowner']}\n
{$modify_text['tasksummary']} {$_POST['item_summary']} \n
{$modify_text['moreinfonew']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$get_task_info['task_id']}";

      // ...And send it off to the category owner or default owner
      if (is_numeric($send_to)) {
        $result = $notify->Basic($send_to, $subject, $message);
      };
      //echo $result;

      $fs->logEvent($get_task_info['task_id'], 1);

?>
      <div class="redirectmessage">
        <p>
          <em><?php echo $modify_text['newtaskadded'];?></em>
        </p>
        <p><?php echo "<a href=\"?do=details&id={$get_task_info['task_id']}\">{$modify_text['gotonewtask']}</a>";?></p>
        <p><?php echo "<a href=\"?do=newtask\">{$modify_text['addanother']}</a>";?></p>
        <p><?php echo "<a href=\"?\">{$modify_text['backtoindex']}</a>";?></p>
      </div>
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
                  && $current_user['user_id'] == $old_details['assigned_to']))) {

  // If they entered something in both the summary and detailed description
  if ($_POST['item_summary'] != ''
    && $_POST['detailed_desc'] != '')
  {

// Check to see if this task has already been modified before we clicked "save"...
// If so, we need to confirm that the we really wants to save our changes
if ($_POST['edit_start_time'] < $old_details['last_edited_time']) {
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
    <input type="text" style="display:none;" name="item_summary" value="<?php echo htmlspecialchars($_POST['item_summary']);?>">
    <textarea style="display:none" name="detailed_desc"><?php echo htmlspecialchars($_POST['detailed_desc']);?></textarea>

    <input type="hidden" name="item_status" value="<?php echo $_POST['item_status'];?>">
    <input type="hidden" name="assigned_to" value="<?php echo $_POST['assigned_to'];?>">
    <input type="hidden" name="product_category" value="<?php echo $_POST['product_category'];?>">
    <input type="hidden" name="closedby_version" value="<?php echo $_POST['closedby_version'];?>">
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

    $old_details_history = $db->FetchRow($db->Query("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));

    $item_summary = $_POST['item_summary'];
    $detailed_desc = $_POST['detailed_desc'];

// A bit dodgy, part 2.
    if ($_POST['edit_start_time'] == "999999999999") {
      $item_summary = stripslashes($_POST['item_summary']);
      $detailed_desc = stripslashes($_POST['detailed_desc']);
    }

    $add_item = $db->Query("UPDATE flyspray_tasks SET
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
                  percent_complete = ?

                  WHERE task_id = ?
                ", array($_POST['attached_to_project'], $_POST['task_type'],
                    $item_summary, $detailed_desc, $_POST['item_status'],
                    $_POST['assigned_to'], $_POST['product_category'],

                    $db->emptyToZero($_POST['closedby_version']),
                    $_POST['operating_system'], $_POST['task_severity'],
                    $_POST['task_priority'], $_COOKIE['flyspray_userid'],
                    $now,
                    $_POST['percent_complete'],
                    $_POST['task_id']
                ));

    // Get the details of the task we just updated
    // To generate the changed-task message
    $new_details = $fs->GetTaskDetails($_POST['task_id']);
    $new_details_history = $db->FetchRow($db->Query("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));

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
            );

    while (list($key, $val) = each($field)) {
      if ($old_details[$val] != $new_details[$val]) {
        //$message = $message . "** " . $key . " " . stripslashes($new_details[$val]) . "\n";
        $send_me = "YES";
      } else {
        //$message = $message . $key . " " . stripslashes($new_details[$val]) . "\n";
      };
    };

    // Log the changed fields in the task history
    while (list($key, $val) = each($old_details_history)) {
        if ($key != 'last_edited_time' && $key != 'last_edited_by' && $key != 'assigned_to'
            && !is_numeric($key)
            && $old_details_history[$key] != $new_details_history[$key]) {
            $fs->logEvent($_POST['task_id'], 0, $new_details_history[$key], $old_details_history[$key], $key);
        };
    };

// Complete the modification notification
/*$item_summary = stripslashes($_POST['item_summary']);
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$message = "{$modify_text['messagefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasjustmodified']} {$modify_text['youonnotify']}
{$modify_text['changedfields']}\n-----\n"
. $message .
"-----\n{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}\n\n";
*/
      if ($send_me == "YES")
         $notify->Create('2', $_POST['task_id']);

    // Check to see if the assignment has changed
    // Because we have to send a simple notification or two
    if ($_POST['old_assigned'] != $_POST['assigned_to']) {
/*
      $item_summary = stripslashes($_POST['item_summary']);

      // If someone had previously been assigned this item, notify them of the change in assignment
      if ($_POST['old_assigned'] != "0" && ($_POST['old_assigned'] != $_COOKIE['flyspray_userid'])) {

        if ($_POST['assigned_to'] == "0") {
          $new_realname = $modify_text['noone'];
          $new_username = $modify_text['unassigned'];
        };

        // Generate the brief notification message to send
        $get_new = $db->FetchArray($db->Query("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($_POST['assigned_to'])));

        if ($get_new['user_name'] != '') {
          $new_username = $get_new['user_name'];
        } else {
          $new_username = "No-one";
        };

        // Create a notification message
        $item_summary = stripslashes($_POST['item_summary']);
        $subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
        $message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
        {$modify_text['nolongerassigned']} {$get_new['real_name']} ($new_username).\n
        {$modify_text['task']} #{$_POST['task_id']} - {$_POST['item_summary']}\n
        {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";
        // End of generating a message

        // Send the brief notification message
        $result = $notify->Basic($_POST['old_assigned'], $subject, $message);
        echo $result;
      };

      // If assignment isn't "none", notify the new assignee of their task
      if ($_POST['assigned_to'] != "0" && ($_POST['assigned_to'] != $_COOKIE['flyspray_userid'])) {

        // Get the brief notification message to send
        $subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - {$_POST['item_summary']}";
        $message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
        {$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasassigned']}\n
        {$modify_text['task']} #{$_POST['task_id']}: {$_POST['item_summary']} \n
        {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";

        // Send the brief notification message
        $result = $notify->Basic($_POST['assigned_to'], $subject, $message);
        echo $result;

      };
*/
      $fs->logEvent($_POST['task_id'], 14, $_POST['assigned_to'], $_POST['old_assigned']);

    };

    $_SESSION['SUCCESS'] = $modify_text['taskupdated'];
    header("Location: index.php?do=details&id=" . $_POST['task_id']);

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

} elseif($_POST['action'] == "close"
         && ($permissions['close_other_tasks'] == '1'
         OR ($permissions['close_own_tasks'] == '1'
             && $old_details['assigned_to'] == $current_user['user_id']))
         ) {

  if (!empty($_POST['resolution_reason'])) {

    $close_item = $db->Query("UPDATE flyspray_tasks SET
                                date_closed = ?,
                                closed_by = ?,
                                closure_comment = ?,
                                is_closed = '1',
                                resolution_reason = ?
                                WHERE task_id = ?
                                ", array($now,
                                         $_COOKIE['flyspray_userid'],
                                         $db->emptyToZero($_POST['closure_comment']),
                                         $_POST['resolution_reason'],
                                         $_POST['task_id']
                                        )
                             );

    // Get the resolution name for the notifications
    $get_res = $db->FetchArray($db->Query("SELECT resolution_name FROM flyspray_list_resolution WHERE resolution_id = ?", array($_POST['resolution_reason'])));

    // Get the item summary for the notifications
    list($item_summary) = $db->FetchArray($db->Query("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));
    $item_summary = stripslashes($item_summary);

    if ($_COOKIE['flyspray_userid'] != $_POST['assigned_to']) {

// Create a basic notification message
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$brief_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasclosedassigned']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['reasonforclosing']} {$get_res['resolution_name']} \n";

if($_POST['closure_comment'] != '') {
   $brief_message = $brief_message . "\n {$modify_text['closurecomment']} {$_POST['closure_comment']}\n";
};

$brief_message = $brief_message . "\n{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']} \n";



      $result = $notify->Basic($_POST['assigned_to'], $subject, $brief_message);
      echo $result;

    };
// Create a detailed notification message
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$detailed_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasclosed']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['reasonforclosing']} {$get_res['resolution_name']} \n";

if($_POST['closure_comment'] != '') {
   $detailed_message = $detailed_message . "{$modify_text['closurecomment']} {$_POST['closure_comment']}\n";
};
$detailed_message = $detailed_message . "\n{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']} \n";

      $result = $notify->Detailed($_POST['task_id'], $subject, $detailed_message);
      echo $result;

    // Log this to the task's history
    $fs->logEvent($_POST['task_id'], 2, $_POST['resolution_reason'], $_POST['closure_comment']);

    // If there's an admin request related to this, close it
    if ($fs->AdminRequestCheck(1, $_POST['task_id']) == '1') {
      $db->Query("UPDATE flyspray_admin_requests
                    SET resolved_by = ?, time_resolved = ?
                    WHERE task_id = ? AND request_type = ?",
                    array($current_user['user_id'], date(U), $_POST['task_id'], 1));
    };

    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['taskclosed']}</em></p>";
    //echo "<p><a href=\"?do=details&amp;id={$_POST['task_id']}\">{$modify_text['returntotask']}</a></p>";
    //echo "<p><a href=\"?\">{$modify_text['backtoindex']}</a></p></div>";

    $_SESSION['SUCCESS'] = $modify_text['taskclosed'];
    header("Location: index.php?do=details&id=" . $_POST['task_id']);

} else {
    $_SESSION['ERROR'] = $modify_text['noclosereason'];
    Header("Location: index.php?do=details&id=" . $_POST['task_id']);
    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['noclosereason']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };

// End of closing a task

/////////////////////////////////
// Start of re-opening an task //
/////////////////////////////////

} elseif ($_POST['action'] == "reopen"
          && $permissions['manage_project'] == "1") {

    $add_item = $db->Query("UPDATE flyspray_tasks SET
                              item_status = '7',
                              resolution_reason = '1',
                              closure_comment = ' ',
                              is_closed = '0'
                              WHERE task_id = ?",
                              array($_POST['task_id']));

    // Find out the user who closed this
    list($item_summary, $closed_by) = $db->FetchArray($db->Query("SELECT item_summary, closed_by FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));

    $item_summary = stripslashes($item_summary);

    if ($closed_by != $_COOKIE['flyspray_userid']) {

      // Generate basic notification message to send
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$brief_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasreopened']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";


      $result = $notify->Basic($closed_by, $subject, $brief_message);
      echo $result;

    };

// Generate detailed notification message to send
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$detailed_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasreopened']} {$modify_text['youonnotify']} \n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";


      $result = $notify->Detailed($_POST['task_id'], $subject, $detailed_message);
      echo $result;

    // If there's an admin request related to this, close it
    if ($fs->AdminRequestCheck(2, $_POST['task_id']) == '1') {
      $db->Query("UPDATE flyspray_admin_requests
                    SET resolved_by = ?, time_resolved = ?
                    WHERE task_id = ? AND request_type = ?",
                    array($current_user['user_id'], date(U), $_POST['task_id'], 2));
    };

    $fs->logEvent($_POST['task_id'], 13);

    $_SESSION['SUCCESS'] = $modify_text['taskreopened'];
    header("Location: index.php?do=details&id=" . $_POST['task_id']);
    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['taskreopened']}</em></p><p><a href=\"?do=details&amp;id={$_POST['task_id']}\">{$modify_text['backtotask']}</a></p></div>";

// End of re-opening an item

///////////////////////////////
// Start of adding a comment //
///////////////////////////////

} elseif ($_POST['action'] == 'addcomment'
          && $permissions['add_comments'] == '1')
{

   if (!empty($_POST['comment_text']))
   {
      $comment = $_POST['comment_text'];

      $insert = $db->Query("INSERT INTO flyspray_comments
                           (task_id, date_added, user_id, comment_text) VALUES
                           ( ?, ?, ?, ? )",
                           array($_POST['task_id'], $now, $_COOKIE['flyspray_userid'], $comment));


      $to  = $notify->Address($_POST['task_id']);
      $msg = $notify->Create('6', $_POST['task_id']);
      $mail = $notify->SendEmail($to[0], $msg[0], $msg[1]);
      $jabb = $notify->SendJabber($to[1], $msg[0], $msg[1]);

      /*$email = $to[0];
      foreach ($email as $key => $val)
      {
         echo $key . ' - ' . $val . '<br />';
      }

      $jabber = $to[1];
      foreach ($jabber as $key => $val)
      {
         echo $key . ' - ' . $val . '<br />';
      }

      echo $msg[0] . ' - ';
      echo $msg[1];
*/
      //echo $to['0'] . ' - ';
      //echo $to['1'] . ' - ';
      //echo $to['2'] . ' - ';

      $row = $db->FetchRow($db->Query("SELECT comment_id FROM flyspray_comments WHERE task_id = ? ORDER BY comment_id DESC", array($_POST['task_id']), 1));
      $fs->logEvent($_POST['task_id'], 4, $row['comment_id']);


      $_SESSION['SUCCESS'] = $modify_text['commentadded'];
      header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=comments#tabs");

   // If they pressed submit without actually typing anything
   } else
   {
      $_SESSION['ERROR'] = $modify_text['nocommententered'];
      header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=comments#tabs");
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
      $check_username = $db->Query("SELECT * FROM flyspray_users WHERE user_name = ?",
                                     array($_POST['user_name']));
      if ($db->CountRows($check_username))
      {
        echo "<p class=\"admin\">{$register_text['usernametaken']}<br>";
        echo "<a href=\"javascript:history.back();\">{$register_text['goback']}</a></p>";
      } else
      {
         // Delete registration codes older than 24 hours
         $now = date(U);
         $yesterday = $now - '86400';
         $remove = $db->Query("DELETE FROM flyspray_registrations WHERE reg_time < ?",
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
         $save_code = $db->Query("INSERT INTO flyspray_registrations
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

$message = "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}\n
{$modify_text['addressused']}\n
{$flyspray_prefs['base_url']}index.php?do=register&amp;magic=$magic_url \n
{$modify_text['confirmcodeis']}\n
{$confirm_code}";

      // Check how they want to receive their code
      if ($_POST['notify_type'] == '1') {

      $notify->SendEmail(
                      $_POST['email_address'],
                      "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}",
                      $message
                      );

      } elseif ($_POST['notify_type'] == '2') {
         $notify->SendJabber($_POST['jabber_id'], "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}", $message);

      };

      // Let the user know what just happened
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['codesent']}</em></p></div>";

    // End of checking if the username is available
    };

  // If the form wasn't filled out correctly, show an error
  } else {

    // Error!
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['erroronform']}</em></p>";
    echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

  // End of checking that the form was completed correctly
  };


// End of sending a new user a confirmation code


//////////////////////////////////////////////////////////////////
// Start of new user self-registration with a confirmation code //
//////////////////////////////////////////////////////////////////

} elseif ($_POST['action'] == "registeruser" && $flyspray_prefs['anon_reg'] == '1') {

  // If they filled in all the required fields
  if ($_POST['user_pass'] != ''
    && $_POST['user_pass2'] != ''
    ) {

      // If the passwords matched
      if (($_POST['user_pass'] == $_POST['user_pass2'])
           && $_POST['user_pass'] != ''
           && $_POST['confirmation_code'] != '') {


        // Check that the user entered the right confirmation code
        $code_check = $db->Query("SELECT * FROM flyspray_registrations WHERE magic_url = ?", array($_POST['magic_url']));
        $reg_details = $db->FetchArray($code_check);

        // If the code is correct
        if ($reg_details['confirm_code'] == $_POST['confirmation_code']) {

          // Encrypt their password
          $pass_hash = crypt("{$_POST['user_pass']}", '4t6dcHiefIkeYcn48B');

          // Add the user to the database
          $add_user = $db->Query("INSERT INTO flyspray_users
                                      (user_name,
                                       user_pass,
                                       real_name,
                                       jabber_id,
                                       email_address,
                                       notify_type,
                                       account_enabled)
                                       VALUES(?, ?, ?, ?, ?, ?, ?)",
                                       array($reg_details['user_name'],
                                             $pass_hash,
                                             $reg_details['real_name'],
                                             $reg_details['jabber_id'],
                                             $reg_details['email_address'],
                                             $reg_details['notify_type'],
                                             '1')
                                   );


        // Get this user's id for the record
        $user_details = $db->FetchArray($db->Query("SELECT * FROM flyspray_users WHERE user_name = ?", array($reg_details['user_name'])));

        // Now, create a new record in the users_in_groups table
        $set_global_group = $db->Query("INSERT INTO flyspray_users_in_groups
                                          (user_id,
                                          group_id)
                                          VALUES(?, ?)",
                                          array($user_details['user_id'], $flyspray_prefs['anon_group']));

          // Let the user know what just happened
          echo "<div class=\"redirectmessage\"><p><em>{$modify_text['accountcreated']}</em></p>";
          echo "<p>{$modify_text['loginbelow']}</p>";
          echo "<p>{$modify_text['newuserwarning']}</p></div>";


        // If they didn't enter the right confirmation code
        } else {
          echo "<div class=\"redirectmessage\"><p><em>{$modify_text['confirmwrong']}</em></p>";
          echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
        };


      // If passwords didn't match
      } else {
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
           && ($permissions['is_admin'] == '1'
                OR ($flyspray_prefs['anon_reg'] == '1'
                    && $flyspray_prefs['spam_proof'] == '1'))) {

  // If they filled in all the required fields
  if ($_POST['user_name'] != ""
    && $_POST['user_pass'] != ""
    && $_POST['user_pass2'] != ""
    && $_POST['real_name'] != ""
    && $_POST['email_address'] != ""
    ) {

    // Check to see if the username is available
    $check_username = $db->Query("SELECT * FROM flyspray_users WHERE user_name = ?", array($_POST['user_name']));
    if ($db->CountRows($check_username)) {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['usernametaken']}</em></p>";
      echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    } else {

      // If the passwords matched, add the user
      if (($_POST['user_pass'] == $_POST['user_pass2']) && $_POST['user_pass'] != '') {

        $pass_hash = crypt("{$_POST['user_pass']}", '4t6dcHiefIkeYcn48B');

        if ($permissions['is_admin'] == '1') {
          $group_in = $_POST['group_in'];
        } else {
          $group_in = $flyspray_prefs['anon_group'];
        };

        $add_user = $db->Query("INSERT INTO flyspray_users
                                    (user_name,
                                     user_pass,
                                     real_name,
                                     jabber_id,
                                     email_address,
                                     notify_type,
                                     account_enabled)
                                     VALUES( ?, ?, ?, ?, ?, ?, ?)",
                                    array($_POST['user_name'],
                                          $pass_hash,
                                          $_POST['real_name'],
                                          $_POST['jabber_id'],
                                          $_POST['email_address'],
                                          $_POST['notify_type'],
                                          '1')
                                 );

        // Get this user's id for the record
        $user_details = $db->FetchArray($db->Query("SELECT * FROM flyspray_users WHERE user_name = ?", array($_POST['user_name'])));

        // Now, create a new record in the users_in_groups table
        $set_global_group = $db->Query("INSERT INTO flyspray_users_in_groups
                                          (user_id,
                                          group_id)
                                          VALUES( ?, ?)",
                                          array($user_details['user_id'], $group_in));

        //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['newusercreated']}</em></p>";

        if ($permissions['is_admin'] != '1') {
          echo "<p>{$modify_text['loginbelow']}</p>";
          echo "<p>{$modify_text['newuserwarning']}</p></div>";
        } else {
          $_SESSION['SUCCESS'] = $modify_text['newusercreated'];
          header("Location: index.php?do=admin&area=groups");
        };


      } else {
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['nomatchpass']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
      };

    };

  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };

// End of adding a new user by an admin

/////////////////////////////////
// Start of adding a new group //
/////////////////////////////////

} elseif ($_POST['action'] == "newgroup"
          && (($_POST['belongs_to_project'] == '0' && $permissions['is_admin'] == '1')
          OR $permissions['manage_project'] == '1')) {

  // If they filled in all the required fields
  if ($_POST['group_name'] != ""
    && $_POST['group_desc'] != ""
    ) {

    // Check to see if the group name is available
    $check_groupname = $db->Query("SELECT * FROM flyspray_groups WHERE group_name = ? AND belongs_to_project = ?", array($_POST['group_name'], $project_id));
    if ($db->CountRows($check_groupname)) {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupnametaken']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    } else
    {
       $add_group = $db->Query("INSERT INTO flyspray_groups
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
                $db->emptyToZero($project_id),
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
                $db->emptyToZero($_POST['group_open'])
                ));

         $_SESSION['SUCCESS'] = $modify_text['newgroupadded'];
         if ($project_id == '0')
         {
            header("Location: index.php?do=admin&area=groups");
         } else
         {
            header("Location: index.php?do=pm&area=groups&project=" . $project_id);
         }
    };

  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };
// End of adding a new group

///////////////////////////////////////////////
// Update the global application preferences //
///////////////////////////////////////////////

} elseif ($_POST['action'] == "globaloptions"
          && $permissions['is_admin'] == '1') {

//  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'anon_open'", array($_POST['anon_open']));
//  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = '{$_POST['theme_style']}' WHERE pref_name = 'theme_style'");
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_server'", array($_POST['jabber_server']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_port'", array($_POST['jabber_port']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_username'", array($_POST['jabber_username']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_password'", array($_POST['jabber_password']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'anon_group'", array($_POST['anon_group']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'base_url'", array($_POST['base_url']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'user_notify'", array($_POST['user_notify']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'admin_email'", array($_POST['admin_email']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'lang_code'", array($_POST['lang_code']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'spam_proof'", array($_POST['spam_proof']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'default_project'", array($_POST['default_project']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'dateformat'", array($_POST['dateformat']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'dateformat_extended'", array($_POST['dateformat_extended']));
  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'anon_reg'", array($_POST['anon_reg']));

  // This is an overly complex way to ensure that we always get the right amount of posted
  // results from the assigned_groups preference
  $get_groups = $db->Query("SELECT * FROM flyspray_groups ORDER BY group_id ASC");
  $group_number = '1';

  while ($row = $db->FetchArray($get_groups)) {
    $posted_group = "assigned_groups" . $group_number;

    if (!isset($first_done)) {
      $assigned_groups = $_POST[$posted_group];
    } else {
      $assigned_groups = $assigned_groups . " $_POST[$posted_group]";
    };
    $first_done = '1';
    $group_number ++;
  };

  $update = $db->Query("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'assigned_groups'", array($assigned_groups));

  $_SESSION['SUCCESS'] = $modify_text['optionssaved'];
  header("Location: index.php?do=admin&area=prefs");

// End of updating application preferences

///////////////////////////////////
// Start of adding a new project //
///////////////////////////////////

} elseif ($_POST['action'] == "newproject"
          && $permissions['is_admin'] == '1') {

  if ($_POST['project_title'] != '') {

    $insert = $db->Query("INSERT INTO flyspray_projects
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

    $newproject = $db->FetchArray($db->Query("SELECT project_id FROM flyspray_projects ORDER BY project_id DESC", false, 1));

      $add_group = $db->Query("INSERT INTO flyspray_groups
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

    $insert = $db->Query("INSERT INTO flyspray_list_category
                             (project_id, category_name, list_position,
                             show_in_list, category_owner)
                             VALUES ( ?, ?, ?, ?, ?)",
                             array($newproject['project_id'],
                            'Backend / Core', '1', '1', '0'));

    $insert = $db->Query("INSERT INTO flyspray_list_os
                             (project_id, os_name, list_position,
                             show_in_list)
                             VALUES (?,?,?,?)",
                             array($newproject['project_id'], 'All', '1', '1'));

    $insert = $db->Query("INSERT INTO flyspray_list_version
                             (project_id, version_name, list_position,
                             show_in_list, version_tense)
                             VALUES (?, ?, ?, ?, ?)",
                        array($newproject['project_id'], '1.0', '1', '1', '2'));

    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['projectcreated']}";
    echo "<br><br><a href=\"?do=pm&amp;area=prefs&amp;project={$newproject['project_id']}\">{$modify_text['customiseproject']}</a></em></p></div>";

  } else {

    echo "<div class=\"errormessage\"><p><em>{$modify_text['emptytitle']}</em></p></div>";

  };

// End of adding a new project

///////////////////////////////////////////
// Start of updating project preferences //
///////////////////////////////////////////

} elseif ($_POST['action'] == "updateproject"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  if ($_POST['project_title'] != '') {

    $update = $db->Query("UPDATE flyspray_projects SET
                             project_title = ?,
                             theme_style = ?,
                             show_logo = ?,
                             inline_images = ?,
                             default_cat_owner = ?,
                             intro_message = ?,
                             project_is_active = ?,
                             others_view = ?,
                             anon_open = ?
                             WHERE project_id = ?
                          ", array($_POST['project_title'],
                                    $_POST['theme_style'],
                                    $db->emptyToZero($_POST['show_logo']),
                                    $db->emptyToZero($_POST['inline_images']),
                                    $db->emptyToZero($_POST['default_cat_owner']),
                                    $_POST['intro_message'],
                                    $db->emptyToZero($_POST['project_is_active']),
                                    $db->emptyToZero($_POST['others_view']),
                                    $db->emptyToZero($_POST['anon_open']),
                                    $_POST['project_id']));

    $_SESSION['SUCCESS'] = $modify_text['projectupdated'];
    header("Location: index.php?do=pm&area=prefs&project=" . $project_id);

  } else {

    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['emptytitle']}</em></p></div>";

  };

  // Process the list of visible columns
  $columnnames = array('id','project','category','tasktype','severity','priority','summary','dateopened','status','openedby','assignedto','lastedit','reportedin','dueversion','comments','attachments','progress');
  foreach ($columnnames AS $column)
  {
    $colname = "visible_columns".$column;
    if($_POST[$colname])
    {
      $columnlist .= "$column ";
    }
  }
  $update = $db->Query("UPDATE flyspray_projects SET visible_columns = ? WHERE project_id = ?", array($columnlist, $_POST['project_id']));


// End of updating project preferences

//////////////////////////////////////
// Start of uploading an attachment //
//////////////////////////////////////

} elseif ($_POST['action'] == "addattachment"
          && $permissions['create_attachments'] == '1') {

     // This function came from the php function page for mt_srand()
     // seed with microseconds to create a random filename
       function make_seed() {
          list($usec, $sec) = explode(' ', microtime());
          return (float) $sec + ((float) $usec * 100000);
       }
       mt_srand(make_seed());
       $randval = mt_rand();
       $file_name = $_POST['task_id']."_$randval";

  // If there is a file attachment to be uploaded, upload it
  if ($_FILES['userfile']['name']) {

    // Then move the uploaded file into the attachments directory and remove exe permissions
    @move_uploaded_file($_FILES['userfile']['tmp_name'], "attachments/$file_name");
    @chmod("attachments/$file_name", 0644);

    // Only add the listing to the database if the file was actually uploaded successfully
    if (file_exists("attachments/$file_name")) {

      $file_desc = $_POST['file_desc'];
      $add_to_db = $db->Query("INSERT INTO flyspray_attachments
                        (task_id, orig_name, file_name, file_desc,
                        file_type, file_size, added_by, date_added)
                        VALUES ( ?, ?, ?, ?, ?, ?, ?, ?)",
                        array(        $_POST['task_id'],
                                $_FILES['userfile']['name'],
                                $file_name, $file_desc,
                                $_FILES['userfile']['type'],
                                $_FILES['userfile']['size'],
                                $_COOKIE['flyspray_userid'],
                                $now));

      $getdetails = $db->Query("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id']));
      $task_details = $db->FetchArray($getdetails);

      $item_summary = stripslashes($task_details['item_summary']);

      if ($task_details['assigned_to'] != "0"
         && ($task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
         ) {

$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$basic_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasuploaded']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs";


        $result = $notify->Basic($task_details['assigned_to'], $subject, $basic_message);
        echo $result;

      };

$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$detailed_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$current_user['real_name']} ({$current_user['user_name']}) {$modify_text['hasattached']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['filename']} {$_FILES['userfile']['name']}
{$modify_text['description']} $file_desc \n
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs";


        $result = $notify->Detailed($_POST['task_id'], $subject, $detailed_message);
        echo $result;

        $row = $db->FetchRow($db->Query("SELECT attachment_id FROM flyspray_attachments WHERE task_id = ? ORDER BY attachment_id DESC", array($_POST['task_id']), 1));
        $fs->logEvent($_POST['task_id'], 7, $row['attachment_id']);

      // Success message!
      //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs\">";
      //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fileuploaded']}</em></p?<p>{$modify_text['waitwhiletransfer']}</p></div>";
      $_SESSION['SUCCESS'] = $modify_text['fileuploaded'];
      header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=attachments#tabs");

    // If the file didn't actually get saved, better show an error to that effect
    } else {
      //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fileerror']}</em></p><p>{$modify_text['contactadmin']}</p></div>";
      $_SESSION['ERROR'] = $modify_text['fileerror'];
      header("Location: ?do=details&id=" . $_POST['task_id'] . "&area=attachments#tabs");
    };

  // If there wasn't a file uploaded with a description, show an error
  } else {
    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['selectfileerror']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    $_SESSION['ERROR'] = $modify_text['selectfileerror'];
    header("Location: ?do=details&id=" . $_POST['task_id'] . "&area=attachments#tabs");
  };
// End of uploading an attachment

/////////////////////////////////////
// Start of modifying user details //
/////////////////////////////////////

} elseif ($_POST['action'] == "edituser"
          && ($permissions['is_admin'] == '1'
              OR ($current_user['user_id'] == $_POST['user_id']))) {

   // If they filled in all the required fields
   if (!empty($_POST['real_name'])
       && (!empty($_POST['email_address'])
          OR !empty($_POST['jabber_id']))
      )
   {
      //If the user entered matching password and confirmation
      //we can change the selected user's password
      $password_problem = false;
      if ($_POST['changepass']
        || $_POST['confirmpass']
        ) {
          //check that the entered passwords match
          if ($_POST['changepass'] == $_POST['confirmpass']) {
            $new_pass = $_POST['changepass'];
            $new_pass_hash = crypt("$new_pass", '4t6dcHiefIkeYcn48B');
            $update_pass = $db->Query("UPDATE flyspray_users SET user_pass = '$new_pass_hash' WHERE user_id = ?", array($_POST['user_id']));

            // If the user is changing their password, better update their cookie hash
            if ($_COOKIE['flyspray_userid'] == $_POST['user_id']) {
              setcookie('flyspray_passhash', crypt("$new_pass_hash", $cookiesalt), time()+60*60*24*30, "/");
            };
          } else {
            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['passnomatch']}</em></p>";
            echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
            $password_problem = true;
          };
      };

      if ($password_problem == false)
      {
         $update = $db->Query("UPDATE flyspray_users SET
                                 real_name = ?,
                                 email_address = ?,
                                 jabber_id = ?,
                                 notify_type = ?,
                                 dateformat = ?,
                                 dateformat_extended = ?
                                 WHERE user_id = ?",
                                 array(
                                       $_POST['real_name'],
                                       $_POST['email_address'],
                                       $_POST['jabber_id'],
                                       $db->emptyToZero($_POST['notify_type']),
                                       $_POST['dateformat'],
                                       $_POST['dateformat_extended'],
                                       $_POST['user_id']
                                      )
                              );

         if ($permissions['is_admin'] == '1' && !empty($_POST['group_in']))
         {
            $update = $db->Query("UPDATE flyspray_users SET
                                  account_enabled = ?
                                  WHERE user_id = ?",
                                  array(
                                        $db->emptyToZero($_POST['account_enabled']),
                                        $_POST['user_id']
                                        )
                                );

            $update = $db->Query("UPDATE flyspray_users_in_groups SET
                                  group_id = ?
                                  WHERE record_id = ?",
                                  array($_POST['group_in'], $_POST['record_id'])
                              );

         }

         $_SESSION['SUCCESS'] = $modify_text['userupdated'];
         header("Location: " . $_POST['prev_page']);
      };

   } else
   {
      $_SESSION['ERROR'] = $modify_text['realandnotify'];
      header("Location: " . $_POST['prev_page']);
   }
   // End of modifying user details

//////////////////////////////////////////
// Start of updating a group definition //
//////////////////////////////////////////

} elseif ($_POST['action'] == "editgroup"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  if ($_POST['group_name'] != ''
    && $_POST['group_desc'] != ''
    ) {
      $update = $db->Query("UPDATE flyspray_groups SET
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
    $group_details = $db->FetchArray($db->Query("SELECT * FROM flyspray_groups WHERE group_id = ?", array($_POST['group_id'])));

    //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=users&amp;project={$group_details['belongs_to_project']}\">";
    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupupdated']}</em></p></div>";
    $_SESSION['SUCCESS'] = $modify_text['groupupdated'];
    header("Location: " . $_POST['prev_page']);
  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupanddesc']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };
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

  if($_POST['project_id'] != '') {
      header("Location: index.php?do=admin&area=projects&id=" . $_POST['project_id'] . "&show=" . $_POST['list_type']);
      //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;show={$_POST['list_type']}&amp;id={$_POST['project_id']}\">";
  } else {
      header("Location: " . $_POST['prev_page']);

  };
  $_SESSION['SUCCESS'] = $redirectmessage;
  //echo "<div class=\"redirectmessage\"><p><em>{$redirectmessage}</em></p></div>";

// End of updating a list

/////////////////////////////////
// Start of adding a list item //
/////////////////////////////////

} elseif ($_POST['action'] == "add_to_list"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      // If the user is requesting a project-level addition
      if ($_POST['project_id'] != '') {

      $update = $db->Query("INSERT INTO $list_table_name
                        (project_id, $list_column_name, list_position, show_in_list)
                        VALUES (?, ?, ?, ?)",
                array($_POST['project_id'], $_POST['list_name'], $_POST['list_position'], '1'));

        $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
        header("Location: " . $_POST['prev_page']);

     // If the user is requesting a global list update
     } else {

      $update = $db->Query("INSERT INTO $list_table_name
                                ($list_column_name, list_position, show_in_list)
                                VALUES (?, ?, ?)",
                array($_POST['list_name'], $_POST['list_position'], '1'));

         $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
         header("Location: " . $_POST['prev_page']);

         //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area={$_POST['list_type']}\">";

      };


    } else {

      $_SESSION['ERROR'] = $modify_text['fillallfields'];

      if ($_POST['project_id'] == '') {
         header("Location: index.php?do=admin&area=" . $_POST['list_type']);
      } else {
         header("Location: " . $_POST['prev_page']);
      };

      //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fillallfields']}</em></p></div>";
  };
// End of adding a list item

////////////////////////////////////////
// Start of updating the version list //
////////////////////////////////////////

} elseif ($_POST['action'] == "update_version_list"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  $listname = $_POST['list_name'];
  $listposition = $_POST['list_position'];
  $listshow = $_POST['show_in_list'];
  $listtense = $_POST['version_tense'];
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
                                    show_in_list = ?,
                                    version_tense = ?
          WHERE $list_id = '{$listid[$i]}'",
          array($listname[$i], $listposition[$i],
                $db->emptyToZero($listshow[$i]),
                $listtense[$i]
                ));
      }
      else {
          $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
      };
  };

  $_SESSION['SUCCESS'] = $redirectmessage;
  header("Location: " . $_POST['prev_page']);

// End of updating the version list

/////////////////////////////////////////
// Start of adding a version list item //
/////////////////////////////////////////

} elseif ($_POST['action'] == "add_to_version_list"
          && ($permissions['is_admin'] == '1'
              OR $permissions['manage_project'] == '1')) {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {

      $update = $db->Query("INSERT INTO $list_table_name
                        (project_id, $list_column_name, list_position, show_in_list, version_tense)
                        VALUES (?, ?, ?, ?, ?)",
                array($_POST['project_id'], $_POST['list_name'], $_POST['list_position'], '1', $_POST['version_tense']));

      //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;show={$_POST['list_type']}&amp;id={$_POST['project_id']}\">";
      //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['listitemadded']}</em></p></div>";
      $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
      header("Location: index.php?do=admin&area=projects&show=" . $_POST['list_type'] . "&id=" . $_POST['project_id']);

} else {
    $_SESSION['ERROR'] = $modify_text['fillallfields'];
   header("Location: " . $_POST['prev_page']);
};
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

  $redirectmessage = $modify_text['listupdated'];

  for($i = 0; $i < count($listname); $i++) {
      $listname[$i] = stripslashes($listname[$i]);
      if ($listname[$i] != ''
          && is_numeric($listposition[$i])
          ) {
          $update = $db->Query("UPDATE flyspray_list_category SET
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

  $_SESSION['SUCCESS'] = $redirectmessage;
  header("Location: " . $_POST['prev_page']);

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
      $update = $db->Query("INSERT INTO flyspray_list_category
                                (project_id, category_name, list_position,
                                show_in_list, category_owner, parent_id)
                                VALUES (?, ?, ?, ?, ?, ?)",
                        array($_POST['project_id'], $_POST['list_name'],
                        $_POST['list_position'],
                        '1',
                        $_POST['category_owner'],
                        $db->emptyToZero($_POST['parent_id'])));

      $_SESSION['SUCCESS'] = $modify_text['listitemadded'];
      header("Location: index.php?do=admin&area=projects&id=" . $_POST['project_id'] . "&show=category");

} else {
    $_SESSION['ERROR'] = $modify_text['fillallfields'];
    header("Location: " . $_POST['prev_page']);
};
// End of adding a category list item

//////////////////////////////////////////
// Start of adding a related task entry //
//////////////////////////////////////////

} elseif ($_POST['action'] == "add_related"
          && ($permissions['modify_all_jobs'] == '1'
               OR ($permissions['modify_own_tasks'] == '1'))) { // FIX THIS PERMISSION!!

  if (is_numeric($_POST['related_task'])) {
    $check = $db->Query("SELECT * FROM flyspray_related
        WHERE this_task = ?
        AND related_task = ?",
        array($_POST['this_task'], $_POST['related_task']));
    $check2 = $db->Query("SELECT attached_to_project FROM flyspray_tasks
        WHERE task_id = ?",
        array($_POST['related_task']));

    if ($db->CountRows($check) > 0) {
        //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
        //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatederror']}</em></p></div>";
        $_SESSION['ERROR'] = $modify_text['relatederror'];
        header("Location: index.php?do=details&id=" . $_POST['this_task'] . "&area=related#tabs");
    } elseif (!$db->CountRows($check2)) {
        //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
        //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedinvalid']}</em></p></div>";
        $_SESSION['ERROR'] = $modify_text['relatedinvalid'];
        header("Location: index.php?do=details&id=" . $_POST['this_task'] . "&area=related#tabs");
    } else {
        list($relatedproject) = $db->FetchRow($check2);
        if ($project_id == $relatedproject || isset($_POST['allprojects'])) {
            $insert = $db->Query("INSERT INTO flyspray_related (this_task, related_task) VALUES(?,?)", array($_POST['this_task'], $_POST['related_task']));

            $fs->logEvent($_POST['this_task'], 11, $_POST['related_task']);
            $fs->logEvent($_POST['related_task'], 15, $_POST['this_task']);

            //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
            //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedadded']}</em></p></div>";
            $_SESSION['SUCCESS'] = $modify_text['relatedadded'];
            header("Location: index.php?do=details&id=" . $_POST['this_task'] . "&area=related#tabs");

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
    //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedinvalid']}</em></p></div>";
    $_SESSION['ERROR'] = $modify_text['relatedinvalid'];
    header("Location: index.php?do=details&id=" . $_POST['this_task'] . "&area=related#tabs");
  };

// End of adding a related task entry

///////////////////////////////////
// Removing a related task entry //
///////////////////////////////////

} elseif ($_POST['action'] == "remove_related"
          && ($permissions['modify_all_jobs'] == '1'
               OR ($permissions['modify_own_tasks'] == '1'))) { // FIX THIS PERMISSION!!

  $remove = $db->Query("DELETE FROM flyspray_related WHERE related_id = ?", array($_POST['related_id']));

  $fs->logEvent($_POST['id'], 12, $_POST['related_task']);
  $fs->logEvent($_POST['related_task'], 16, $_POST['id']);

  //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['id']}&amp;area=related#tabs\">";
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedremoved']}</em></p></div>";
  $_SESSION['SUCCESS'] = $modify_text['relatedremoved'];
  header("Location: index.php?do=details&id=" . $_POST['id'] . "&area=related#tabs");

// End of removing a related task entry

/////////////////////////////////////////////////////
// Start of adding a user to the notification list //
/////////////////////////////////////////////////////

} elseif ($_POST['action'] == "add_notification"
          && $_COOKIE['flyspray_userid']) {

  $check = $db->Query("SELECT * FROM flyspray_notifications
    WHERE task_id = ?  AND user_id = ?",
    array($_POST['task_id'], $_POST['user_id']));
  if (!$db->CountRows($check)) {

    $insert = $db->Query("INSERT INTO flyspray_notifications (task_id, user_id) VALUES(?,?)",
    array($_POST['task_id'], $_POST['user_id']));

    $fs->logEvent($_POST['task_id'], 9, $_POST['user_id']);

    //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['notifyadded']}</em></p></div>";
    $_SESSION['SUCCESS'] = $modify_text['notifyadded'];
    header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=notify#tabs");
  } else {
    //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['notifyerror']}</em></p></div>";
    $_SESSION['ERROR'] = $modify_text['notifyerror'];
    header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=notify#tabs");
  };

// End of adding a user to the notification list

////////////////////////////////////////////
// Start of removing a notification entry //
////////////////////////////////////////////

} elseif ($_POST['action'] == "remove_notification"
          && $_COOKIE['flyspray_userid']) {

  $remove = $db->Query("DELETE FROM flyspray_notifications WHERE task_id = ? AND user_id = ?",
    array($_POST['task_id'], $_POST['user_id']));

  $fs->logEvent($_POST['task_id'], 10, $_POST['user_id']);

  //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['notifyremoved']}</em></p></div>";
  $_SESSION['SUCCESS'] = $modify_text['notifyremoved'];
  header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=notify#tabs");

// End of removing a notification entry

////////////////////////////////
// Start of editing a comment //
////////////////////////////////

} elseif ($_POST['action'] == "editcomment"
          && $permissions['edit_comments'] == '1') {

  $update = $db->Query("UPDATE flyspray_comments
              SET comment_text = ?  WHERE comment_id = ?",
              array($_POST['comment_text'], $_POST['comment_id']));

  $fs->logEvent($_POST['task_id'], 5, $_POST['comment_text'], $_POST['previous_text'], $_POST['comment_id']);

  //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['editcommentsaved']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";
  $_SESSION['SUCCESS'] = $modify_text['editcommentsaved'];
  header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=comments#tabs");

// End of editing a comment

/////////////////////////////////
// Start of deleting a comment //
/////////////////////////////////

} elseif ($_POST['action'] == "deletecomment"
          && $permissions['delete_comments'] == '1') {

  $row = $db->FetchRow($db->Query('SELECT comment_text, user_id, date_added FROM flyspray_comments WHERE comment_id = ?', array($_POST['comment_id'])));
  $delete = $db->Query('DELETE FROM flyspray_comments WHERE comment_id = ?', array($_POST['comment_id']));

  $fs->logEvent($_POST['task_id'], 6, $row['user_id'], $row['comment_text'], $row['date_added']);

  //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['commentdeleted']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";
  $_SESSION['SUCCESS'] = $modify_text['commentdeleted'];
  header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=comments#tabs");

// End of deleting a comment

/////////////////////////////////////
// Start of deleting an attachment //
/////////////////////////////////////

//  "Deleting attachments" code contributed by Harm Verbeek <info@certeza.nl>
} elseif ($_POST['action'] == "deleteattachment"
          && $permissions['delete_attachments'] == '1') {

// if an attachment needs to be deleted do it right now
  $delete = $db->Query('SELECT file_name, orig_name FROM flyspray_attachments
                            WHERE attachment_id = ?',
                            array($_POST['attachment_id']));
  if ($row = $db->FetchArray($delete)) {
    @unlink("attachments/".$row['file_name']);
    $db->Query('DELETE FROM flyspray_attachments WHERE attachment_id = ?',
                    array($_POST['attachment_id']));
  }

  $fs->logEvent($_POST['task_id'], 8, $row['orig_name']);

  //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs\">";
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['attachmentdeleted']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";
  $_SESSION['SUCCESS'] = $modify_text['attachmentdeleted'];
  header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=attachments#tabs");

// End of deleting an attachment

////////////////////////////////
// Start of adding a reminder //
////////////////////////////////

} elseif ($_POST['action'] == "addreminder"
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1')) {

  $now = date(U);

  $how_often = $_POST['timeamount1'] * $_POST['timetype1'];
  //echo "how often = $how_often<br>";
  //echo "now = $now<br>";

  $start_time = ($_POST['timeamount2'] * $_POST['timetype2']) + $now;
  //echo "start time = $start_time";

  $insert = $db->Query("INSERT INTO flyspray_reminders (task_id, to_user_id, from_user_id, start_time, how_often, reminder_message) VALUES(?,?,?,?,?,?)", array($_POST['task_id'], $_POST['to_user_id'], $current_user['user_id'], $start_time, $how_often, $_POST['reminder_message']));

  $fs->logEvent($_POST['task_id'], 17, $_POST['to_user_id']);

  //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=remind#tabs\">";
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['reminderadded']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";
  $_SESSION['SUCCESS'] = $modify_text['reminderadded'];
  header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=remind#tabs");

// End of adding a reminder

//////////////////////////////////
// Start of removing a reminder //
//////////////////////////////////
} elseif ($_POST['action'] == "deletereminder"
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1')) {

  $reminder = $db->FetchRow($db->Query("SELECT to_user_id FROM flyspray_reminders WHERE reminder_id = ?", array($_POST['reminder_id'])));
  $db->Query('DELETE FROM flyspray_reminders WHERE reminder_id = ?',
                    array($_POST['reminder_id']));

  $fs->logEvent($_POST['task_id'], 18, $reminder['to_user_id']);

  //echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=remind#tabs\">";
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['reminderdeleted']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";
  $_SESSION['SUCCESS'] = $modify_text['reminderdeleted'];
  header("Location: index.php?do=details&id=" . $_POST['task_id'] . "&area=remind#tabs");

// End of removing a reminder

/////////////////////////////////////////////////
// Start of adding a bunch of users to a group //
/////////////////////////////////////////////////
} elseif ($_POST['action'] == "addtogroup"
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1')) {

  // If no users were selected, throw an error
  if (!is_array($_POST['user_list'])) {
    $_SESSION['ERROR'] = $modify_text['nouserselected'];
    header("Location: index.php?do=admin&area=users&project=" . $_POST['project_id']);

  // If users were select, keep going
  } else {

  // Cycle through the users passed to us
  while (list($key, $val) = each($_POST['user_list'])) {

    // Create entries for them that point to the requested group
    $create = $db->Query("INSERT INTO flyspray_users_in_groups
                            (user_id, group_id)
                            VALUES(?, ?)",
                            array($val, $_POST['add_to_group']));
  };

  $_SESSION['SUCCESS'] = $modify_text['groupswitchupdated'];
  header("Location: " . $_POST['prev_page']);
  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupswitchupdated']}</em></p>";
  //echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

  };

// End of adding a bunch of users to a group


///////////////////////////////////////////////
// Start of change a bunch of users' groups //
//////////////////////////////////////////////
} elseif ($_POST['action'] == "movetogroup"
          && ($permissions['manage_project'] == '1'
              OR $permissions['is_admin'] == '1')) {

  $num_users = $_POST['num_users'];

  while ($num_users > '0') {

    $foo = 'user' . $num_users;

    if (!empty($_POST[$foo])) {
      if ($_POST['switch_to_group'] == '0') {

        $remove = $db->Query("DELETE FROM flyspray_users_in_groups
            WHERE user_id = ? AND group_id = ?",
            array($_POST[$foo], $_POST['old_group']));

      } else {

        $update = $db->Query("UPDATE flyspray_users_in_groups
            SET group_id = ?
            WHERE user_id = ? AND group_id = ?",
            array($_POST['switch_to_group'], $_POST[$foo], $_POST['old_group']));
      }
    }

    $num_users --;
  };

  $_SESSION['SUCCESS'] = $modify_text['groupswitchupdated'];
  header("Location: " . $_POST['prev_page']);

  // End of changing a bunch of users' groups

///////////////////////////////
// Start of taking ownership //
///////////////////////////////

} elseif ($_POST['action'] == 'takeownership'
          && (($permissions['assign_to_self'] == '1' && $old_details['assigned_to'] == '0')
               OR $permissions['assign_others_to_self'] == '1')) {

  $update = $db->Query("UPDATE flyspray_tasks
                          SET assigned_to = ?, item_status = '3'
                          WHERE task_id = ?",
                          array($current_user['user_id'], $_POST['task_id']));
  // Add code for notifications

  // If this task was previously assigned to someone else, better notify them of their loss
  if($old_details['assigned_to'] != '0') {

    $item_summary = stripslashes($_POST['item_summary']);
    $subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
    $message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
    {$modify_text['nolongerassigned']} {$current_user['real_name']} ({$current_user['username']}).\n
    {$modify_text['task']} #{$_POST['task_id']} - {$old_details['item_summary']}\n
    {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";
    // End of generating a message

    // Send the brief notification message
    $result = $notify->Basic($old_details['assigned_to'], $subject, $message);
    echo $result;

  };

  // Log this event to the task history
  $fs->logEvent($_POST['task_id'], 19, $current_user['user_id'], $old_details['assigned_to']);


  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['takenownership']}</em></p>";
  echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

// End of taking ownership


//////////////////////////////////////
// Start of requesting task closure //
//////////////////////////////////////

} elseif ($_POST['action'] == 'requestclose') {

  // Log the admin request
  $fs->AdminRequest(1, $project_id, $_POST['task_id'], $current_user['user_id']);

  // Log this event to the task history
  $fs->logEvent($_POST['task_id'], 20, $current_user['user_id']);

  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['adminrequestmade']}</em></p>";
  echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

// End of requesting task closure


/////////////////////////////////////////
// Start of requesting task re-opening //
/////////////////////////////////////////

} elseif ($_POST['action'] == 'requestreopen') {

  // Log the admin request
  $fs->AdminRequest(2, $project_id, $_POST['task_id'], $current_user['user_id']);

  // Log this event to the task history
  $fs->logEvent($_POST['task_id'], 21, $current_user['user_id']);

  // Check if the user is on the notification list
  $check_notify = $db->Query("SELECT * FROM flyspray_notifications
                                WHERE task_id = ?
                                AND user_id = ?",
                                array($_POST['task_id'], $current_user['user_id'])
                              );

  if (!$db->CountRows($check_notify)) {
    // Add the requestor to the task notification list, so that they know when it has been re-opened
    $insert = $db->Query("INSERT INTO flyspray_notifications (task_id, user_id) VALUES(?,?)",
    array($_POST['task_id'], $current_user['user_id']));

    $fs->logEvent($_POST['task_id'], 9, $current_user['user_id']);
  };

  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['adminrequestmade']}</em></p>";
  echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

// End of requesting task re-opening


//////////////////////////////////
// Start of adding a dependency //
//////////////////////////////////

} elseif ($_POST['action'] == 'newdep'
          && (($permissions['modify_own_tasks'] == '1'
               && $old_details['assigned_to'] == $current_user['user_id'])
             OR $permissions['modify_all_tasks'] =='1')) {

  // First check that the user hasn't tried to add this twice
  $check_dep = $db->Query("SELECT * FROM flyspray_dependencies
                             WHERE task_id = ? AND dep_task_id = ?",
                             array($_POST['task_id'], $_POST['dep_task_id']));

  // or that they are trying to reverse-depend the same task, creating a mutual-block
  $check_dep2 = $db->Query("SELECT * FROM flyspray_dependencies
                             WHERE task_id = ? AND dep_task_id = ?",
                             array($_POST['dep_task_id'], $_POST['task_id']));

  // Check that the dependency actually exists!
  $check_dep3 = $db->Query("SELECT * FROM flyspray_tasks
                              WHERE task_id = ?",
                              array($_POST['dep_task_id'])
                            );


  if (!$db->CountRows($check_dep)
       && !$db->CountRows($check_dep2)
       && $db->CountRows($check_dep3)
       // Check that the user hasn't tried to add the same task as a dependency
       && $_POST['task_id'] != $_POST['dep_task_id']) {

    // Log this event to the task history, both ways
    $fs->logEvent($_POST['task_id'], 22, $_POST['dep_task_id']);
    $fs->logEvent($_POST['dep_task_id'], 23, $_POST['task_id']);

    // Add the dependency to the database
    $add_dep = $db->Query("INSERT INTO flyspray_dependencies
                             (task_id, dep_task_id)
                             VALUES(?,?)",
                             array($_POST['task_id'], $_POST['dep_task_id']));

    // Get the details on the task that was just added as a dependency
    $dep_details = $db->FetchArray($db->Query("SELECT * FROM flyspray_tasks
                                                   WHERE task_id = ?",
                                                   array($_POST['dep_task_id'])));

    // Create notification message
    $item_summary = stripslashes($old_details['item_summary']);
    $subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
    $message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
    {$modify_text['newdepnotify']} .\n
    FS#{$_POST['task_id']} - {$old_details['item_summary']}
    {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}\n
    {$modify_text['newdepis']}\n
    FS#{$_POST['dep_task_id']}: {$dep_details['item_summary']}
    {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['dep_task_id']}\n";
    // End of generating a message

    // Send the brief notification message
    $result = $notify->Basic($old_details['assigned_to'], $subject, $message);
    echo $result;

    // Send the detailed notification message
    $result = $notify->Detailed($_POST['task_id'], $subject, $detailed_message);
    echo $result;

    // Redirect
   $_SESSION['SUCCESS'] = $modify_text['dependadded'];
   header("Location: index.php?do=details&id=" . $_POST['task_id']);

    //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['dependadded']}</em></p>";
    //echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

  // If the user tried to add the wrong task as a dependency
  } else {

    // If the user tried to add the 'wrong' task as a dependency,
    // show error and redirect
   $_SESSION['ERROR'] = $modify_text['dependaddfailed'];
   header("Location: index.php?do=details&id=" . $_POST['task_id']);

   //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['dependaddfailed']}</em></p>";
   //echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

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
  $dep_info = $db->FetchArray($db->Query("SELECT * FROM flyspray_dependencies
                                              WHERE depend_id = ?",
                                              array($_GET['depend_id'])));

  $remove = $db->Query("DELETE FROM flyspray_dependencies
                           WHERE depend_id = ?",
                           array($_GET['depend_id']));

  // Log this event to the task's history
  $fs->logEvent($dep_info['task_id'], 24, $dep_info['dep_task_id']);
  $fs->logEvent($dep_info['dep_task_id'], 25, $dep_info['task_id']);

   // Generate status message and redirect
   $_SESSION['SUCCESS'] = $modify_text['depremoved'];
   header("Location: index.php?do=details&id=" . $dep_info['task_id']);

  //echo "<div class=\"redirectmessage\"><p><em>{$modify_text['depremoved']}</em></p>";
  //echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";


// End of removing a dependency


//////////////////////////////////////////////////
// Start of a user requesting a password change //
//////////////////////////////////////////////////

} elseif ($_POST['action'] == 'sendmagic') {

  // Check that the username exists
  $check_details = $db->Query("SELECT * FROM flyspray_users
                                 WHERE user_name = ?",
                                 array($_POST['user_name']));

  // If the username doesn't exist, throw an error
  if (!$db->CountRows($check_details)) {
    // Error message goes here

  // ...otherwise get on with it
  } else {
    $user_details = $db->FetchArray($check_details);

    // Generate a looonnnnggg random string to send as an URL
    $magic_url = md5(microtime());

    // Insert the random "magic url" into the user's profile
    $update = $db->Query("UPDATE flyspray_users
                            SET magic_url = ?
                            WHERE user_id = ?",
                            array($magic_url, $user_details['user_id'])
                          );

    // Create notification message
    $message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
{$modify_text['magicurlmessage']} \n
{$flyspray_prefs['base_url']}index.php?do=lostpw&amp;magic=$magic_url\n";
    // End of generating a message

    // Send the brief notification message
    $result = $notify->Basic($user_details['user_id'], $modify_text['changefspass'], $message);
    echo $result;

    // Let the user know what just happened
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['magicurlsent']}</em></p></div>";

  // End of checking if the username exists
  };

// End of a user requesting a password change


////////////////////////////////
// Change the user's password //
////////////////////////////////

} elseif ($_POST['action'] == 'chpass') {

  // Check that the user submitted both the fields, and they are the same
  if ($_POST['pass1'] != ''
      && $_POST['pass2'] != ''
      && $_POST['magic_url'] != ''
      && $_POST['pass2'] == $_POST['pass2']) {

  // Get the user's details from the magic url
  $user_details = $db->FetchArray($db->Query("SELECT * FROM flyspray_users
                                                  WHERE magic_url = ?",
                                                  array($_POST['magic_url'])
                                                )
                                    );

  // Encrypt the new password
  $new_pass_hash = crypt($_POST['pass1'], '4t6dcHiefIkeYcn48B');

  // Change the password and clear the magic_url field
  $update = $db->Query("UPDATE flyspray_users SET
                          user_pass = ?,
                          magic_url = ''
                          WHERE magic_url = ?",
                          array($new_pass_hash, $_POST['magic_url'])
                        );

  // Let the user know what just happened
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['passchanged']}</em></p>";
    echo "<p>{$modify_text['loginbelow']}</p></div>";

  // If the fields were submitted incorrectly, show an error
  } else {

    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['erroronform']}</em></p>";
    echo "<p><a href=\"javascript:history.back()\">{$modify_text['goback']}</a></p></div>";

  // End of checking fields were submitted correctly
  };

// End of changing the user's password


////////////////////////////////////
// Start of making a task private //
////////////////////////////////////

} elseif ($_GET['action'] == 'makeprivate'
  && $permissions['manage_project'] == '1') {

  $update = $db->Query("UPDATE flyspray_tasks
                          SET mark_private = '1'
                          WHERE task_id = ?",
                          array($_GET['id'])
                         );

   $fs->logEvent($_GET['id'], 26);

   $_SESSION['SUCCESS'] = $modify_text['taskmadeprivate'];
   header("Location: index.php?do=details&id=" . $_GET['id']);

// End of making a task private


///////////////////////////////////
// Start of making a task public //
///////////////////////////////////

} elseif ($_GET['action'] == 'makepublic'
  && $permissions['manage_project'] == '1') {

  $update = $db->Query("UPDATE flyspray_tasks
                          SET mark_private = '0'
                          WHERE task_id = ?",
                          array($_GET['id'])
                         );

   $fs->logEvent($_GET['id'], 27);

   $_SESSION['SUCCESS'] = $modify_text['taskmadepublic'];
   header("Location: index.php?do=details&id=" . $_GET['id']);

// End of making a task public


/////////////////////
// End of actions! //
/////////////////////
};

?>
