<?php
require("lang/$lang/chpass.php");

if ($_SESSION['userid']) {

  $result = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = ?", array($_COOKIE['flyspray_userid']));
  $current_user = $fs->dbFetchArray($result);

  // Check that the user hasn't spoofed the cookie contents somehow
  if ($_COOKIE['flyspray_passhash'] == crypt($current_user['user_pass'], $cookiesalt)) {
  ?>

<form action="index.php" method="post" id="chgpassword">
<h1><?php echo $chpass_text['changeyourpass'];?></h1>
    <table class="admin">
      <tr>
      <td>
        <input type="hidden" name="do" value="modify">
        <input type="hidden" name="action" value="chpass">
        <label for="oldpass"><?php echo $chpass_text['current'];?></label></td>
        <td><input id="oldpass" name="old_pass" type="password" size="15" maxlength="15"></td>
      </tr>
      <tr>
        <td><label for="newpass"><?php echo $chpass_text['new'];?></label></td>
        <td><input id="newpass" name="new_pass" type="password" size="15" maxlength="15"></td>
      </tr>
      <tr>
        <td><label for="confirmpass"><?php echo $chpass_text['confirm'];?></label></td>
        <td><input id="confirmpass" name="confirm_pass" type="password" size="15" maxlength="15"></td>
      </tr>
      <tr>
        <td colspan="2" class="buttons">
        <input class="adminbutton" type="submit" value="<?php echo $chpass_text['savepass'];?>">
        </td>
      </tr>
    </table>
      </form>

</body>
</html>

<?php
} else {
  echo $chpass_text['nopermission'];
};

} else {
  echo $chpass_text['nopermission'];
};
