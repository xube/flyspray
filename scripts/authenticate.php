<?php
// This script authenticates the user, and sets up a session.
include('../header.php');
$flyspray_prefs = $fs->GetGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
get_language_pack($lang, 'authenticate');

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
  // Get the user's account and group details
  $result = $fs->dbQuery("SELECT * FROM flyspray_users, flyspray_groups 
			    WHERE user_name = ? AND group_id = group_in", 
			array($username));
  $auth_details = $fs->dbFetchArray($result);

  // Encrypt the password, and compare it to the one in the database
  if (crypt($password, '4t6dcHiefIkeYcn48B') == $auth_details['user_pass']
    && $auth_details['account_enabled'] == "1"
    && $auth_details['group_open'] == '1')
  {
    $message = $authenticate_text['loginsuccessful'];

    // Determine if the user should be remembered on this machine
    if ($_POST['remember_login']) {
      $cookie_time = time() + (60 * 60 * 24 * 30); // Set cookies for 30 days
    } else {
      $cookie_time = 0; // Set cookies to expire when session ends (browser closes)
    };

    setcookie('flyspray_userid', $auth_details['user_id'], $cookie_time, "/");
    setcookie('flyspray_passhash', crypt("{$auth_details['user_pass']}", "$cookiesalt"), $cookie_time, "/");
    
  } else {
    $message = $authenticate_text['loginfailed'];
  };

} else {
  // If the user didn't provide both a username and a password, show this error:
  $message = "{$authenticate_text['loginfailed']}<br>{$authenticate_text['userandpass']}";
};
header('Content-type: text/html; charset=utf-8');


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <link href="../themes/<?php echo $project_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
    <?php if ($_POST['task']) { ?>
  <meta http-equiv="refresh" content="2; URL=../?do=details&id=<?php echo $_POST['task'];?>">
    <?php } else { ?>
  <meta http-equiv="refresh" content="2; URL=../">
    <?php };?>
  <title>Are we logged in yet?</title>
</head>
<body>
<div id="loginmessage">
<h1>
  <?php echo "$message";?>
</h1>
<p>
  <?php if ($_POST['task']) {
  echo $authenticate_text['waitwhiletransfer'];
  ?>
</p>
<p>
  <a href="../?do=details&id=<?php echo $_POST['task'];?>"><?php echo $authenticate_text['clicknowait'];?></a>
  <?php } else {
  echo $authenticate_text['waitwhiletransfer'];
  ?>
  <br>
  <a href="../"><?php echo $authenticate_text['clicknowait'];?></a>
  <?php };?>
</p>
</div>
</body>
</html>
