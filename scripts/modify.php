<?php
// This script performs all database modifications

get_language_pack($lang, 'modify');

// FIXME: only temporary workaround
$_POST['default_cat_owner'] = $fs->emptyToZero($_POST['default_cat_owner']);
$_POST['category_owner']    = $fs->emptyToZero($_POST['category_owner']);

$list_table_name = "flyspray_list_".addslashes($_POST['list_type']);
$list_column_name = addslashes($_POST['list_type'])."_name";
$list_id = addslashes($_POST['list_type'])."_id";

// Find out the current user's name
if (!empty($_COOKIE['flyspray_userid'])) {
  $get_current_username = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($_COOKIE['flyspray_userid']));
  list($current_username, $current_realname) = $fs->dbFetchArray($get_current_username);
}

$now = date(U);

if (!empty($_POST['task_id'])) {
  $old_details = $fs->GetTaskDetails($_POST['task_id']);
}

////////////////////////////////
// Start of adding a new task //
////////////////////////////////

if ($_POST['action'] == "newtask" && ($_SESSION['can_open_jobs'] == "1" OR $flyspray_prefs['anon_open'] == "1")) {

  // If they entered something in both the summary and detailed description
  if ($_POST['item_summary'] != ''
    && $_POST['detailed_desc'] != '')
  {

    $item_summary = $_POST['item_summary'];
    $detailed_desc = $_POST['detailed_desc'];

    $param_names = array('task_type', 'item_status',
        'assigned_to', 'product_category', 'product_version',
        'closedby_version', 'operating_system', 'task_severity', 'task_priority');
    $sql_values = array($_POST['project_id'], $now, $now, $item_summary,
                $detailed_desc, 
		$fs->emptyToZero($_COOKIE['flyspray_userid']), '0');
    $sql_params = array();
    foreach ($param_names as $param_name) {
        if (!empty($_POST[$param_name])) {
            array_push($sql_params, $param_name);
            array_push($sql_values, $_POST[$param_name]);
        }
    }
    $sql_params = join(', ', $sql_params);
    $sql_placeholder = join(', ', array_fill(1, count($sql_values), '?'));

    $add_item = $fs->dbQuery("INSERT INTO flyspray_tasks
    (attached_to_project, date_opened, last_edited_time, item_summary, 
    detailed_desc, opened_by, percent_complete, $sql_params)
    VALUES ($sql_placeholder)", $sql_values);

    // Now, let's get the task_id back, so that we can send a direct link
    // URL in the notification message
    $get_task_info = $fs->dbFetchArray($fs->dbQuery("SELECT task_id, item_summary FROM flyspray_tasks
                                                WHERE item_summary = ?
                                                AND detailed_desc = ?
                                                ORDER BY task_id DESC LIMIT 1
                                ", array($item_summary, $detailed_desc)));
    //$task_id = $get_task_info['task_id'];

    // If the reporter wanted to be added to the notification list
    if ($_POST['notifyme'] == '1') {
      $insert = $fs->dbQuery("INSERT INTO flyspray_notifications
      (task_id, user_id)
      VALUES('{$get_task_info['task_id']}', '{$_COOKIE['flyspray_userid']}')");
      $fs->logEvent($get_task_info['task_id'], 9, $_COOKIE['flyspray_userid']);
    };

    // Check if the new task was assigned to anyone
    if ($_POST['assigned_to'] != '' && $_POST['assigned_to'] != '0') {
        $fs->logEvent($get_task_info['task_id'], 14, $_POST['assigned_to'], '0');
        if ($_POST['assigned_to'] != $_COOKIE['flyspray_userid']) {

// Create the brief notification message
$subject = "{$modify_text['flyspraytask']} #{$get_task_info['task_id']} - {$get_task_info['item_summary']}";
$message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasopened']}\n
{$modify_text['newtask']}: {$_POST['item_summary']} \n
{$modify_text['moreinfonew']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$get_task_info['task_id']}";

            // ...And send it off to let the person know about their task
            $result = $fs->SendBasicNotification($_POST['assigned_to'], $subject, $message);
            echo $result;
        };
    };

    // OK, we also need to notify the category owner
    // First, see if there's an owner for this category
    $send_to = '';
    $cat_details = $fs->dbFetchArray($fs->dbQuery("SELECT category_name, category_owner, parent_id
                                       FROM flyspray_list_category
                                       WHERE category_id = ?",
                                       array($_POST['product_category'])));

    // If this category has an owner, address the notification to them
    if ($cat_details['category_owner'] != '0') {
      $send_to = $cat_details['category_owner'];
    } elseif ($cat_details['parent_id'] != '0') {
      // If not, see if we can get the parent category owner
      $parent_cat_details = $fs->dbFetchArray($fs->dbQuery('SELECT category_owner
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
        $result = $fs->SendBasicNotification($send_to, $subject, $message);
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
          && ($_SESSION['can_modify_jobs'] == "1" 
                OR $_SESSION['userid'] == $old_details['assigned_to'])) {

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

    $old_details_history = $fs->dbFetchRow($fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));

    $item_summary = $_POST['item_summary'];
    $detailed_desc = $_POST['detailed_desc'];

// A bit dodgy, part 2.
    if ($_POST['edit_start_time'] == "999999999999") {
      $item_summary = stripslashes($_POST['item_summary']);
      $detailed_desc = stripslashes($_POST['detailed_desc']);
    }

    $add_item = $fs->dbQuery("UPDATE flyspray_tasks SET
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

                    $fs->emptyToZero($_POST['closedby_version']),
                    $_POST['operating_system'], $_POST['task_severity'],
                    $_POST['task_priority'], $_COOKIE['flyspray_userid'],
                    $now,
                    $_POST['percent_complete'],
                    $_POST['task_id']
                ));

    // Get the details of the task we just updated
    // To generate the changed-task message
    $new_details = $fs->GetTaskDetails($_POST['task_id']);
    $new_details_history = $fs->dbFetchRow($fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));
    
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
        $message = $message . "** " . $key . " " . stripslashes($new_details[$val]) . "\n";
        $send_me = "YES";
      } else {
        $message = $message . $key . " " . stripslashes($new_details[$val]) . "\n";
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
$item_summary = stripslashes($_POST['item_summary']);
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$message = "{$modify_text['messagefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasjustmodified']} {$modify_text['youonnotify']}
{$modify_text['changedfields']}\n-----\n"
. $message .
"-----\n{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}\n\n";

      if ($send_me == "YES") {
        // Send the detailed notification message
        $result = $fs->SendDetailedNotification($_POST['task_id'], $subject, $message);
        echo $result;
      };

    // Check to see if the assignment has changed
    // Because we have to send a simple notification or two
    if ($_POST['old_assigned'] != $_POST['assigned_to']) {

      $item_summary = stripslashes($_POST['item_summary']);

      // If someone had previously been assigned this item, notify them of the change in assignment
      if ($_POST['old_assigned'] != "0" && ($_POST['old_assigned'] != $_COOKIE['flyspray_userid'])) {

        if ($_POST['assigned_to'] == "0") {
          $new_realname = $modify_text['noone'];
          $new_username = $modify_text['unassigned'];
        };

        // Generate the brief notification message to send
        $get_new = $fs->dbFetchArray($fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = ?", array($_POST['assigned_to'])));

        if ($get_new['user_name'] != '') {
          $new_username = $get_new['user_name'];
        } else {
          $new_username = "No-one";
        };

        $item_summary = stripslashes($_POST['item_summary']);
        $subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
        $message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
        {$modify_text['nolongerassigned']} {$get_new['real_name']} ($new_username).\n
        {$modify_text['task']} #{$_POST['task_id']} - {$_POST['item_summary']}\n
        {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";
        // End of generating a message

        // Send the brief notification message
        $result = $fs->SendBasicNotification($_POST['old_assigned'], $subject, $message);
        echo $result;
      };

      // If assignment isn't "none", notify the new assignee of their task
      if ($_POST['assigned_to'] != "0" && ($_POST['assigned_to'] != $_COOKIE['flyspray_userid'])) {

        // Get the brief notification message to send
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - {$_POST['item_summary']}";
$message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasassigned']}\n
{$modify_text['task']} #{$_POST['task_id']}: {$_POST['item_summary']} \n
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";

        // Send the brief notification message
        $result = $fs->SendBasicNotification($_POST['assigned_to'], $subject, $message);
        echo $result;
        
      };
              
      $fs->logEvent($_POST['task_id'], 14, $_POST['assigned_to'], $_POST['old_assigned']);

    };
      
    //$fs->logEvent($_POST['task_id'], 3);

    echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}\">";
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['taskupdated']}</em></p>";
    echo "<p>{$modify_text['waitwhiletransfer']}</p></div>";

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

} elseif($_POST['action'] == "close" && $_SESSION['can_modify_jobs'] == "1") {

  if ($_POST['resolution_reason'] != "1") {

    $close_item = $fs->dbQuery("UPDATE flyspray_tasks SET

    date_closed = ?,
    closed_by = ?,
    closure_comment = ?,
    is_closed = '1',
    resolution_reason = ?

    WHERE task_id = ?

    ", array($now, $_COOKIE['flyspray_userid'], $_POST['closure_comment'],
    $_POST['resolution_reason'], $_POST['task_id']));

    // Get the resolution name for the notifications
    $get_res = $fs->dbFetchArray($fs->dbQuery("SELECT resolution_name FROM flyspray_list_resolution WHERE resolution_id = ?", array($_POST['resolution_reason'])));

    // Get the item summary for the notifications
    list($item_summary) = $fs->dbFetchArray($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));
    $item_summary = stripslashes($item_summary);

    if ($_COOKIE['flyspray_userid'] != $_POST['assigned_to']) {

// Create a basic notification message
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$brief_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasclosedassigned']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['reasonforclosing']} {$get_res['resolution_name']} \n";

if($_POST['closure_comment'] != '') {
   $brief_message = $brief_message . "\n {$modify_text['closurecomment']} {$_POST['closure_comment']}\n";
};

$brief_message = $brief_message . "\n{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']} \n";



      $result = $fs->SendBasicNotification($_POST['assigned_to'], $subject, $brief_message);
      echo $result;

    };
// Create a detailed notification message
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$detailed_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasclosed']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['reasonforclosing']} {$get_res['resolution_name']} \n";

if($_POST['closure_comment'] != '') {
   $detailed_message = $detailed_message . "{$modify_text['closurecomment']} {$_POST['closure_comment']}\n";
};
$detailed_message = $detailed_message . "\n{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']} \n";

      $result = $fs->SendDetailedNotification($_POST['task_id'], $subject, $detailed_message);
      echo $result;
      
    $fs->logEvent($_POST['task_id'], 2, $_POST['resolution_reason'], $_POST['closure_comment']);

    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['taskclosed']}</em></p>";
    echo "<p><a href=\"?do=details&amp;id={$_POST['task_id']}\">{$modify_text['returntotask']}</a></p>";
    echo "<p><a href=\"?\">{$modify_text['backtoindex']}</a></p></div>";

  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['noclosereason']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };

// End of closing a task

/////////////////////////////////
// Start of re-opening an task //
/////////////////////////////////

} elseif ($_POST['action'] == "reopen" && $_SESSION['can_modify_jobs'] == "1") {

    $add_item = $fs->dbQuery("UPDATE flyspray_tasks SET

    item_status = '7',
    resolution_reason = '1',
    closure_comment = ' ',
    is_closed = '0'

    WHERE task_id = ?

    ", array($_POST['task_id']));

    // Find out the user who closed this
    list($item_summary, $closed_by) = $fs->dbFetchArray($fs->dbQuery("SELECT item_summary, closed_by FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id'])));

    $item_summary = stripslashes($item_summary);

    if ($closed_by != $_COOKIE['flyspray_userid']) {

      // Generate basic notification message to send
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$brief_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasreopened']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";


      $result = $fs->SendBasicNotification($closed_by, $subject, $brief_message);
      echo $result;

    };

// Generate detailed notification message to send
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$detailed_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasreopened']} {$modify_text['youonnotify']} \n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}";


      $result = $fs->SendDetailedNotification($_POST['task_id'], $subject, $detailed_message);
      echo $result;
      
    $fs->logEvent($_POST['task_id'], 13);

    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['taskreopened']}</em></p><p><a href=\"?do=details&amp;id={$_POST['task_id']}\">{$modify_text['backtotask']}</a></p></div>";

// End of re-opening an item

///////////////////////////////
// Start of adding a comment //
///////////////////////////////

} elseif ($_POST['action'] == "addcomment" && $_SESSION['can_add_comments'] == "1") {

  if ($_POST['comment_text'] != "") {

    $comment = $_POST['comment_text'];

    $insert = $fs->dbQuery("INSERT INTO flyspray_comments
    (task_id, date_added, user_id, comment_text) VALUES
    ( ?, ?, ?, ? )",
    array($_POST['task_id'], $now, $_COOKIE['flyspray_userid'], $comment));

    echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['commentadded']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";

    $getdetails = $fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id']));
    $task_details = $fs->dbFetchArray($getdetails);

    //$get_jabber_id = $fs->dbQuery("SELECT jabber_id FROM flyspray_users WHERE user_id = '{$task_details['assigned_to']}'") or die ($fs->dbQuery());
    //list($jabber_id) = $fs->dbFetchArray($get_jabber_id);

    $item_summary = stripslashes($task_details['item_summary']);

    if ($task_details['assigned_to'] != "0"
       && ($task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
       ) {

      // Generate the basic notification message to send
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$basic_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['commenttoassigned']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
-----\n {$_POST['comment_text']} \n -----
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs";


      $result = $fs->SendBasicNotification($task_details['assigned_to'], $subject, $basic_message);
      echo $result;

    };

      // Generate the detailed notification message to send
$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$detailed_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['commenttotask']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
-----\n {$_POST['comment_text']} \n -----
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs";


      $result = $fs->SendDetailedNotification($_POST['task_id'], $subject, $detailed_message);
      echo $result;
      
      $row = $fs->dbFetchRow($fs->dbQuery("SELECT comment_id FROM flyspray_comments WHERE task_id = ? ORDER BY comment_id DESC LIMIT 1", array($_POST['task_id'])));        
      $fs->logEvent($_POST['task_id'], 4, $row['comment_id']);

  // If they pressed submit without actually typing anything
  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['nocommententered']}</em></p></table>";
  };


// End of adding a comment

/////////////////////////////////////////
// Start of changing a user's password //
/////////////////////////////////////////
/*
} elseif ($_POST['action'] == "chpass" && $_COOKIE['flyspray_userid'] && $_SESSION['userid']) {

  // get the password hash out of the db and hash the new one
  $get_pass_hash = $fs->dbQuery("SELECT user_pass FROM flyspray_users WHERE user_id = ?", array($_COOKIE['flyspray_userid']));
  list($db_pass_hash) = $fs->dbFetchArray($get_pass_hash);
  $old_pass = $_POST['old_pass'];
  $old_pass_hash = crypt("$old_pass", '4t6dcHiefIkeYcn48B');
  $new_pass = $_POST['new_pass'];
  $new_pass_hash = crypt("$new_pass", '4t6dcHiefIkeYcn48B');
  $confirm_pass = $_POST['confirm_pass'];

  // If they didn't fill in all the fields, show an error
  if (!($_POST['old_pass'] && $_POST['new_pass'] && $_POST['confirm_pass'])) {
    echo "<div class=\"redirectmessage\"><p>";
    echo "<a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";


  // Compare the old password with the db to see if it matches
  } elseif ($old_pass_hash != $db_pass_hash) {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['notcurrentpass']}</em></p>";
    echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";

  // if neither of the above two conditions are true, then process the password change
  } else {

    // If the two new passwords are the same
    if ($new_pass == $confirm_pass) {
      $update_pass = $fs->dbQuery("UPDATE flyspray_users SET user_pass = '$new_pass_hash' WHERE user_id = ?", array($_COOKIE['flyspray_userid']));
      
      //  Set a new passhash cookie so that the user isn't logged out
      setcookie('flyspray_passhash', crypt("$new_pass_hash", $cookiesalt), time()+60*60*24*30, "/");
      
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['passchanged']}</em></p>";
      echo "<p><a href=\"?\">{$modify_text['backtoindex']}</a></p></div>";

    // if the two new passwords aren't the same, show an error
    } else {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['passnomatch']}</em></p>";
      echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    };

  };
// End of changing a user's password
*/
////////////////////////////////////
// Start of new user registration //
////////////////////////////////////

} elseif ($_POST['action'] == "registeruser" && $flyspray_prefs['anon_open'] != '0') {

  // If they filled in all the required fields
  if ($_POST['user_pass'] != ''
    && $_POST['user_pass2'] != ''
    ) {
      // If the passwords matched
      if (($_POST['user_pass'] == $_POST['user_pass2']) && $_POST['user_pass'] != '') {
        //echo "reg_ref = {$_SESSION['reg_ref']}<br>";
        // Check that the user entered the right confirmation code
        $code_check = $fs->dbQuery("SELECT * FROM flyspray_registrations WHERE reg_time = ?", array($_SESSION['reg_ref']));
        $code_details = $fs->dbFetchArray($code_check);
        //echo "db = {$code_details['confirm_code']}<br>";
        //echo "posted = {$_POST['confirmation_code']}<br>";
        if ($code_details['confirm_code'] == $_POST['confirmation_code']) {

          $pass_hash = crypt("{$_POST['user_pass']}", '4t6dcHiefIkeYcn48B');

          $add_user = $fs->dbQuery("INSERT INTO flyspray_users 
				      (user_name, user_pass, real_name,
				      group_in, jabber_id, email_address,
				      notify_type, account_enabled, dateformat, dateformat_extended)
				      VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
		array($_POST['user_name'], $pass_hash, $_POST['real_name'],
		$flyspray_prefs['anon_group'], $_POST['jabber_id'],
		$_POST['email_address'], $_POST['notify_type'], '1',
		$flyspray_prefs['dateformat'], $flyspray_prefs['dateformat_extended']));
          echo "<div class=\"redirectmessage\"><p><em>{$modify_text['accountcreated']}</em></p>";
          echo "<p>{$modify_text['loginbelow']}</p>";
          echo "<p>{$modify_text['newuserwarning']}</p></div>";
          session_destroy();

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

////////////////////////////////////////////
// Start of adding a new user by an admin //
////////////////////////////////////////////

} elseif ($_POST['action'] == "newuser") {

  // If they filled in all the required fields
  if ($_POST['user_name'] != ""
    && $_POST['user_pass'] != ""
    && $_POST['user_pass2'] != ""
    && $_POST['real_name'] != ""
    && $_POST['email_address'] != ""
    ) {

    // Check to see if the username is available
    $check_username = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_name = ?", array($_POST['user_name']));
    if ($fs->dbCountRows($check_username)) {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['usernametaken']}</em></p>";
      echo "<p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    } else {

      // If the passwords matched, add the user
      if (($_POST['user_pass'] == $_POST['user_pass2']) && $_POST['user_pass'] != '') {

        $pass_hash = crypt("{$_POST['user_pass']}", '4t6dcHiefIkeYcn48B');

        if ($_SESSION['admin'] == '1') {
          $group_in = $_POST['group_in'];
        } else {
          $group_in = $flyspray_prefs['anon_group'];
        };

        $add_user = $fs->dbQuery("INSERT INTO flyspray_users
                                    (user_name, user_pass, real_name,
                                    group_in, jabber_id, email_address,
                                    notify_type, account_enabled)
                                    VALUES( ?, ?, ?, ?, ?, ?, ?, ?)",
                        array($_POST['user_name'], $pass_hash,
                            $_POST['real_name'], $group_in,
                            $_POST['jabber_id'], $_POST['email_address'],
                            $_POST['notify_type'], '1'));

        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['newusercreated']}</em></p>";

        if ($_SESSION['admin'] != '1') {
          echo "<p>{$modify_text['loginbelow']}</p>";
          echo "<p>{$modify_text['newuserwarning']}</p></div>";
        } else {
          echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=users\">";
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

} elseif ($_POST['action'] == "newgroup" && ($_SESSION['admin'] == '1' OR $flyspray_prefs['anon_open'] == '2')) {

  // If they filled in all the required fields
  if ($_POST['group_name'] != ""
    && $_POST['group_desc'] != ""
    ) {

    // Check to see if the group name is available
    $check_groupname = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_name = ?", array($_POST['group_name']));
    if ($fs->dbCountRows($check_groupname)) {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupnametaken']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
    } else {

      $add_group = $fs->dbQuery("INSERT INTO flyspray_groups
                                      (group_name, group_desc, is_admin,
                                      can_open_jobs, can_modify_jobs,
                                      can_add_comments, can_attach_files,
                                      can_vote, group_open)
                                      VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array($_POST['group_name'], $_POST['group_desc'],
                $fs->emptyToZero($_POST['is_admin']),
                $fs->emptyToZero($_POST['can_open_jobs']),
                $fs->emptyToZero($_POST['can_modify_jobs']),
                $fs->emptyToZero($_POST['can_add_comments']),
                $fs->emptyToZero($_POST['can_attach_files']),
                $fs->emptyToZero($_POST['can_vote']),
                $fs->emptyToZero($_POST['group_open'])
                ));

        echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=users\">";
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['newgroupadded']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";
    };

  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['formnotcomplete']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };
// End of adding a new group

///////////////////////////////////////////////
// Update the global application preferences //
///////////////////////////////////////////////

} elseif ($_POST['action'] == "globaloptions" && $_SESSION['admin'] == '1') {

  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'anon_open'", array($_POST['anon_open']));
  //$update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['theme_style']}' WHERE pref_name = 'theme_style'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_server'", array($_POST['jabber_server']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_port'", array($_POST['jabber_port']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_username'", array($_POST['jabber_username']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'jabber_password'", array($_POST['jabber_password']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'anon_group'", array($_POST['anon_group']));
  //$update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['project_title']}' WHERE pref_name = 'project_title'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'base_url'", array($_POST['base_url']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'user_notify'", array($_POST['user_notify']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'admin_email'", array($_POST['admin_email']));
//  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'default_cat_owner'", array($_POST['default_cat_owner']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'lang_code'", array($_POST['lang_code']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'spam_proof'", array($_POST['spam_proof']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'anon_view'", array($_POST['anon_view']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'default_project'", array($_POST['default_project']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'dateformat'", array($_POST['dateformat']));
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'dateformat_extended'", array($_POST['dateformat_extended']));

  // This is an overly complex way to ensure that we always get the right amount of posted
  // results from the assigned_groups preference
  $get_groups = $fs->dbQuery("SELECT * FROM flyspray_groups ORDER BY group_id ASC");
  $group_number = '1';

  while ($row = $fs->dbFetchArray($get_groups)) {
    $posted_group = "assigned_groups" . $group_number;

    if (!isset($first_done)) {
      $assigned_groups = $_POST[$posted_group];
    } else {
      $assigned_groups = $assigned_groups . " $_POST[$posted_group]";
    };
    $first_done = '1';
    $group_number ++;
  };

  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = ? WHERE pref_name = 'assigned_groups'", array($assigned_groups));
  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=options\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['optionssaved']}</em></p></div>";
  
// End of updating application preferences

///////////////////////////////////
// Start of adding a new project //
///////////////////////////////////

} elseif ($_POST['action'] == "newproject" && $_SESSION['admin'] == '1') {

  if ($_POST['project_title'] != '') {

    $insert = $fs->dbQuery("INSERT INTO flyspray_projects
                              (project_title, theme_style, show_logo, inline_images,
                              default_cat_owner, intro_message, project_is_active, visible_columns)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            array($_POST['project_title'],
                              $_POST['theme_style'],
                              $fs->emptyToZero($_POST['show_logo']),
                              $fs->emptyToZero($_POST['inline_images']),
                              $_POST['default_cat_owner'],
                              $_POST['intro_message'],
                              '1',
                              'id tasktype severity summary status dueversion progress',
                              ));

    $newproject = $fs->dbFetchArray($fs->dbQuery("SELECT project_id FROM flyspray_projects ORDER BY project_id DESC limit 1"));

    $insert = $fs->dbQuery("INSERT INTO flyspray_list_category
                             (project_id, category_name, list_position,
                             show_in_list, category_owner)
                             VALUES ( ?, ?, ?, ?, ?)",
                        array($newproject['project_id'],
                           'Backend / Core', '1', '1', '0'));

    $insert = $fs->dbQuery("INSERT INTO flyspray_list_os
                             (project_id, os_name, list_position,
                             show_in_list)
                             VALUES (?,?,?,?)",
                             array($newproject['project_id'], 'All', '1', '1'));

    $insert = $fs->dbQuery("INSERT INTO flyspray_list_version
                             (project_id, version_name, list_position,
                             show_in_list, version_tense)
                             VALUES (?, ?, ?, ?, ?)",
                        array($newproject['project_id'], '1.0', '1', '1', '2'));

    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['projectcreated']}";
    echo "<br><br><a href=\"?do=admin&amp;area=projects&amp;show=prefs&amp;id={$newproject['project_id']}\">{$modify_text['customiseproject']}</a></em></p></div>";

  } else {

    echo "<div class=\"errormessage\"><p><em>{$modify_text['emptytitle']}</em></p></div>";

  };

// End of adding a new project

///////////////////////////////////////////
// Start of updating project preferences //
///////////////////////////////////////////

} elseif ($_POST['action'] == "updateproject" && $_SESSION['admin'] == '1') {

  if ($_POST['project_title'] != '') {

    $update = $fs->dbQuery("UPDATE flyspray_projects SET
                             project_title = ?,
                             theme_style = ?,
                             show_logo = ?,
                             inline_images = ?,
                             default_cat_owner = ?,
                             intro_message = ?,
                             project_is_active = ?
                             WHERE project_id = ?
                          ", array($_POST['project_title'],
                                    $_POST['theme_style'],
                                    $fs->emptyToZero($_POST['show_logo']),
                                    $fs->emptyToZero($_POST['inline_images']),
                                    $fs->emptyToZero($_POST['default_cat_owner']),
                                    $_POST['intro_message'],
                                    $fs->emptyToZero($_POST['project_is_active']),
                                    $_POST['project_id']));

    echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;id={$_POST['project_id']}&amp;show=prefs\">";
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['projectupdated']}</em></p></div>";

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
  $update = $fs->dbQuery("UPDATE flyspray_projects SET visible_columns = ? WHERE project_id = ?", array($columnlist, $_POST['project_id']));  
  
  
// End of updating project preferences

//////////////////////////////////////
// Start of uploading an attachment //
//////////////////////////////////////

} elseif ($_POST['action'] == "addattachment" && $_SESSION['can_attach_files'] == '1') {

     // This function came from ZenTracker http://zentrack.phpzen.net/
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
      $add_to_db = $fs->dbQuery("INSERT INTO flyspray_attachments
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

      $getdetails = $fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = ?", array($_POST['task_id']));
      $task_details = $fs->dbFetchArray($getdetails);

      $item_summary = stripslashes($task_details['item_summary']);

      if ($task_details['assigned_to'] != "0"
         && ($task_details['assigned_to'] != $_COOKIE['flyspray_userid'])
         ) {

$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$basic_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasuploaded']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs";


        $result = $fs->SendBasicNotification($task_details['assigned_to'], $subject, $basic_message);
        echo $result;

      };

$subject = "{$modify_text['flyspraytask']} #{$_POST['task_id']} - $item_summary";
$detailed_message = "{$modify_text['noticefrom']} {$project_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasattached']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['filename']} {$_FILES['userfile']['name']}
{$modify_text['description']} $file_desc \n
{$flyspray_prefs['base_url']}index.php?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs";


        $result = $fs->SendDetailedNotification($_POST['task_id'], $subject, $detailed_message);
        echo $result;
        
        $row = $fs->dbFetchRow($fs->dbQuery("SELECT attachment_id FROM flyspray_attachments WHERE task_id = ? ORDER BY attachment_id DESC LIMIT 1", array($_POST['task_id'])));        
        $fs->logEvent($_POST['task_id'], 7, $row['attachment_id']);

      // Success message!
      echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs\">";
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fileuploaded']}</em></p?<p>{$modify_text['waitwhiletransfer']}</p></div>";

    // If the file didn't actually get saved, better show an error to that effect
    } else {
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fileerror']}</em></p><p>{$modify_text['contactadmin']}</p></div>";
    };

  // If there wasn't a file uploaded with a description, show an error
  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['selectfileerror']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };
// End of uploading an attachment

/////////////////////////////////////
// Start of modifying user details //
/////////////////////////////////////

} elseif ($_POST['action'] == "edituser" && ($_SESSION['admin'] == '1' OR ($_COOKIE['flyspray_userid'] == $_POST['user_id']))) {

  // If they filled in all the required fields
  if ($_POST['real_name'] != ""
    && $_POST['email_address'] != ""
    ) {
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
            $update_pass = $fs->dbQuery("UPDATE flyspray_users SET user_pass = '$new_pass_hash' WHERE user_id = ?", array($_POST['user_id']));

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

      if ($password_problem == false){
        $update = $fs->dbQuery("UPDATE flyspray_users SET
                  real_name = ?,
                  email_address = ?,
                  jabber_id = ?,
                  notify_type = ?,
                  dateformat = ?,
                  dateformat_extended = ?
        WHERE user_id = ?",
        array($_POST['real_name'], $_POST['email_address'],
        $_POST['jabber_id'], $_POST['notify_type'], $_POST['dateformat'], $_POST['dateformat_extended'], $_POST['user_id']));

      if ($_SESSION['admin'] == '1') {
        $update = $fs->dbQuery("UPDATE flyspray_users SET
                  group_in = ?,
                  account_enabled = ?
        WHERE user_id = ?",
        array($_POST['group_in'],
                $fs->emptyToZero($_POST['account_enabled']),
                $_POST['user_id']));
      };

      echo "<meta http-equiv=\"refresh\" content=\"0; URL=index.php\">";
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['userupdated']}</em></p></div>";
    };
  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['realandemail']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };
// End of modifying user details

/////////////////////////////////////////
// Start of modifying group definition //
/////////////////////////////////////////

} elseif ($_POST['action'] == "editgroup" && $_SESSION['admin'] == '1') {

  if ($_POST['group_name'] != ''
    && $_POST['group_desc'] != ''
    ) {
      $update = $fs->dbQuery("UPDATE flyspray_groups SET
                  group_name = ?,
                  group_desc = ?,
                  is_admin = ?,
                  can_open_jobs = ?,
                  can_modify_jobs = ?,
                  can_add_comments = ?,
                  can_attach_files = ?,
                  can_vote = ?,
                  group_open = ?
      WHERE group_id = ?",
      array($_POST['group_name'], $_POST['group_desc'],
              $fs->emptyToZero($_POST['is_admin']),
              $fs->emptyToZero($_POST['can_open_jobs']),
              $fs->emptyToZero($_POST['can_modify_jobs']),
              $fs->emptyToZero($_POST['can_add_comments']),
              $fs->emptyToZero($_POST['can_attach_files']),
              $fs->emptyToZero($_POST['can_vote']),
              $fs->emptyToZero($_POST['group_open']),
              $_POST['group_id']));
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=users\">";
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupupdated']}</em></p></div>";
  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['groupanddesc']}</em></p><p><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></p></div>";
  };
// End of updating group definition

//////////////////////////////
// Start of updating a list //
//////////////////////////////

} elseif ($_POST['action'] == "update_list" && $_SESSION['admin'] == '1') {

  $listname = $_POST['list_name'];
  $listposition = $_POST['list_position'];
  $listshow = $_POST['show_in_list'];
  $listid = $_POST['id'];
  
  $redirectmessage = $modify_text['listupdated'];

  for($i = 0; $i < count($listname); $i++) {
      $listname[$i] = stripslashes($listname[$i]);
      if($listname[$i] != ''
          && is_numeric($listposition[$i])
          ) {
          $update = $fs->dbQuery("UPDATE $list_table_name SET
                                    $list_column_name = ?,
                                    list_position = ?,
                                    show_in_list = ?
          WHERE $list_id = '{$listid[$i]}'",
          array($listname[$i], $listposition[$i],
                $fs->emptyToZero($listshow[$i])
                ));
      }
      else {
          $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
      };
  };
  if($_POST['project_id'] != '') {
      echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;show={$_POST['list_type']}&amp;id={$_POST['project_id']}\">";
  } else {
      echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area={$_POST['list_type']}\">";
  };
  echo "<div class=\"redirectmessage\"><p><em>{$redirectmessage}</em></p></div>";

// End of updating a list

/////////////////////////////////
// Start of adding a list item //
/////////////////////////////////

} elseif ($_POST['action'] == "add_to_list" && $_SESSION['admin'] == '1') {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      if ($_POST['project_id'] != '') {

      $update = $fs->dbQuery("INSERT INTO $list_table_name
                        (project_id, $list_column_name, list_position, show_in_list)
                        VALUES (?, ?, ?, ?)",
                array($_POST['project_id'], $_POST['list_name'], $_POST['list_position'], '1'));

        echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;show={$_POST['list_type']}&amp;id={$_POST['project_id']}\">";

          } else {

      $update = $fs->dbQuery("INSERT INTO $list_table_name
                                ($list_column_name, list_position, show_in_list)
                                VALUES (?, ?, ?)",
                array($_POST['list_name'], $_POST['list_position'], '1'));

        echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area={$_POST['list_type']}\">";

      };

      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['listitemadded']}</em></p></div>";

  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fillallfields']}</em></p></div>";
  };
// End of adding a list item

////////////////////////////////////////
// Start of updating the version list //
////////////////////////////////////////

} elseif ($_POST['action'] == "update_version_list" && $_SESSION['admin'] == '1') {

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
          $update = $fs->dbQuery("UPDATE $list_table_name SET
                                    $list_column_name = ?,
                                    list_position = ?,
                                    show_in_list = ?,
                                    version_tense = ?
          WHERE $list_id = '{$listid[$i]}'",
          array($listname[$i], $listposition[$i],
                $fs->emptyToZero($listshow[$i]),
                $listtense[$i]
                ));
      }
      else {
          $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
      };
  };

  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;show={$_POST['list_type']}&amp;id={$_POST['project_id']}\">";
  echo "<div class=\"redirectmessage\"><p><em>{$redirectmessage}</em></p></div>";

// End of updating the version list

/////////////////////////////////////////
// Start of adding a version list item //
/////////////////////////////////////////

} elseif ($_POST['action'] == "add_to_version_list" && $_SESSION['admin'] == '1') {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {

      $update = $fs->dbQuery("INSERT INTO $list_table_name
                        (project_id, $list_column_name, list_position, show_in_list, version_tense)
                        VALUES (?, ?, ?, ?, ?)",
                array($_POST['project_id'], $_POST['list_name'], $_POST['list_position'], '1', $_POST['version_tense']));

      echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;show={$_POST['list_type']}&amp;id={$_POST['project_id']}\">";

      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['listitemadded']}</em></p></div>";

  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fillallfields']}</em></p></div>";
  };
// End of adding a version list item



////////////////////////////////////////////
// Start of updating the category list    //
// Category lists are slightly different, //
// requiring their own update section     //
////////////////////////////////////////////

} elseif ($_POST['action'] == "update_category" && $_SESSION['admin'] == '1') {

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
          $update = $fs->dbQuery("UPDATE flyspray_list_category SET
                                    category_name = ?,
                                    list_position = ?,
                                    show_in_list = ?,
                                    category_owner = ?
                              WHERE category_id = ?",
                              array($listname[$i], $listposition[$i],
                              $fs->emptyToZero($listshow[$i]),
                              $fs->emptyToZero($listowner[$i]),
                              $listid[$i]));
      }
      else {
          $redirectmessage = $modify_text['listupdated'] . " " . $modify_text['fieldsmissing'];
      };
  };
  
  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;id={$_POST['project_id']}&amp;show=category\">";
  echo "<div class=\"redirectmessage\"><p><em>{$redirectmessage}</em></p></div>";

// End of updating the category list

//////////////////////////////////////////
// Start of adding a category list item //
//////////////////////////////////////////

} elseif ($_POST['action'] == "add_category" && $_SESSION['admin'] == '1') {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      $update = $fs->dbQuery("INSERT INTO flyspray_list_category
                                (project_id, category_name, list_position,
                                show_in_list, category_owner, parent_id)
                                VALUES (?, ?, ?, ?, ?, ?)",
                        array($_POST['project_id'], $_POST['list_name'],
                        $_POST['list_position'], '1',
                        $_POST['category_owner'], 
			$fs->emptyToZero($_POST['parent_id'])));
      echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=admin&amp;area=projects&amp;id={$_POST['project_id']}&amp;show=category\">";
      echo "<div class=\"redirectmessage\"><p><em>{$modify_text['listitemadded']}</em></p></div>";
  } else {
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['fillallfields']}</em></p></div>";
  };
// End of adding a category list item

//////////////////////////////////////////
// Start of adding a related task entry //
//////////////////////////////////////////

} elseif ($_POST['action'] == "add_related" && $_SESSION['can_modify_jobs'] == '1') {
    
  if (is_numeric($_POST['related_task'])) {  
    $check = $fs->dbQuery("SELECT * FROM flyspray_related
        WHERE this_task = ?
        AND related_task = ?",
        array($_POST['this_task'], $_POST['related_task']));
    $check2 = $fs->dbQuery("SELECT attached_to_project FROM flyspray_tasks
        WHERE task_id = ?",
        array($_POST['related_task']));
        
    if ($fs->dbCountRows($check) > 0) {
        echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatederror']}</em></p></div>";
    } elseif (!$fs->dbCountRows($check2)) {
        echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
        echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedinvalid']}</em></p></div>";
    } else {
        list($relatedproject) = $fs->dbFetchRow($check2);
        if ($project_id == $relatedproject || isset($_POST['allprojects'])) {
            $insert = $fs->dbQuery("INSERT INTO flyspray_related (this_task, related_task) VALUES(?,?)", array($_POST['this_task'], $_POST['related_task']));
            
            $fs->logEvent($_POST['this_task'], 11, $_POST['related_task']);
            $fs->logEvent($_POST['related_task'], 15, $_POST['this_task']);
            
            echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
            echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedadded']}</em></p></div>";
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
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedinvalid']}</em></p></div>";
  };

// End of adding a related task entry

///////////////////////////////////
// Removing a related task entry //
///////////////////////////////////

} elseif ($_POST['action'] == "remove_related" && $_SESSION['can_modify_jobs'] == '1') {

  $remove = $fs->dbQuery("DELETE FROM flyspray_related WHERE related_id = ?", array($_POST['related_id']));
  
  $fs->logEvent($_POST['id'], 12, $_POST['related_task']);
  $fs->logEvent($_POST['related_task'], 16, $_POST['id']);
  
  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['id']}&amp;area=related#tabs\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['relatedremoved']}</em></p></div>";

// End of removing a related task entry

/////////////////////////////////////////////////////
// Start of adding a user to the notification list //
/////////////////////////////////////////////////////

} elseif ($_POST['action'] == "add_notification" && $_SESSION['userid']) {

  $check = $fs->dbQuery("SELECT * FROM flyspray_notifications
    WHERE task_id = ?  AND user_id = ?",
    array($_POST['task_id'], $_POST['user_id']));
  if (!$fs->dbCountRows($check)) {

    $insert = $fs->dbQuery("INSERT INTO flyspray_notifications (task_id, user_id) VALUES(?,?)",
    array($_POST['task_id'], $_POST['user_id']));
    
    $fs->logEvent($_POST['task_id'], 9, $_POST['user_id']);

    echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['notifyadded']}</em></p></div>";
  } else {
    echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
    echo "<div class=\"redirectmessage\"><p><em>{$modify_text['notifyerror']}</em></p></div>";
  };

// End of adding a user to the notification list

////////////////////////////////////////////
// Start of removing a notification entry //
////////////////////////////////////////////

} elseif ($_POST['action'] == "remove_notification" && $_SESSION['userid']) {

  $remove = $fs->dbQuery("DELETE FROM flyspray_notifications WHERE task_id = ? AND user_id = ?",
    array($_POST['task_id'], $_POST['user_id']));
    
  $fs->logEvent($_POST['task_id'], 10, $_POST['user_id']);

  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['notifyremoved']}</em></p></div>";

// End of removing a notification entry

////////////////////////////////
// Start of editing a comment //
////////////////////////////////

} elseif ($_POST['action'] == "editcomment" && $_SESSION['admin'] == '1') {

  $update = $fs->dbQuery("UPDATE flyspray_comments
              SET comment_text = ?  WHERE comment_id = ?",
              array($_POST['comment_text'], $_POST['comment_id']));
              
  $fs->logEvent($_POST['task_id'], 5, $_POST['comment_text'], $_POST['previous_text'], $_POST['comment_id']);

  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['editcommentsaved']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";

// End of editing a comment

/////////////////////////////////
// Start of deleting a comment //
/////////////////////////////////

} elseif ($_POST['action'] == "deletecomment" && $_SESSION['admin'] == '1') {
  $row = $fs->dbFetchRow($fs->dbQuery('SELECT comment_text, user_id, date_added FROM flyspray_comments WHERE comment_id = ?', array($_POST['comment_id'])));
  $delete = $fs->dbQuery('DELETE FROM flyspray_comments WHERE comment_id = ?', array($_POST['comment_id']));
  
  $fs->logEvent($_POST['task_id'], 6, $row['user_id'], $row['comment_text'], $row['date_added']);

  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['commentdeleted']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";

// End of deleting a comment

/////////////////////////////////////
// Start of deleting an attachment //
/////////////////////////////////////

//  "Deleting attachments" code contributed by Harm Verbeek <info@certeza.nl>
} elseif ($_POST['action'] == "deleteattachment" && $_SESSION['admin'] == '1') {
// if an attachment needs to be deleted do it right now
  $delete = $fs->dbQuery('SELECT file_name, orig_name FROM flyspray_attachments
                            WHERE attachment_id = ?',
                            array($_POST['attachment_id']));
  if ($row = $fs->dbFetchArray($delete)) {
    @unlink("attachments/".$row['file_name']);
    $fs->dbQuery('DELETE FROM flyspray_attachments WHERE attachment_id = ?',
                    array($_POST['attachment_id']));
  }
  
  $fs->logEvent($_POST['task_id'], 8, $row['orig_name']);

  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['attachmentdeleted']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";

// End of deleting an attachment

////////////////////////////////
// Start of adding a reminder //
////////////////////////////////

} elseif ($_POST['action'] == "addreminder" && $_SESSION['can_modify_jobs'] == '1') {
  
  $now = date(U);
  
  $how_often = $_POST['timeamount1'] * $_POST['timetype1'];
  //echo "how often = $how_often<br>";
  //echo "now = $now<br>";
  
  $start_time = ($_POST['timeamount2'] * $_POST['timetype2']) + $now;
  //echo "start time = $start_time";
  
  $insert = $fs->dbQuery("INSERT INTO flyspray_reminders (task_id, to_user_id, from_user_id, start_time, how_often, reminder_message) VALUES(?,?,?,?,?,?)", array($_POST['task_id'], $_POST['to_user_id'], $_SESSION['userid'], $start_time, $how_often, $_POST['reminder_message']));
  
  $fs->logEvent($_POST['task_id'], 17, $_POST['to_user_id']);

  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=remind#tabs\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['reminderadded']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";

// End of adding a reminder

//////////////////////////////////
// Start of removing a reminder //
//////////////////////////////////
} elseif ($_POST['action'] == "deletereminder" && $_SESSION['can_modify_jobs'] == '1') {
  
  $reminder = $fs->dbFetchRow($fs->dbQuery("SELECT to_user_id FROM flyspray_reminders WHERE reminder_id = ?", array($_POST['reminder_id'])));
  $fs->dbQuery('DELETE FROM flyspray_reminders WHERE reminder_id = ?',
                    array($_POST['reminder_id']));
                    
  $fs->logEvent($_POST['task_id'], 18, $reminder['to_user_id']);
  
  echo "<meta http-equiv=\"refresh\" content=\"0; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=remind#tabs\">";
  echo "<div class=\"redirectmessage\"><p><em>{$modify_text['reminderdeleted']}</em></p><p>{$modify_text['waitwhiletransfer']}</p></div>";

// End of removing a reminder

// End of actions.
};

?>
