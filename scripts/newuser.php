<?php
require("lang/$lang/newuser.php");

// Make sure that only admins are using this page, unless
// The application preferences allow anonymous signups
if ($_SESSION['admin'] == "1" OR ($flyspray_prefs['anon_open'] != "0" && !$_SESSION['userid'])) {
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
  <title>Flyspray: <?php echo $newuser_text['registernewuser'];?></title>
  <link href="../themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
  <script type="text/javascript"> <!--
    function Disable()
    {
    document.form1.buSubmit.disabled = true;
    document.form1.submit();
    }
//-->  </script>
</head>

<body>
<form name="form1" action="index.php" method="post" id="registernewuser">

<h1><?php echo $newuser_text['registernewuser'];?></h1>
<p>
  <em><?php echo $newuser_text['requiredfields'];?></em> <strong>*</strong>
</p>

<table class="admin">
  <tr>
    <td>
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="newuser">
      <label for="username"><?php echo $newuser_text['username'];?></label></td>
    <td><input id="username" name="user_name" type="text" size="20" maxlength="20"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass"><?php echo $newuser_text['password'];?></label></td>
      <td><input id="userpass" name="user_pass" type="password" size="20" maxlength="100"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass2"><?php echo $newuser_text['confirmpass'];?></label></td>
      <td><input id="userpass2" name="user_pass2" type="password" size="20" maxlength="100"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="realname"><?php echo $newuser_text['realname'];?></label></td>
      <td><input id="realname" name="real_name" type="text" size="20" maxlength="100"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="emailaddress"><?php echo $newuser_text['emailaddress'];?></label></td>
      <td><input id="emailaddress" name="email_address" type="text" size="20" maxlength="100"><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="jabberid"><?php echo $newuser_text['jabberid'];?></label></td>
      <td><input id="jabberid" name="jabber_id" type="text" size="20" maxlength="100"></td>
    </tr>
    <tr>
      <td><label><?php echo $newuser_text['notifications'];?></label></td>
      <td>
      <input type="radio" name="notify_type" value="0" checked="checked"><?php echo $newuser_text['none'];?> <br>
      <input type="radio" name="notify_type" value="1"><?php echo $newuser_text['email'];?> <br>
      <input type="radio" name="notify_type" value="2"><?php echo $newuser_text['jabber'];?> <br>
      </td>
    </tr>
    <?php if ($_SESSION['admin'] == "1") { ?>
    <tr>
      <td><label for="groupin"><?php echo $newuser_text['group'];?></label></td>
      <td>
      <select class="adminlist" name="group_in" id="groupin">
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
      <td colspan="2" class="buttons">
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $newuser_text['registeraccount'];?>" onclick="Disable()">
      </td>
    </tr>
  </table>
</form>

</body>
</html>

<?php
};
?>
