<?php
// This script performs all database modifications

// Include config files, depending on where we're coming from
// Also include the html headings etc
if (!$_POST['do']) {
  include('../header.php');
  session_start();

  $flyspray_prefs = $fs->GetGlobalPrefs();

  $lang = $flyspray_prefs['lang_code'];
  require("../lang/$lang/modify.php");

  header('Content-type: text/html; charset=utf-8');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Flyspray:: <?php echo $modify_text['modify'];?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link href="../themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
</head>
<body>
<div align="center">


<?php
} else {
  //$flyspray_prefs = $fs->GetGlobalPrefs();
  $lang = $flyspray_prefs['lang_code'];
  require("lang/$lang/modify.php");
};

// Find out the current user's name
$get_current_username = $fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$_COOKIE['flyspray_userid']}'");
list($current_username, $current_realname) = $fs->dbFetchArray($get_current_username);

$now = date(U);

// Adding a new task
if ($_POST['action'] == "newtask" && ($_SESSION['can_open_jobs'] == "1" OR $flyspray_prefs['anon_open'] == "1")) {

  // If they entered something in both the summary and detailed description
  if ($_POST['item_summary'] != ''
    && $_POST['detailed_desc'] != '')
  {

    $item_summary = addslashes($_POST['item_summary']);
    $detailed_desc = addslashes($_POST['detailed_desc']);

    $add_item = $fs->dbQuery("INSERT INTO flyspray_tasks VALUES
    (
    '',
    '{$_POST['task_type']}',
    '$now',
    '{$_COOKIE['flyspray_userid']}',
    '',
    '',
    '$item_summary',
    '$detailed_desc',
    '{$_POST['item_status']}',
    '{$_POST['assigned_to']}',
    '',
    '{$_POST['product_category']}',
    '{$_POST['product_version']}',
    '{$_POST['closedby_version']}',
    '{$_POST['operating_system']}',
    '{$_POST['task_severity']}',
    '',
    '',
    '0'
    )");

    // Now, let's get the task_id back, so that we can send a direct link
    // URL in the notification message
    $get_task_id = $fs->dbFetchArray($fs->dbQuery("SELECT task_id FROM flyspray_tasks
                                                                    WHERE item_summary = '$item_summary'
                                                                    AND detailed_desc = '$detailed_desc'
                                                                    ORDER BY task_id DESC LIMIT 1
                                "));
    $task_id = $get_task_id['task_id'];

    // Check if the new task was assigned to anyone
    if ($_POST['assigned_to'] != "0"
      && $_POST['assigned_to'] != $_COOKIE['flyspray_userid'])
    {

// Create the brief notification message
$message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasopened']}\n
{$modify_text['newtask']}: {$_POST['item_summary']} \n
{$modify_text['moreinfonew']} {$flyspray_prefs['base_url']}?do=details&amp;id=$task_id";

      // ...And send it off to let the person know about their task
      $result = $fs->SendBasicNotification($_POST['assigned_to'], $message);
      echo $result;

    };

    // OK, we also need to notify the category owner
    // First, see if there's an owner for this category
    $get_cat_details = $fs->dbQuery("SELECT category_name, category_owner FROM flyspray_list_category WHERE category_id = '{$_POST['product_category']}'");
    $cat_details = $fs->dbFetchArray($get_cat_details);

    // If this category has an owner, address the notification to them
    if ($cat_details['category_owner'] != '0') {
      $send_to = $cat_details['category_owner'];
    // Otherwise address it to the default category owner
    } else {
      $send_to = $flyspray_prefs['default_cat_owner'];
    };

    // Create the notification message
$message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
{$modify_text['newtaskcategory']} - \"{$cat_details['category_name']}\"
{$modify_text['categoryowner']}\n
{$modify_text['tasksummary']} {$_POST['item_summary']} \n
{$modify_text['moreinfonew']} {$flyspray_prefs['base_url']}?do=details&amp;id=$task_id";

      // ...And send it off to the category owner or default owner
      $result = $fs->SendBasicNotification($send_to, $message);
      echo $result;

?>

      <?php if ($_POST['addmore'] == "1") {
      ?>
      <META HTTP-EQUIV="refresh" CONTENT="2; URL=<?php echo $flyspray_prefs['base_url'];?>?do=newtask">
      <?php
      } else {
      ?>
      <META HTTP-EQUIV="refresh" CONTENT="2; URL=<?php echo $flyspray_prefs['base_url'];?>">
      <?php
      };
      ?>

      <table class="admin">
        <tr>
          <td class="admintext" align="center">
          <?php echo $modify_text['newtaskadded'];?><br><?php echo $modify_text['waitwhiletransfer'];?>
          <br>
          <?php if ($_POST['addmore'] == "on") {
          ?>
          <a href="?do=newitem"><?php echo $modify_text['clicknowait'];?></a>
          <?php
          } else {
          ?>
          <a href="?"><?php echo $modify_text['clicknowait'];?></a>
          <?php
          };
          ?>
          </td>
        </tr>
      </table>
<?php
  // If they didn't fill in both the summary and detailed description, show an error
  } else {
    echo "<table class=\"admin\"><tr><td class=\"admintext\">{$modify_text['summaryanddetails']}<br>";
    echo "{$modify_text['goback']}</td></tr></table>";
  };

// End of adding a new task.

// Start of modifying an existing task
} elseif ($_POST['action'] == "update" && $_SESSION['can_modify_jobs'] == "1") {
  
  // If they entered something in both the summary and detailed description
  if ($_POST['item_summary'] != ''
    && $_POST['detailed_desc'] != '')
  {
    // Get the existing task details before updating
    // We need them in order to generate the changed-task message
    $old_details = $fs->GetTaskDetails($_POST['task_id']);

    $item_summary = addslashes($_POST['item_summary']);
    $detailed_desc = addslashes($_POST['detailed_desc']);

    $add_item = $fs->dbQuery("UPDATE flyspray_tasks SET

                  task_type = '{$_POST['task_type']}',
                  item_summary = '$item_summary',
                  detailed_desc = '$detailed_desc',
                  item_status = '{$_POST['item_status']}',
                  assigned_to = '{$_POST['assigned_to']}',
                  product_category = '{$_POST['product_category']}',
                  product_version = '{$_POST['product_version']}',
                  closedby_version = '{$_POST['closedby_version']}',
                  operating_system = '{$_POST['operating_system']}',
                  task_severity = '{$_POST['task_severity']}',
                  last_edited_by = '{$_COOKIE['flyspray_userid']}',
                  last_edited_time = '$now',
                  percent_complete = '{$_POST['percent_complete']}'

                  WHERE task_id = '{$_POST['task_id']}'
                ");

    // Get the details of the task we just updated
    // To generate the changed-task message
    $new_details = $fs->GetTaskDetails($_POST['task_id']);

    // Now we compare old and new, mark the changed fields
    $field = array(
            "{$modify_text['summary']}"      =>  'item_summary',
            "{$modify_text['tasktype']}"    =>  'tasktype_name',
            "{$modify_text['category']}"    =>  'category_name',
            "{$modify_text['status']}"      =>  'status_name',
            "{$modify_text['operatingsystem']}"  =>  'os_name',
            "{$modify_text['severity']}"    =>  'severity_name',
            "{$modify_text['reportedversion']}"  =>  'reported_version_name',
            "{$modify_text['dueinversion']}"  =>  'due_in_version_name',
            "{$modify_text['percentcomplete']}"  =>  'percent_complete',
            "{$modify_text['details']}"      =>  'detailed_desc',
            );

    while (list($key, $val) = each($field)) {
      if ($old_details[$val] != $new_details[$val]) {
        $message = $message . "** " . $key . " " . stripslashes($new_details[$val]) . "\n";
        $send_me = "YES";
      } else {
        $message = $message . $key . " " . stripslashes($new_details[$val]) . "\n";
      };
    };

// Complete the modification notification
$message = "{$modify_text['messagefrom']} {$flyspray_prefs['project_title']}\n
$current_realname ($current_username) {$modify_text['hasjustmodified']} {$modify_text['youonnotify']}
{$modify_text['changedfields']}\n-----\n"
. $message .
"-----\n{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}\n\n";

      if ($send_me == "YES") {
        // Send the detailed notification message
        $result = $fs->SendDetailedNotification($_POST['task_id'], $message);
        echo $result;
      };

    // Check to see if the assignment has changed
    // Because we have to send a simple notification or two
    if ($_POST['old_assigned'] != $_POST['assigned_to']) {

      $item_summary = stripslashes($_POST['item_summary']);

      // If someone had previously been assigned this item, notify them of the change in assignment
      if ($_POST['old_assigned'] != "0") {

        if ($_POST['assigned_to'] == "0") {
          $new_realname = $modify_text['noone'];
          $new_username = $modify_text['unassigned'];
        };

        // Generate thebrief notification message to send
        $get_new = $fs->dbFetchArray($fs->dbQuery("SELECT user_name, real_name FROM flyspray_users WHERE user_id = '{$_POST['assigned_to']}'"));

        if ($get_new['user_name'] != '') {
          $new_username = $get_new['user_name'];
        } else {
          $new_username = "No-one";
        };

$message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
{$modify_text['nolongerassigned']} $new_real_name ($new_username).\n
{$modify_text['task']} #{$_POST['task_id']} ({$_POST['item_summary']})\n
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}";
        // End of generating a message

        // Send the brief notification message
        $result = $fs->SendBasicNotification($_POST['old_assigned'], $message);
        echo $result;
      };

      // If assignment isn't "none", notify the new assignee of their task
      if ($_POST['assigned_to'] != "0") {
  
        // Get the brief notification message to send
$message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasassigned']}\n
{$modify_text['task']} #{$_POST['task_id']}: {$_POST['item_summary']} \n
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}";

        // Send the brief notification message
        $result = $fs->SendBasicNotification($_POST['assigned_to'], $message);
        echo $result;

      };
    };

    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=?do=details&amp;id={$_POST['task_id']}\">";
    echo "<table class=\"admin\"><tr><td class=\"admintext\">{$modify_text['taskupdated']}<br>";
    echo "{$modify_text['waitwhiletransfer']}</td></tr></table>";

  // If they didn't fill in both the summary and detailed description, show an error
  } else {
    echo "<table class=\"admin\"><tr><td class=\"admintext\">{$modify_text['summaryanddetails']}<br>";
    echo "{$modify_text['goback']}</td></tr></table>";
  };

// End of updating an task

// Start of closing a task
} elseif($_POST['action'] == "close" && $_SESSION['can_modify_jobs'] == "1") {

  if ($_POST['resolution_reason'] != "1") {

    $close_item = $fs->dbQuery("UPDATE flyspray_tasks SET

    date_closed = '$now',
    closed_by = '{$_COOKIE['flyspray_userid']}',
    item_status = '8',
    resolution_reason = '{$_POST['resolution_reason']}'

    WHERE task_id = '{$_POST['task_id']}'

    ");

    // Get the resolution name for the notifications
    $get_res = $fs->dbFetchArray($fs->dbQuery("SELECT resolution_name FROM flyspray_list_resolution WHERE resolution_id = '{$_POST['resolution_reason']}'"));
    $item_summary = stripslashes($_POST['item_summary']);

    if ($_COOKIE['flyspray_userid'] != $_POST['assigned_to']) {

$brief_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasclosedassigned']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['reasonforclosing']} {$get_res['resolution_name']} \n
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']} \n";

      $result = $fs->SendBasicNotification($_POST['assigned_to'], $brief_message);
      echo $result;

    };
      // The detailed notification
$detailed_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasclosed']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['reasonforclosing']} {$get_res['resolution_name']} \n
{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']} \n";

      $result = $fs->SendDetailedNotification($_POST['task_id'], $detailed_message);
      echo $result;

    echo "<table class=\"admin\"><tr><td class=\"admintext\">{$modify_text['taskclosed']}<br>";
    echo "<a href=\"?do=details&amp;id={$_POST['task_id']}\">{$modify_text['returntotask']}</a><br><br>";
    echo "<a href=\"?\">{$modify_text['backtoindex']}</a></td></tr></table>";

  } else {
    echo "<table class=\"admin\"><tr><td class=\"admintext\">{$modify_text['noclosereason']}<br>{$modify_text['goback']}</td></tr></table>";
  };

// End of closing a task

// Start of re-opening an task
} elseif ($_POST['action'] == "reopen" && $_SESSION['can_modify_jobs'] == "1") {

    $add_item = $fs->dbQuery("UPDATE flyspray_tasks SET

    item_status = '7',
    resolution_reason = '1'

    WHERE task_id = '{$_POST['task_id']}'

    ");

    // Find out the user who closed this
    $get_closedby = $fs->dbQuery("SELECT item_summary, closed_by FROM flyspray_tasks WHERE task_id = '{$_POST['task_id']}'");
    list($item_summary, $closed_by) = $fs->dbFetchArray($get_closedby);

    $item_summary = stripslashes($item_summary);

    if ($closed_by != $_COOKIE['flyspray_userid']) {

      // Generate basic notification message to send
$brief_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasreopened']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}";


      $result = $fs->SendBasicNotification($closed_by, $brief_message);
      echo $result;

    };

      // Generate detailed notification message to send
$detailed_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasreopened']} {$modify_text['youonnotify']} \n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$modify_text['moreinfomodify']} {$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}";


      $result = $fs->SendDetailedNotification($_POST['task_id'], $detailed_message);
      echo $result;

    echo "<table class=\"admin\"><tr><td class=\"admintext\">{$modify_text['taskreopened']}<br><a href=\"?do=details&amp;id={$_POST['task_id']}\">{$modify_text['backtotask']}</a></td></tr></table>";

// End of re-opening an item

// Start of adding a comment

} elseif ($_POST['action'] == "addcomment" && $_SESSION['can_add_comments'] == "1") {

  if ($_POST['comment_text'] != "") {

    $comment = addslashes($_POST['comment_text']);

    $insert = $fs->dbQuery("INSERT INTO flyspray_comments VALUES
    (
    '',
    '{$_POST['task_id']}',
    '$now',
    '{$_COOKIE['flyspray_userid']}',
    '$comment'
    )");

    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
    echo "<table class=\"admin\"><tr><td class=\"admintext\">{$modify_text['commentadded']}<br>{$modify_text['waitwhiletransfer']}</td></tr></table>";

    $getdetails = $fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = '{$_POST['task_id']}'");
    $bug_details = $fs->dbFetchArray($getdetails);

    //$get_jabber_id = $fs->dbQuery("SELECT jabber_id FROM flyspray_users WHERE user_id = '{$bug_details['assigned_to']}'") or die ($fs->dbQuery());
    //list($jabber_id) = $fs->dbFetchArray($get_jabber_id);

    $item_summary = stripslashes($bug_details['item_summary']);

    if ($bug_details['assigned_to'] != "0"
       && ($bug_details['assigned_to'] != $_COOKIE['flyspray_userid'])
       ) {

      // Generate the basic notification message to send
$basic_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['commenttoassigned']}\n
{$modify_text['task']} #{$_POST['task_id']}: $summary \n
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs";


      $result = $fs->SendBasicNotification($bug_details['assigned_to'], $basic_message);
      echo $result;

    };

      // Generate the detailed notification message to send
$detailed_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['commenttotask']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
-----\n {$_POST['comment_text']} \n -----
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs";


      $result = $fs->SendDetailedNotification($_POST['task_id'], $detailed_message);
      echo $result;

  // If they pressed submit without actually typing anything
  } else {
    echo "<table><tr><td class=\"admintext\">{$modify_text['nocommententered']}</td></tr></table>";
  };


// End of adding a comment

// Start of changing a user's password
// This section is called directly, not as part of another page.
} elseif ($_POST['action'] == "chpass" && $_COOKIE['flyspray_userid'] && $_SESSION['userid']) {

  // get the password hash out of the db and hash the new one
  $get_pass_hash = $fs->dbQuery("SELECT user_pass FROM flyspray_users WHERE user_id = '{$_COOKIE['flyspray_userid']}'");
  list($db_pass_hash) = $fs->dbFetchArray($get_pass_hash);
  $old_pass = $_POST['old_pass'];
  $old_pass_hash = crypt("$old_pass", "4t6dcHiefIkeYcn48B");
  $new_pass = $_POST['new_pass'];
  $new_pass_hash = crypt("$new_pass", "4t6dcHiefIkeYcn48B");
  $confirm_pass = $_POST['confirm_pass'];

  // If they didn't fill in all the fields, show an error
  if (!($_POST['old_pass'] && $_POST['new_pass'] && $_POST['confirm_pass'])) {
    echo "<table><tr><td class=\"admintext\" align=\"center\"><br>";
    echo "<a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";


  // Compare the old password with the db to see if it matches
  } elseif ($old_pass_hash != $db_pass_hash) {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['notcurrentpass']}<br>";
    echo "<a href=\"javascript:history.back();\">{$modify_text['goback']}</a><br></td></tr></table>";

  // if neither of the above two conditions are true, then process the password change
  } else {

    // If the two new passwords are the same
    if ($new_pass == $confirm_pass) {
      $update_pass = $fs->dbQuery("UPDATE flyspray_users SET user_pass = '$new_pass_hash' WHERE user_id = '{$_COOKIE['flyspray_userid']}'");
      //setcookie('flyspray_passhash', crypt("$new_pass_hash", "4t6dcHiefIkeYcn48B"), time()+60*60*24*30, "/");
      echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['passchanged']}<br>";
      echo "{$modify_text['closewindow']}</td></tr></table>";

    // if the two new passwords aren't the same, show an error
    } else {
      echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['passnomatch']}<br>";
      echo "<a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
    };

  };
// End of changing a user's password

// Start of new user registration
} elseif ($_POST['action'] == "registeruser" && $flyspray_prefs['anon_open'] != '0') {

  // If they filled in all the required fields
  if ($_POST['user_pass'] != ''
    && $_POST['user_pass2'] != ''
    ) {

      // If the passwords matched
      if (($_POST['user_pass'] == $_POST['user_pass2']) && $_POST['user_pass'] != '') {
        //echo "reg_ref = {$_SESSION['reg_ref']}<br>";
        // Check that the user entered the right confirmation code
        $code_check = $fs->dbQuery("SELECT * FROM flyspray_registrations WHERE reg_time = '{$_SESSION['reg_ref']}'");
        $code_details = $fs->dbFetchArray($code_check);
        //echo "db = {$code_details['confirm_code']}<br>";
        //echo "posted = {$_POST['confirmation_code']}<br>";
        if ($code_details['confirm_code'] == $_POST['confirmation_code']) {

          $pass_hash = crypt("{$_POST['user_pass']}", "4t6dcHiefIkeYcn48B");

          $add_user = $fs->dbQuery("INSERT INTO flyspray_users VALUES(
                                      '',
                                      '{$_POST['user_name']}',
                                      '$pass_hash',
                                      '{$_POST['real_name']}',
                                      '{$flyspray_prefs['anon_group']}',
                                      '{$_POST['jabber_id']}',
                                      '{$_POST['email_address']}',
                                      '{$_POST['notify_type']}',
                                      '1'
                                      )");
          echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['accountcreated']}<br>";
          echo "{$modify_text['closewindow']}<br><br>";
          echo "{$modify_text['newuserwarning']}</td></tr></table>";
          session_destroy();

        // If they didn't enter the right confirmation code
        } else {
          echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['confirmwrong']}<br>";
          echo "<a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
        };


      // If passwords didn't match
      } else {
        echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['nomatchpass']}<br><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
      };
  // If they didn't fill in all the fields
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['formnotcomplete']}<br><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
  };
// End of registering a new user
						
// Start of adding a new user by an admin
} elseif ($_POST['action'] == "newuser" && $_SESSION['admin'] == '1') {

  // If they filled in all the required fields
  if ($_POST['user_name'] != ""
    && $_POST['user_pass'] != ""
    && $_POST['user_pass2'] != ""
    && $_POST['real_name'] != ""
    && $_POST['email_address'] != ""
    ) {

    // Check to see if the username is available
    $check_username = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_name = '{$_POST['user_name']}'");
    if ($fs->dbCountRows($check_username)) {
      echo "<table><tr><td class=\"admintext\">{$modify_text['usernametaken']}<br>";
      echo "<a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
    } else {

      // If the passwords matched, add the user
      if (($_POST['user_pass'] == $_POST['user_pass2']) && $_POST['user_pass'] != '') {

        $pass_hash = crypt("{$_POST['user_pass']}", "4t6dcHiefIkeYcn48B");

        $add_user = $fs->dbQuery("INSERT INTO flyspray_users VALUES(
                                    '',
                                    '{$_POST['user_name']}',
                                    '$pass_hash',
                                    '{$_POST['real_name']}',
                                    '{$_POST['group_in']}',
                                    '{$_POST['jabber_id']}',
                                    '{$_POST['email_address']}',
                                    '{$_POST['notify_type']}',
                                    '1'
                                    )");
        echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['newusercreated']}<br>";
        echo "{$modify_text['closewindow']}<br><br>";

      } else {
        echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['nomatchpass']}<br><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
      };

    };

  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['formnotcomplete']}<br><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
  };
// End of adding a new user by an admin

// Start of adding a new group
} elseif ($_POST['action'] == "newgroup" && ($_SESSION['admin'] == '1' OR $flyspray_prefs['anon_open'] == '2')) {

  // If they filled in all the required fields
  if ($_POST['group_name'] != ""
    && $_POST['group_desc'] != ""
    ) {

    // Check to see if the group name is available
    $check_groupname = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_name = '{$_POST['group_name']}'");
    if ($fs->dbCountRows($check_groupname)) {
      echo "<table><tr><td>{$modify_text['groupnametaken']}<br><a href=\"javascript:history.back();\">{$modify_text['goback']}</a></td></tr></table>";
    } else {

      $add_group = $fs->dbQuery("INSERT INTO flyspray_groups VALUES(
                                      '',
                                      '{$_POST['group_name']}',
                                      '{$_POST['group_desc']}',
                                      '{$_POST['is_admin']}',
                                      '{$_POST['can_open_jobs']}',
                                      '{$_POST['can_modify_jobs']}',
                                      '{$_POST['can_add_comments']}',
                                      '{$_POST['can_attach_files']}',
                                      '{$_POST['can_vote']}',
                                      '{$_POST['group_open']}'
                                      )");

        echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['newgroupadded']}<br>{$modify_text['closewindow']}</td></tr></table>";
    };

  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['formnotcomplete']}<br><a href=\"javascript:history.back();\">{$modify_text['goback']}</a>.</td></tr></table>";
  };
// End of adding a new group

// Update the application preferences
} elseif ($_POST['action'] == "globaloptions" && $_SESSION['admin'] == '1') {

  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['anon_open']}' WHERE pref_name = 'anon_open'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['theme_style']}' WHERE pref_name = 'theme_style'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['jabber_server']}' WHERE pref_name = 'jabber_server'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['jabber_port']}' WHERE pref_name = 'jabber_port'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['jabber_username']}' WHERE pref_name = 'jabber_username'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['jabber_password']}' WHERE pref_name = 'jabber_password'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['anon_group']}' WHERE pref_name = 'anon_group'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['project_title']}' WHERE pref_name = 'project_title'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['base_url']}' WHERE pref_name = 'base_url'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['user_notify']}' WHERE pref_name = 'user_notify'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['admin_email']}' WHERE pref_name = 'admin_email'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['default_cat_owner']}' WHERE pref_name = 'default_cat_owner'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['lang_code']}' WHERE pref_name = 'lang_code'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['spam_proof']}' WHERE pref_name = 'spam_proof'");
  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '{$_POST['anon_view']}' WHERE pref_name = 'anon_view'");
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

  $update = $fs->dbQuery("UPDATE flyspray_prefs SET pref_value = '$assigned_groups' WHERE pref_name = 'assigned_groups'");

  echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['optionssaved']}<br></td></tr></table>";

// End of updating application preferences

// Start of uploading an attachment
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
  if ($_FILES['userfile']['name'] && $_POST['file_desc'] != "") {

    // Then move the uploaded file into the attachments directory and remove exe permissions
    @move_uploaded_file($_FILES['userfile']['tmp_name'], "attachments/$file_name");
    @chmod("attachments/$file_name", 0644);

    // Only add the listing to the database if the file was actually uploaded successfully
    if (file_exists("attachments/$file_name")) {

      $file_desc = addslashes($_POST['file_desc']);
      $add_to_db = $fs->dbQuery("INSERT INTO flyspray_attachments VALUES (
                        '',
                        '{$_POST['task_id']}',
                        '{$_FILES['userfile']['name']}',
                        '$file_name',
                        '$file_desc',
                        '{$_FILES['userfile']['type']}',
                        '{$_FILES['userfile']['size']}',
                        '{$_COOKIE['flyspray_userid']}',
                        '$now'
                        )");

      $getdetails = $fs->dbQuery("SELECT * FROM flyspray_tasks WHERE task_id = '{$_POST['task_id']}'");
      $bug_details = $fs->dbFetchArray($getdetails);

      $item_summary = stripslashes($bug_details['item_summary']);

      if ($bug_details['assigned_to'] != "0"
         && ($bug_details['assigned_to'] != $_COOKIE['flyspray_userid'])
         ) {

$basic_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasuploaded']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary \n
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs";


        $result = $fs->SendBasicNotification($bug_details['assigned_to'], $basic_message);
        echo $result;

      };

$detailed_message = "{$modify_text['noticefrom']} {$flyspray_prefs['project_title']} \n
$current_realname ($current_username) {$modify_text['hasattached']} {$modify_text['youonnotify']}\n
{$modify_text['task']} #{$_POST['task_id']}: $item_summary
{$modify_text['filename']} {$_FILES['userfile']['name']}
{$modify_text['description']} $file_desc \n
{$flyspray_prefs['base_url']}?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs";


        $result = $fs->SendDetailedNotification($_POST['task_id'], $detailed_message);
        echo $result;

      echo "<table><tr><td class=\"admintext\" align=\"left\">{$modify_text['fileuploaded']}<br><a href=\"?do=details&amp;id={$_POST['task_id']}&amp;area=attachments#tabs\">{$modify_text['goback']}</a></td></tr></table>";

    // If the file didn't actually get saved, better show an error to that effect
    } else {
      echo "<table><tr><td class=\"admintext\" align=\"left\">{$modify_text['fileerror']}<br>{$modify_text['contactadmin']}</td></tr></table>";
    };

  // If there wasn't a file uploaded with a description, show an error
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['selectfileerror']}<br>{$modify_text['goback']}</td></tr></table>";
  };
// End of uploading an attachment

// Start of modifying user details
} elseif ($_POST['action'] == "edituser" && ($_SESSION['admin'] == '1' OR ($_COOKIE['flyspray_userid'] == $_POST['user_id']))) {

  // If they filled in all the required fields
  if ($_POST['real_name'] != ""
    && $_POST['email_address'] != ""
    ) {
      $update = $fs->dbQuery("UPDATE flyspray_users SET
                  real_name = '{$_POST['real_name']}',
                  email_address = '{$_POST['email_address']}',
                  jabber_id = '{$_POST['jabber_id']}',
                  notify_type = '{$_POST['notify_type']}'

      WHERE user_id = '{$_POST['user_id']}'");

    if ($_SESSION['admin'] == '1') {
      $update = $fs->dbQuery("UPDATE flyspray_users SET
                  group_in = '{$_POST['group_in']}',
                  account_enabled = '{$_POST['account_enabled']}'
      WHERE user_id = '{$_POST['user_id']}'");
    };
    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=admin&amp;area=users\">";
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['userupdated']}</td></tr></table>";
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['realandemail']}<br>{$modify_text['goback']}</td></tr></table>";
  };
// End of modifying user details

// Start of modifying group definition
} elseif ($_POST['action'] == "editgroup" && $_SESSION['admin'] == '1') {

  if ($_POST['group_name'] != ''
    && $_POST['group_desc'] != ''
    ) {
      $update = $fs->dbQuery("UPDATE flyspray_groups SET
                  group_name = '{$_POST['group_name']}',
                  group_desc = '{$_POST['group_desc']}',
                  is_admin = '{$_POST['is_admin']}',
                  can_open_jobs = '{$_POST['can_open_jobs']}',
                  can_modify_jobs = '{$_POST['can_modify_jobs']}',
                  can_add_comments = '{$_POST['can_add_comments']}',
                  can_attach_files = '{$_POST['can_attach_files']}',
                  can_vote = '{$_POST['can_vote']}',
                  group_open = '{$_POST['group_open']}'
      WHERE group_id = '{$_POST['group_id']}'");
    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=admin&amp;area=users\">";
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['groupupdated']}</td></tr></table>";
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['groupanddesc']}<br>{$modify_text['goback']}</td></tr></table>";
  };
// End of updating group definition

// Start of updating a list
} elseif ($_POST['action'] == "update_list" && $_SESSION['admin'] == '1') {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      $update = $fs->dbQuery("UPDATE flyspray_list_{$_POST['list_type']} SET
                                {$_POST['list_type']}_name = '{$_POST['list_name']}',
                                list_position = '{$_POST['list_position']}',
                                show_in_list = '{$_POST['show_in_list']}'
      WHERE {$_POST['list_type']}_id = '{$_POST['id']}'");
      echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=admin&amp;area={$_POST['list_type']}\">";
      echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['listupdated']}</td></tr></table>";
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['fillbothfields']}</td></tr></table>";
  };
// End of updating a list

// Start of adding a list item
} elseif ($_POST['action'] == "add_to_list" && $_SESSION['admin'] == '1') {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      $update = $fs->dbQuery("INSERT INTO flyspray_list_{$_POST['list_type']} VALUES (
                                '',
                                '{$_POST['list_name']}',
                                '{$_POST['list_position']}',
                                '1'
                                )");
      echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=admin&amp;area={$_POST['list_type']}\">";
      echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['listitemadded']}</td></tr></table>";
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['fillbothfields']}</td></tr></table>";
  };
// End of adding a list item

// Start of updating the category list
// Category lists are slightly different, requiring their own update section
} elseif ($_POST['action'] == "update_category" && $_SESSION['admin'] == '1') {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      $update = $fs->dbQuery("UPDATE flyspray_list_category SET
                                category_name = '{$_POST['list_name']}',
                                list_position = '{$_POST['list_position']}',
                                show_in_list = '{$_POST['show_in_list']}',
                                category_owner = '{$_POST['category_owner']}'
      WHERE category_id = '{$_POST['id']}'");
      echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=admin&amp;area=category\">";
      echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['listupdated']}</td></tr></table>";
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['fillallfields']}</td></tr></table>";
  };
// End of updating the category list

// Start of adding a category list item
} elseif ($_POST['action'] == "add_category" && $_SESSION['admin'] == '1') {

  if ($_POST['list_name'] != ''
    && $_POST['list_position'] != ''
    ) {
      $update = $fs->dbQuery("INSERT INTO flyspray_list_category VALUES (
                                '',
                                '{$_POST['list_name']}',
                                '{$_POST['list_position']}',
                                '1',
                                '{$_POST['category_owner']}'
                                )");
      echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=admin&amp;area=category\">";
      echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['listitemadded']}</td></tr></table>";
  } else {
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['fillallfields']}</td></tr></table>";
  };
// End of adding a category list item


// Start of adding a related task entry
} elseif ($_POST['action'] == "add_related" && $_SESSION['can_modify_jobs'] == '1') {

  $check = $fs->dbQuery("SELECT * FROM flyspray_related
    WHERE this_task = '{$_POST['this_task']}'
    AND related_task = '{$_POST['related_task']}'
  ");
  if (!$fs->dbCountRows($check)) {

    $insert = $fs->dbQuery("INSERT INTO flyspray_related VALUES('', '{$_POST['this_task']}', '{$_POST['related_task']}')");

    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['relatedadded']}</td></tr></table>";
  } else {
    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=?do=details&amp;id={$_POST['this_task']}&amp;area=related#tabs\">";
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['relatederror']}</td></tr></table>";
  };
  
// End of adding a related task entry

// Removing a related task entry
} elseif ($_POST['action'] == "remove_related" && $_SESSION['can_modify_jobs'] == '1') {

  $remove = $fs->dbQuery("DELETE FROM flyspray_related WHERE related_id = '{$_POST['related_id']}'");

  echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=details&amp;id={$_POST['id']}&amp;area=related#tabs\">";
  echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['relatedremoved']}</td></tr></table>";

// End of removing a related task entry

// Start of adding a user to the notification list
} elseif ($_POST['action'] == "add_notification" && $_SESSION['userid']) {
  
  $check = $fs->dbQuery("SELECT * FROM flyspray_notifications
    WHERE task_id = '{$_POST['task_id']}'
    AND user_id = '{$_POST['user_id']}'
  ");
  if (!$fs->dbCountRows($check)) {
  
    $insert = $fs->dbQuery("INSERT INTO flyspray_notifications VALUES('', '{$_POST['task_id']}', '{$_POST['user_id']}')");

    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['notifyadded']}</td></tr></table>";
  } else {
    echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"2; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
    echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['notifyerror']}</td></tr></table>";
  };
  
// End of adding a user to the notification list


// Start of removing a notification entry
} elseif ($_POST['action'] == "remove_notification" && $_SESSION['userid']) {
  
  $remove = $fs->dbQuery("DELETE FROM flyspray_notifications WHERE task_id = '{$_POST['task_id']}' AND user_id = '{$_POST['user_id']}'");

  echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=notify#tabs\">";
  echo "<table><tr><td class=\"admintext\" align=\"center\">{$modify_text['notifyremoved']}</td></tr></table>";

// End of removing a notification entry

// Start of editing a comment
} elseif ($_POST['action'] == "editcomment" && $_SESSION['admin'] == '1') {

  $update = $fs->dbQuery("UPDATE flyspray_comments
              SET comment_text = '{$_POST['comment_text']}'
              WHERE comment_id = '{$_POST['comment_id']}'");

  echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
  echo "<table><tr><td class=\"admintext\">{$modify_text['editcommentsaved']}<br>{$modify_text['waitwhiletransfer']}</td></tr></table>";

// End of editing a comment

// Start of deleting a comment
} elseif ($_POST['action'] == "deletecomment" && $_SESSION['admin'] == '1') {
  $delete = $fs->dbQuery("DELETE FROM flyspray_comments WHERE comment_id = '{$_POST['comment_id']}'");

  echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"1; URL=?do=details&amp;id={$_POST['task_id']}&amp;area=comments#tabs\">";
  echo "<table><tr><td class=\"admintext\">{$modify_text['commentdeleted']}<br>{$modify_text['waitwhiletransfer']}</td></tr></table>";

// End of deleting a comment


// End of actions.
};

// Finish off the html we started earlier
if (!$_POST['do']) {
  echo "</div></body></html>";
};
?>
