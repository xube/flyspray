<?php
require("lang/$lang/newgroup.php");

// Make sure that only admins are using this page
if ($_SESSION['admin'] == '1') {
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <title>Flyspray:: <?php echo $newgroup_text['createnewgroup'];?></title>
  <link href="../themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
  <script type="text/javascript"> <!--
    function Disable()
    {
    document.form1.buSubmit.disabled = true;
    document.form1.submit();
    }
//--> </script>
</head>

<body>

<form action="index.php" method="post" id="newgroup">
<h1><?php echo $newgroup_text['createnewgroup'];?></h1>
<p><em><?php echo $newgroup_text['requiredfields'];?></em> <strong>*</strong></p>

  <table class="admin">
    <tr>
      <td>
      <input type="hidden" name="do" value="modify">
      <input type="hidden" name="action" value="newgroup">
      <label for="groupname">
      <?php echo $newgroup_text['groupname'];?></label></td>
      <td><input id="groupname" type="text" name="group_name" size="20" maxlength="20"> <strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="groupdesc"><?php echo $newgroup_text['description'];?></label></td>
      <td><input id="groupdesc" type="text" name="group_desc" size="50" maxlength="100"> <strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="isadmin"><?php echo $newgroup_text['admin'];?></label></td>
      <td><input id="isadmin" type="checkbox" name="is_admin" value="1"></td>
    </tr>
    <tr>
      <td><label for="canopenjobs"><?php echo $newgroup_text['opennewtasks'];?></label></td>
      <td><input id="canopenjobs" type="checkbox" name="can_open_jobs" value="1"></td>
    </tr>
    <tr>
      <td><label for="canmodifyjobs"><?php echo $newgroup_text['modifytasks'];?></label></td>
      <td><input id="canmodifyjobs" type="checkbox" name="can_modify_jobs" value="1"></td>
    </tr>
    <tr>
      <td><label for="canaddcomments"><?php echo $newgroup_text['addcomments'];?></label></td>
      <td><input id="canaddcomments" type="checkbox" name="can_add_comments" value="1"></td>
    </tr>
    <tr>
      <td><label for="canattachfiles"><?php echo $newgroup_text['attachfiles'];?></label></td>
      <td><input id="canattachfiles" type="checkbox" name="can_attach_files" value="1"></td>
    </tr>
    <tr>
      <td><label for="canvote"><?php echo $newgroup_text['vote'];?></label></td>
      <td><input id="canvote" type="checkbox" name="can_vote" value="1"></td>
    </tr>
    <tr>
      <td><label for="groupopen"><?php echo $newgroup_text['groupenabled'];?></label></td>
      <td><input id="groupopen" type="checkbox" name="group_open" value="1"></td>
    </tr>
    <tr>
      <td colspan="2" class="buttons">
        <input class="adminbutton" type="submit" value="<?php echo $newgroup_text['addthisgroup'];?>">
      </td>
    </tr>
  </table>
    </form>

</body>
</html>

<?php
} else {
  echo $newgroup_text['nopermission'];
};
?>
