<?php
// This script authenticates the user, and sets up a session.
include('../header.php');
$flyspray_prefs = $fs->GetGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
require("../lang/$lang/authenticate.php");

// If logout was requested, log the user out.
if ($_GET['action'] == "logout") {
  session_start();
  session_destroy();
  setcookie('flyspray_userid', '', time()-60, '/');
  setcookie('flyspray_passhash', '', time()-60, '/');

  $message = $authenticate_text['youareloggedout'];

// Otherwise, they requested login.  See if they provided the correct credentials...
} elseif ($_POST['username'] AND $_POST['password']) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  // Get the user's account details
  $result = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_name = '$username'");
  $auth_details = $fs->dbFetchArray($result);
  // Get the user's group details
  $result = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = '{$auth_details['group_in']}'");
  $group_details = $fs->dbFetchArray($result);

  // Encrypt the password, and compare it to the one in the database
  if (crypt("$password", "4t6dcHiefIkeYcn48B") == $auth_details['user_pass']
    && $auth_details['account_enabled'] == "1"
    && $group_details['group_open'] == '1')
  {
    $message = $authenticate_text['loginsuccessful'];

    // Generate an extra hash of the already hashed password... for added security
    //$pass_double_hash = crypt("{$auth_details['user_pass']}", "4t6dcHiefIkeYcn48B");

    //session_start();
    //$_SESSION['userid'] = $auth_details['user_id'];
    //$_SESSION['username'] = $auth_details['user_name'];

    setcookie('flyspray_userid', $auth_details['user_id'], time()+60*60*24*30, "/");
    setcookie('flyspray_passhash', crypt("{$auth_details['user_pass']}", "4t6dcHiefIkeYcn48B"), time()+60*60*24*30, "/");

  } else {
    $message = $authenticate_text['loginfailed'];
  };

} else {
  // If the user didn't provide both a username and a password, show this error:
  $message = "{$authenticate_text['loginfailed']}<br>{$authenticate_text['userandpass']}";
};
header('Content-type: text/html; charset=utf-8');


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <link href="../themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css" />
    <?php if ($_POST['task']) { ?>
      <META HTTP-EQUIV="refresh" CONTENT="2; URL=../?do=details&id=<?php echo $_POST['task'];?>">
    <?php } else { ?>
      <META HTTP-EQUIV="refresh" CONTENT="2; URL=../">
    <?php };?>
    <title>Are we logged in yet?</title>
  </head>
  <body>
    <!-- Center the background canvas on the page -->
    <div align="center">
      <br>
      <!-- Message box -->
      <table class="admin" style="position: absolute; left: 40%; top:50%;">
        <tr>
          <td align="center" class="admintext">
          <?php echo "$message";?>
          <br>
          <?php if ($_POST['task']) {
            echo $authenticate_text['waitwhiletransfer'];
            ?>
            <br><br>
            <a href="../?do=details&id=<?php echo $_POST['task'];?>"><?php echo $authenticate_text['clicknowait'];?></a>
          <?php } else {
            echo $authenticate_text['waitwhiletransfer'];
            ?>
            <br><br>
            <a href="../"><?php echo $authenticate_text['clicknowait'];?></a>
          <?php };?>
          </td>
        </tr>
      </table>
      <br>

    <!-- End of the table that everything else goes into -->
    </div>
  </body>
</html>
