<?php
$fs->get_language_pack($lang, 'newuser');

// Make sure that only admins are using this page, unless
// The application preferences allow anonymous signups
if (@$permissions['is_admin'] == "1"
    OR ($flyspray_prefs['spam_proof'] != '1'
    && $flyspray_prefs['anon_reg'] == '1'
    && !isset($_COOKIE['flyspray_userid']) ) ) {
?>

<form name="form1" action="<?php echo $conf['general']['baseurl'];?>index.php" method="post" id="registernewuser">

<h1><?php echo $newuser_text['registernewuser'];?></h1>
<p>
  <em><?php echo $newuser_text['requiredfields'];?></em> <strong>*</strong>
</p>

<table class="admin">
  <tr>
    <td>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="newuser" />
      <label for="username"><?php echo $newuser_text['username'];?></label></td>
    <td><input id="username" name="user_name" type="text" size="20" maxlength="20" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass"><?php echo $newuser_text['password'];?></label></td>
      <td><input id="userpass" name="user_pass" type="password" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="userpass2"><?php echo $newuser_text['confirmpass'];?></label></td>
      <td><input id="userpass2" name="user_pass2" type="password" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="realname"><?php echo $newuser_text['realname'];?></label></td>
      <td><input id="realname" name="real_name" type="text" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="emailaddress"><?php echo $newuser_text['emailaddress'];?></label></td>
      <td><input id="emailaddress" name="email_address" type="text" size="20" maxlength="100" /><strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="jabberid"><?php echo $newuser_text['jabberid'];?></label></td>
      <td><input id="jabberid" name="jabber_id" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label><?php echo $newuser_text['notifications'];?></label></td>
      <td>
      <input type="radio" name="notify_type" value="0" checked="checked" /><?php echo $newuser_text['none'];?> <br />
      <input type="radio" name="notify_type" value="1" /><?php echo $newuser_text['email'];?> <br />
      <input type="radio" name="notify_type" value="2" /><?php echo $newuser_text['jabber'];?> <br />
      </td>
    </tr>
    <?php if (@$permissions['is_admin'] == "1") { ?>
    <tr>
      <td><label for="groupin"><?php echo $newuser_text['globalgroup'];?></label></td>
      <td>
      <select id="groupin" class="adminlist" name="group_in">
      <?php // Get the group names
      $get_group_details = $db->Query("SELECT group_id, group_name FROM {$dbprefix}groups WHERE belongs_to_project = '0' ORDER BY group_id ASC");
      while ($row = $db->FetchArray($get_group_details)) {
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
      <input class="adminbutton" type="submit" name="buSubmit" value="<?php echo $newuser_text['registeraccount'];?>" onclick="Disable()" />
      </td>
    </tr>
  </table>
</form>

<?php
}
?>
