<?php
include('../header.php');

// Get the application preferences into an array
$flyspray_prefs = $fs->getGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
require("../lang/$lang/register.php");
header('Content-type: text/html; charset=utf-8');

// The application preferences allow anonymous signups
if ($flyspray_prefs['anon_open'] != "0" && !$_SESSION['userid']) {
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
  <title>Flyspray: <?php echo $register_text['registernewuser'];?></title>
  <link href="../themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
  <script language="javascript" type="text/javascript">
    function Disable()
    {
    document.form1.buSubmit.disabled = true;
    document.form1.submit();
    }
  </script>
</head>

<body>

<div align="center">
<?php
// The first page of signup.
if (!$_GET['page']) {
?>

<b class="subheading"><?php echo $register_text['registernewuser'];?></b>
<br>
<i class="admintext"><?php echo $register_text['requiredfields'];?></i> <font color="red">*</font>

    <form name="form1" action="register.php" method="get">
  <table class="admin">
    <tr>
      <input type="hidden" name="page" value="2">
      <td class="adminlabel">
      <?php echo $register_text['username'];?></td>
      <td align="left"><input class="admintext" name="user_name" type="text" size="20" maxlength="20"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $register_text['realname'];?></td>
      <td align="left"><input class="admintext" name="real_name" type="text" size="20" maxlength="100"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $register_text['emailaddress'];?></td>
      <td align="left"><input class="admintext" name="email_address" type="text" size="20" maxlength="100"></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $register_text['jabberid'];?></td>
      <td align="left"><input class="admintext" name="jabber_id" type="text" size="20" maxlength="100"></td>
    </tr>
    <tr>
      <td class="adminlabel" valign="top"><?php echo $register_text['notifications'];?></td>
      <td class="admintext" align="left">
      <input class="admintext" type="radio" name="notify_type" value="1"><?php echo $register_text['email'];?> <br>
      <input class="admintext" type="radio" name="notify_type" value="2"><?php echo $register_text['jabber'];?> <br>
      </td>
    </tr>
    <tr>
      <td class="admintext" align="left" colspan="2">
      <br>
      <?php echo $register_text['note'];?>
      <br>
      </td>
    </tr>
    <tr>
      <td colspan="2" align="center">
      <br>
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $register_text['sendcode'];?>" onclick="Disable()">
      </td>
    </tr>
  </table>
    </form>

<?php
} elseif ($_GET['page'] == '2') {
  if ($_GET['user_name']
      && $_GET['real_name']
      && (($_GET['email_address'] != '' && $_GET['notify_type'] == '1')
           OR ($_GET['jabber_id'] != '' && $_GET['notify_type'] == '2'))
  ) {

    // Check to see if the username is available
    $check_username = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_name = '{$_GET['user_name']}'");
    if ($fs->dbCountRows($check_username)) {
      echo "<table class=\"admin\"><tr><td class=\"admintext\" align=\"left\">{$register_text['usernametaken']}<br><br>";
      echo "<a href=\"javascript:history.back();\">{$register_text['goback']}</a></td></tr></table>";
    } else {

    // Check that a confirmation code has been generated
    if (!$_SESSION['reg_ref']) {

      // Delete registration codes older than 24 hours
      $now = date(U);
      $yesterday = $now - '86400';
      $remove = $fs->dbQuery("DELETE FROM flyspray_registrations WHERE reg_time < '$yesterday'");

      // Generate a random bunch of numbers
      // This function came from ZenTrack http://zentrack.phpzen.net/
      function make_seed() {
         list($usec, $sec) = explode(' ', microtime());
         return (float) $sec + ((float) $usec * 100000);
      }
      mt_srand(make_seed());
      $randval = mt_rand();

      // Convert those numbers to a seemingly random string using crypt
      $code = crypt("$randval", "4t6dcHiefIkeYcn48B");

      // Store the registration reference in the session
      $_SESSION['reg_ref'] = $now;
      // Insert everything into the database
      $save_code = $fs->dbQuery("INSERT INTO flyspray_registrations VALUES ('', '$now', '$code')");

    // End of generating a confirmation code and storing it etc
    };

    // Since we're not guaranteed of having the code passed from the above statement...
    $get_code = $fs->dbQuery("SELECT * FROM flyspray_registrations WHERE reg_time = '{$_SESSION['reg_ref']}'");
	$code_details = $fs->dbFetchArray($get_code);

$message = "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}\n
{$register_text['addressused']}\n
{$code_details['confirm_code']}";

      // Check how they want to receive their code
      if ($_GET['notify_type'] == '1') {

      $fs->SendEmail(
                      $_GET['email_address'],
                      $message
                      );

      } elseif ($_GET['notify_type'] == '2') {
        $fs->JabberMessage(
                             $flyspray_prefs['jabber_server'],
                             $flyspray_prefs['jabber_port'],
                             $flyspray_prefs['jabber_username'],
                             $flyspray_prefs['jabber_password'],
                             $_GET['jabber_id'],
                             "{$register_text['noticefrom']} {$flyspray_prefs['project_title']}",
                             $message,
                             "Flyspray"
                             );
      };



?>
<b class="subheading"><?php echo $register_text['registernewuser'];?></b>
   <form action="modify.php" name="form1" method="post">
  <table class="admin">
    <tr>
      <input type="hidden" name="action" value="registeruser">

      <input type="hidden" name="user_name" value="<?php echo $_GET['user_name']; ?>">
      <input type="hidden" name="real_name" value="<?php echo $_GET['real_name']; ?>">
      <input type="hidden" name="email_address" value="<?php echo $_GET['email_address']; ?>">
      <input type="hidden" name="jabber_id" value="<?php echo $_GET['jabber_id']; ?>">
      <input type="hidden" name="notify_type" value="<?php echo $_GET['notify_type']; ?>">

      <td class="admintext" align="left" colspan="2">
      <br>
      <?php echo $register_text['entercode']; ?>
      <br><br>
      </td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $register_text['confirmationcode']; ?></td>
      <td align="left"><input class="admintext" name="confirmation_code" type="text" size="20" maxlength="20"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $register_text['password'];?></td>
      <td align="left"><input class="admintext" name="user_pass" type="password" size="20" maxlength="100"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $register_text['confirmpass'];?></td>
      <td align="left"><input class="admintext" name="user_pass2" type="password" size="20" maxlength="100"><font color="red">*</font></td>
    </tr>
    <tr>
      <td colspan="2">
      <br>
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $register_text['registeraccount'];?>" onclick="Disable()">
      </td>
    </tr>
  </table>
   
   </form>


<?php
// End of checking if the username is taken
};

// If they didn't fill the form in, show an error
} else {
?>

<table class="admin">
  <tr>
    <td class="admintext" align="left">
    <?php echo $register_text['registererror']; ?>
    <br><br>
    <a href="javascript:history.back();"><?php echo $register_text['goback']; ?></a>
    </td>
  </tr>
</table>

<?php
// End of checking that the user has filled in the form correctly
};

// End of pages
};
?>

</div>
</body>
</html>

<?php
};
?>
