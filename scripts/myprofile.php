<?php
/*
   -------------------------------------------------------
   | This script allows users to edit their user profile |
   -------------------------------------------------------
*/

$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'admin');
$fs->get_language_pack($lang, 'index');

// This generates an URL so that the action script takes us back to the previous page
$this_page = sprintf("%s",$_SERVER["REQUEST_URI"]);
$this_page = str_replace('&', '&amp;', $this_page);

if (isset($_COOKIE['flyspray_userid']))
{

echo '<h2>' . $language['editmydetails'] . '</h2>';

?>

<fieldset class="admin">
   <form action="<?php echo $conf['general']['baseurl'];?>index.php" method="post">
      <table class="admin">
         <tr>
            <td>
               <input type="hidden" name="do" value="modify" />
               <input type="hidden" name="action" value="edituser" />
               <input type="hidden" name="user_id" value="<?php echo $current_user['user_id'];?>" />
               <input type="hidden" name="prev_page" value="<?php echo $this_page;?>" />

               <label for="realname"><?php echo $admin_text['realname'];?></label>
               </td>
            <td><input id="realname" type="text" name="real_name" size="50" maxlength="100" value="<?php echo stripslashes($current_user['real_name']);?>" /></td>
         </tr>
         <tr>
            <td><label for="emailaddress"><?php echo $admin_text['emailaddress'];?></label></td>
            <td><input id="emailaddress" type="text" name="email_address" size="50" maxlength="100" value="<?php echo $current_user['email_address'];?>" /></td>
         </tr>
         <tr>
            <td><label for="jabberid"><?php echo $admin_text['jabberid'];?></label></td>
            <td><input id="jabberid" type="text" name="jabber_id" size="50" maxlength="100" value="<?php echo $current_user['jabber_id'];?>" /></td>
         </tr>
         <tr>
            <td><label for="notifytype"><?php echo $admin_text['notifytype'];?></label></td>
            <td>
            <?php if ($flyspray_prefs['user_notify'] == '1') { ?>
            <select id="notifytype" name="notify_type">
               <option value="0" <?php if ($current_user['notify_type'] == "0") {echo "selected=\"selected\"";};?>>None</option>
               <option value="1" <?php if ($current_user['notify_type'] == "1") {echo "selected=\"selected\"";};?>>Email</option>
               <option value="2" <?php if ($current_user['notify_type'] == "2") {echo "selected=\"selected\"";};?>>Jabber</option>
            </select>
            <?php
            } else {
               echo $admin_text['setglobally'];
            }; ?>
            </td>
         </tr>
         <tr>
            <td><label for="dateformat"><?php echo $admin_text['dateformat'];?></label></td>
            <td><input id="dateformat" name="dateformat" type="text" size="40" maxlength="30" value="<?php echo $current_user['dateformat'];?>" /></td>
         </tr>
         <tr>
            <td><label for="dateformat_extended"><?php echo $admin_text['dateformat_extended'];?></label></td>
            <td><input id="dateformat_extended" name="dateformat_extended" type="text" size="40" maxlength="30" value="<?php echo $current_user['dateformat_extended'];?>" /></td>
         </tr>
         <tr>
            <td><label for="tasks_perpage"><?php echo $admin_text['tasksperpage'];?></label></td>
            <td>
                <select name="tasks_perpage" id="tasks_perpage">
<?php
// This should really share its list of values with admin.php...
$perpagevals = array(10,25,50,100,250,500);
foreach ($perpagevals as $n) {
  $s = ($current_user['tasks_perpage'] == $n ? " selected=\"selected\"" : "");
  echo "                     <option value=\"$n\"$s>$n</option>\n";
 }
?>
               </select>
            </td>
         </tr>
         <tr>
            <td colspan="2"><hr /></td>
         </tr>
         <tr>
            <td><label for="changepass"><?php echo $admin_text['changepass'];?></label></td>
            <td><input id="changepass" type="password" name="changepass" size="40" maxlength="100" /></td>
         </tr>
         <tr>
            <td><label for="confirmpass"><?php echo $admin_text['confirmpass'];?></label></td>
            <td><input id="confirmpass" type="password" name="confirmpass" size="40" maxlength="100" /></td>
         </tr>
         <tr>
            <td colspan="2" class="buttons"><input class="adminbutton" type="submit" value="<?php echo $admin_text['updatedetails'];?>" /></td>
         </tr>
      </table>
   </form>
</fieldset>

<?php
} else
{
   echo $admin_text['nopermission'];
}
?>