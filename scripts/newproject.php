<?php
// Get the application preferences into an array
$flyspray_prefs = $fs->getGlobalPrefs();

$lang = $flyspray_prefs['lang_code'];
get_language_pack($lang, 'newproject');

// Make sure that only admins are using this page
if ($_SESSION['admin'] == '1') {
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <title>Flyspray:: <?php echo $newproject_text['createnewgroup'];?></title>
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

<h3><?php echo $newproject_text['createnewproject'];?></h3>

<form action="index.php" method="post">
  <input type="hidden" name="do" value="modify">
  <input type="hidden" name="action" value="newproject">
  <input type="hidden" name="project_id" value="<?php echo $_GET['id'];?>">
<table class="admin">
  <tr>
    <td>

      <label for="projecttitle"><?php echo $newproject_text['projecttitle'];?></label>
    </td>
    <td>
      <input id="projecttitle" name="project_title" type="text" size="40" maxlength="100" value="<?php echo $project_details['project_title'];?>">
    </td>
  </tr>

  <tr>
    <td>
      <label for="themestyle"><?php echo $newproject_text['themestyle'];?></label>
    </td>
    <td>
      <select id="themestyle" name="theme_style">
    <?php
    // Let's get a list of the theme names by reading the ./themes/ directory
    if ($handle = opendir('themes/')) {
      $theme_array = array();
       while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && file_exists("themes/$file/theme.css")) {
          array_push($theme_array, $file);
        }
      }
      closedir($handle);
    }

    // Sort the array alphabetically
    sort($theme_array);
    // Then display them
    while (list($key, $val) = each($theme_array)) {
      // If the theme is currently being used, pre-select it in the list
      if ($val == $project_details['theme_style']) {
        echo "<option class=\"adminlist\" selected=\"selected\">$val</option>\n";
        // If it's not, don't pre-select it
      } else {
      echo "<option class=\"adminlist\">$val</option>\n";
      };
    };
    ?>
    </select>
    </td>
  </tr>
  <tr>
    <td>
    <label for="showlogo"><?php echo $newproject_text['showlogo'];?></label>
    </td>
    <td>
    <input type="checkbox" name="show_logo" value="1" CHECKED>
    </td>
  </tr>
  <tr>
    <td>
    <label for="inlineimages"><?php echo $newproject_text['inlineimages'];?></label>
    </td>
    <td>
    <input type="checkbox" name="inline_images" value="1">
    </td>
  </tr>
  <tr>
    <td>
      <label for="defaultcatowner"><?php echo $newproject_text['defaultcatowner'];?></label>
    </td>
    <td>
      <select id="defaultcatowner" name="default_cat_owner">
      <option value=""><?php echo $newproject_text['noone'];?></option>
      <?php
      // Get list of developers
      $fs->listUsers($project_details['default_cat_owner']);
      ?>
    </select>
    </td>
  </tr>
  <tr>
    <td>
    <label for="intromessage"><?php echo $newproject_text['intromessage'];?></label>
    </td>
    <td>
    <textarea name="intro_message" rows="10" cols="50"><?php echo $project_details['intro_message'];?></textarea>
    </td>
  </tr>
  <tr>
    <td class="buttons" colspan="2"><input class="adminbutton" type="submit" value="<?php echo $newproject_text['createthisproject'];?>"></td>
  </tr>

</table>
  </form>

</body>
</html>

<?php
} else {
  echo $newproject_text['nopermission'];
};
?>
