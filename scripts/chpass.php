<?php
include('../header.php');

// Get the application preferences into an array
$flyspray_prefs = $fs->getGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
require("../lang/$lang/chpass.php");

header('Content-type: text/html; charset=utf-8');
session_start();

if ($_SESSION['userid']) {

  $result = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = '{$_COOKIE['flyspray_userid']}'");
  $current_user = $fs->dbFetchArray($result);

  // Check that the user hasn't spoofed the cookie contents somehow
  if ($_COOKIE['flyspray_passhash'] == crypt($current_user['user_pass'], "4t6dcHiefIkeYcn48B")) {
  ?>

    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
      <title><?php echo $chpass_text['changeyourpass'];?></title>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
      <link href="../themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
    </head>

    <body>

    <div align="center">

      <form action="modify.php" method="post">
    <table class="admin" cellspacing="3">
      <tr>
        <input type="hidden" name="action" value="chpass">
        <td class="adminlabel" colspan="2" align="center"><?php echo $chpass_text['changeyourpass'];?></td>
      </tr>
      <tr>
        <td height="10">
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $chpass_text['current'];?>
        </td>
        <td><input class="admintext" name="old_pass" type="password" size="15" maxlength="15"></td>
      </tr>
      <tr>
        <td height="10">
        </td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $chpass_text['new'];?></td>
        <td><input class="admintext" name="new_pass" type="password" size="15" maxlength="15"></td>
      </tr>
      <tr>
        <td class="adminlabel"><?php echo $chpass_text['confirm'];?></td>
        <td><input class="admintext" name="confirm_pass" type="password" size="15" maxlength="15"></td>
      </tr>
      <tr>
        <td colspan="2" align="center">
        <br>
        <input class="adminbutton" type="submit" value="<?php echo $chpass_text['savepass'];?>">
        </td>
      </tr>
    </table>
      </form>

    </div>

    </body>
    </html>

<?php
} else {
  echo $chpass_text['nopermission'];
};

} else {
  echo $chpass_text['nopermission'];
};
