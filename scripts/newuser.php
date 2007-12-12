<?php
include('../header.php');

// Get the application preferences into an array
$flyspray_prefs = $fs->getGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
require("../lang/$lang/newuser.php");
header('Content-type: text/html; charset=utf-8');

// Make sure that only admins are using this page, unless
// The application preferences allow anonymous signups
if ($_SESSION['admin'] == "1" OR ($flyspray_prefs['anon_open'] != "0" && !$_SESSION['userid'])) {
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<head>
  <title>Flyspray: <?php echo $newuser_text['registernewuser'];?></title>
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

<h3 class="subheading"><?php echo $newuser_text['registernewuser'];?></h3>
<i class="admintext"><?php echo $newuser_text['requiredfields'];?></i> <font color="red">*</font>

    <form name="form1" action="modify.php" method="post">
  <table class="admin">
    <tr>
      <td class="adminlabel">
    <input type="hidden" name="action" value="newuser">
      <?php echo $newuser_text['username'];?></td>
      <td align="left"><input class="admintext" name="user_name" type="text" size="20" maxlength="20"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newuser_text['password'];?></td>
      <td align="left"><input class="admintext" name="user_pass" type="password" size="20" maxlength="100"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newuser_text['confirmpass'];?></td>
      <td align="left"><input class="admintext" name="user_pass2" type="password" size="20" maxlength="100"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newuser_text['realname'];?></td>
      <td align="left"><input class="admintext" name="real_name" type="text" size="20" maxlength="100"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newuser_text['emailaddress'];?></td>
      <td align="left"><input class="admintext" name="email_address" type="text" size="20" maxlength="100"><font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newuser_text['jabberid'];?></td>
      <td align="left"><input class="admintext" name="jabber_id" type="text" size="20" maxlength="100"></td>
    </tr>
    <tr>
      <td class="adminlabel" valign="top"><?php echo $newuser_text['notifications'];?></td>
      <td class="admintext" align="left">
      <input class="admintext" type="radio" name="notify_type" value="0" CHECKED><?php echo $newuser_text['none'];?> <br>
      <input class="admintext" type="radio" name="notify_type" value="1"><?php echo $newuser_text['email'];?> <br>
      <input class="admintext" type="radio" name="notify_type" value="2"><?php echo $newuser_text['jabber'];?> <br>
      </td>
    </tr>
    <?php if ($_SESSION['admin'] == "1") { ?>
    <tr>
      <td class="adminlabel"><?php echo $newuser_text['group'];?></td>
      <td align="left">
      <select class="adminlist" name="group_in">
      <?php // Get the group names
      $get_group_details = $fs->dbQuery("SELECT group_id, group_name FROM flyspray_groups ORDER BY group_id ASC");
      while ($row = $fs->dbFetchArray($get_group_details)) {
        echo "<option value=\"{$row['group_id']}\">{$row['group_name']}</option>";
      };
      ?>
      </select>
      </td>
    </tr>
    <?php
    };
    ?>
    <tr>
      <td colspan="2" align="center">
      <br>
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $newuser_text['registeraccount'];?>" onclick="Disable()">
      </td>
    </tr>
  </table>
    </form>

</div>
</body>
</html>

<?php
};
?>
