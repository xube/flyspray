<?php
include('header.php');

$lang = $flyspray_prefs['lang_code'];
require("lang/$lang/main.php");

// If a file was requested, deliver it
if ($_GET['getfile']) {

  list($orig_name, $file_name, $file_type) = $fs->dbFetchArray(
                                $fs->dbQuery("SELECT orig_name,
                                              file_name,
                                              file_type
                                              FROM flyspray_attachments
                                              WHERE attachment_id = '{$_GET['getfile']}'
                                      ")
                                 );

  if (file_exists("attachments/$file_name")) {

    $path = "attachments/$file_name";

    header("Content-type: $file_type");
    header("Content-Disposition: filename=$orig_name");
    header("Content-transfer-encoding: binary\n");
    header("Content-length: " . filesize($path) . "\n");

    readfile("$path");
  } else {
    echo $language['filenotexist'];
  };

// If no file was requested, show the page as per normal
} else {

  header("Content-type: text/html; charset=utf-8");

// If the user has specified column sorting, send them a cookie
/*if ($_GET['order']) {
  setcookie('flyspray_order', $_GET['order'], time()+60*60*24*30, "/");
};

if ($_GET['sort']) {
  setcookie('flyspray_sort', $_GET['sort'], time()+60*60*24*30, "/");
};*/

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo "{$flyspray_prefs['project_title']} :: {$_GET['do']}";?></title>
  <link rel="icon" href="./favicon.ico" type="image/png">
  <meta name="description" content="Flyspray, a Bug Tracking System written in PHP.">
  <script language="javascript" type="text/javascript" src="functions.js"></script>
  <link href="themes/<?php echo $flyspray_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
  <?php
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
      echo "<link href=\"themes/$val/theme.css\" title=\"$val\" rel=\"alternate stylesheet\" type=\"text/css\">\n";
    };
    ?>
</head>
<body>


<div id="pageborder">

<div id="title">
</div>

    <form action="index.php" method="get">
    <table width="100%">
      <tr>
      <td>
      <a class="subheading" href="<?php echo $flyspray_prefs['base_url'];?>"><?php echo $flyspray_prefs['project_title'];?></a>
      </td>
        <td class="mainlabel" valign="top" align="right" nowrap><?php echo $language['showtask'];?> #
        <input class="maintext" name="id" type="text" size="10" maxlength="10">
        <input type="hidden" name="do" value="details">
        <input class="mainbutton" type="submit" value="<?php echo $language['go'];?>">
        </td>
      </tr>
    </table>
    </form>

      <?php

      // If we have allowed anonymous logging of new tasks
      // Show the link to the Add Task form
      if ($flyspray_prefs['anon_open'] == '1' && $flyspray_prefs['anon_view'] == '1' && !$_COOKIE['flyspray_userid']) {
        echo "<table><tr><td class=\"admintext\"><a href=\"?do=newtask\">{$language['opentaskanon']}</a></td></tr></table>";
      };

      // Otherwise show the link to a registration form
      if ($flyspray_prefs['anon_open'] != '0' && !$_COOKIE['flyspray_userid'] && $flyspray_prefs['spam_proof'] == '1') {
        echo "<table><tr><td class=\"admintext\"><a href=\"javascript:void(0)\" onClick=\"window.open('scripts/register.php','Register', 'width=350,height=370,toobar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1'); return false\">{$language['register']}</a></td></tr></table><br>";
      } elseif ($flyspray_prefs['anon_open'] != '0' && !$_COOKIE['flyspray_userid'] && $flyspray_prefs['spam_proof'] != '1') {
        echo "<table><tr><td class=\"admintext\"><a href=\"javascript:void(0)\" onClick=\"window.open('scripts/newuser.php','Register', 'width=350,height=370,toobar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1'); return false\">{$language['register']}</a></td></tr></table><br>";
      };

      // If the user has the right name cookies
      if ($_COOKIE['flyspray_userid'] && $_COOKIE['flyspray_passhash']) {

        // Get current user details
        $result = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = '{$_COOKIE['flyspray_userid']}'");
        $current_user = $fs->dbFetchArray($result);

        // Get the group information for this user
        $result = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = '{$current_user['group_in']}'");
        $group_details = $fs->dbFetchArray($result);

        // Check that the user hasn't spoofed the cookie contents somehow
        if ($_COOKIE['flyspray_passhash'] == crypt($current_user['user_pass'], "4t6dcHiefIkeYcn48B")
          // And that their account is enabled
          && $current_user['account_enabled'] == "1"
          // And that their group is open
          && $group_details['group_open'] == '1')
        {

          // Get the group information for this user
          $isgroupadmin = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = '{$current_user['group_in']}'");
          $permissions = $fs->dbFetchArray($isgroupadmin);
          $_SESSION['userid'] = $current_user['user_id'];
          //$_SESSION['account_enabled'] = $current_user['account_enabled'];
          $_SESSION['admin'] = $permissions['is_admin'];
          $_SESSION['can_open_jobs'] = $permissions['can_open_jobs'];
          $_SESSION['can_modify_jobs'] = $permissions['can_modify_jobs'];
          $_SESSION['can_add_comments'] = $permissions['can_add_comments'];
          $_SESSION['can_attach_files'] = $permissions['can_attach_files'];
          $_SESSION['can_vote'] = $permissions['can_vote'];

          // Show the user menu
          echo "<table class=\"admin\"><tr><td>";
          echo "<b class=\"admintext\">{$language['loggedinas']} - {$current_user['user_name']}</b>&nbsp;|&nbsp;\n";
          echo "<a class=\"admintext\" href=\"?dev={$_COOKIE['flyspray_userid']}\">{$language['mytasks']}</a>&nbsp;|&nbsp;\n";
          echo "<a class=\"admintext\" href=\"?do=newtask\">{$language['addnewtask']}</a>&nbsp;|&nbsp;\n";
          echo "<a class=\"admintext\" href=\"?do=admin&amp;area=users&amp;id={$_SESSION['userid']}\">{$language['editmydetails']}</a>&nbsp;|&nbsp;\n";
          echo "<a class=\"admintext\" href=\"javascript:void(0)\" onClick=\"window.open('scripts/chpass.php', 'ChangePassword', 'width=250,height=220,toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1'); return false\">{$language['changepassword']}</a>&nbsp;|&nbsp\n";
          echo "<a class=\"admintext\" href=\"scripts/authenticate.php?action=logout\">{$language['logout']}</a><br>\n";

          $isgroupadmin = $fs->dbQuery("SELECT is_admin FROM flyspray_groups WHERE group_id = '{$current_user['group_in']}'");
          $is_admin = $fs->dbFetchArray($isgroupadmin);
          $_SESSION['admin'] = $is_admin['is_admin'];

          // Show the Admin menu
          if ($_SESSION['admin'] == "1") {
          ?>
            <a class="admintext" href="?do=admin&amp;area=options"><?php echo $language['options'];?></a>&nbsp;|&nbsp;
            <a class="admintext" href="?do=admin&amp;area=users"><?php echo $language['usersandgroups'];?></a>&nbsp;|&nbsp;
            <a class="admintext" href="?do=admin&amp;area=tasktype"><?php echo $language['tasktypes'];?></a>&nbsp;|&nbsp;
            <a class="admintext" href="?do=admin&amp;area=category"><?php echo $language['categories'];?></a>&nbsp;|&nbsp;
            <a class="admintext" href="?do=admin&amp;area=os"><?php echo $language['operatingsystems'];?></a>&nbsp;|&nbsp;
            <a class="admintext" href="?do=admin&amp;area=resolution"><?php echo $language['resolutions'];?></a>&nbsp;|&nbsp;
            <a class="admintext" href="?do=admin&amp;area=version"><?php echo $language['versions'];?></a>
          <?php
          };
          echo "</td></tr></table><br>";
        } else {
          echo "<br>{$language['disabledaccount']}";
          echo "<META HTTP-EQUIV=\"refresh\" CONTENT=\"0; URL=scripts/authenticate.php?action=logout\">";
        };

      };

      if ($_GET['do']) {
        $do = $_GET['do'];
      } elseif ($_POST['do']) {
        $do = $_POST['do'];
      };

      if (isset($do) && file_exists("scripts/$do.php") && ($flyspray_prefs['anon_view'] == '1' OR $_SESSION['userid'])) {
        require("scripts/$do.php");
      } elseif ($flyspray_prefs['anon_view'] == '1' OR $_SESSION['userid']) {
        require("scripts/index.php");
      };
      ?>


      <?php
      // if no-one's logged in, show the login box
      if(!$_COOKIE['flyspray_userid']) {
        require('scripts/loginbox.php');
      };
      ?>
</div>
<br>
<br>
<a href="http://flyspray.rocks.cc/" class="maintext" target="_blank"><?php echo $language['poweredby'];?></a>
<br>
<br>
</body>
</html>
<?php
// End of file delivery / showing the page
};
?>
