<?php
include('../header.php');

// Get the application preferences into an array
$flyspray_prefs = $fs->getGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
require("../lang/$lang/newgroup.php");
header('Content-type: text/html; charset=utf-8');
// Make sure that only admins are using this page
if ($_SESSION['admin'] == '1') {
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title>Flyspray:: <?php echo $newgroup_text['createnewgroup'];?></title>
  <link href="../themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css" />
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

<h3><?php echo $newgroup_text['createnewgroup'];?></h3>
<i class="admintext"><?php echo $newgroup_text['requiredfields'];?></i> <font color="red">*</font>

    <form action="modify.php" method="post">
  <table class="admin">
    <tr>
      <td class="adminlabel">
      <input type="hidden" name="action" value="newgroup" />
      <?php echo $newgroup_text['groupname'];?></td>
      <td align="left"><input class="admintext" type="text" name="group_name" size="20" maxlength="20" /> <font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['description'];?></td>
      <td align="left"><input class="admintext" type="text" name="group_desc" size="50" maxlength="100" /> <font color="red">*</font></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['admin'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="is_admin" value="1" /></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['opennewtasks'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_open_jobs" value="1" /></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['modifytasks'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_modify_jobs" value="1" /></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['addcomments'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_add_comments" value="1" /></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['attachfiles'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_attach_files" value="1" /></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['vote'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="can_vote" value="1" /></td>
    </tr>
    <tr>
      <td class="adminlabel"><?php echo $newgroup_text['groupenabled'];?></td>
      <td align="left"><input class="admintext" type="checkbox" name="group_open" value="1" /></td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input class="adminbutton" type="submit" value="<?php echo $newgroup_text['addthisgroup'];?>" /></td>
    </tr>
  </table>
    </form>

</div>
</body>
</html>

<?php
} else {
  echo $newgroup_text['nopermission'];
};
?>
