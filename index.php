<?php
include('header.php');

$lang = $flyspray_prefs['lang_code'];
get_language_pack($lang, 'main');

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

    header("Pragma: public");
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title><?php echo "{$project_prefs['project_title']}";?></title>
  <link rel="icon" href="./favicon.ico" type="image/png">
  <meta name="description" content="Flyspray, a Bug Tracking System written in PHP.">
  <script type="text/javascript" src="functions.js"></script>
  <script type="text/javascript" src="styleswitcher.js"></script>
  <link href="themes/<?php echo $project_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css">
  <?php
      // open the themes directory
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
      echo "<link href=\"themes/$val/theme.css\" title=\"$val\" rel=\"alternate stylesheet\" type=\"text/css\">\n";
    };
    ?>
</head>
<body>


<?php
if ($project_prefs['show_logo'] == '1') {
  echo "<h1 id=\"title\"><span>{$project_prefs['project_title']}</span></h1>";
};
?>

<div id="content">
<form action="index.php" method="get">
      <select name="tasks">
        <option value="all"><?php echo $language['tasksall'];?></option>
      <?php if ($_COOKIE['flyspray_userid']) { ?>
        <option value="assigned" <?php if($_GET['tasks'] == 'assigned') echo 'SELECTED'; ?>><?php echo $language['tasksassigned']; ?></option>
        <option value="reported" <?php if($_GET['tasks'] == 'reported') echo 'SELECTED'; ?>><?php echo $language['tasksreported']; ?></option>
        <option value="watched" <?php if($_GET['tasks'] == 'watched') echo 'SELECTED'; ?>><?php echo $language['taskswatched']; ?></option>
      <?php }; ?> 
      </select>
      <?php echo $language['selectproject'];?>
      <select name="project">
      <option value="0"<?php if ($_GET['project'] == '0') echo ' SELECTED';?>><?php echo $language['allprojects'];?></option>
      <?php
      $get_projects = $fs->dbQuery("SELECT * FROM flyspray_projects WHERE project_is_active = '1' ORDER BY project_title");
      while ($row = $fs->dbFetchArray($get_projects)) {
        if ($project_id == $row['project_id'] && $_GET['project'] != '0') {
          echo "<option value=\"{$row['project_id']}\" SELECTED>{$row['project_title']}</option>";
        } else {
          echo "<option value=\"{$row['project_id']}\">{$row['project_title']}</option>";
        };
      };
      ?>
      </select>
      <input class="mainbutton" type="submit" value="<?php echo $language['show'];?>">
</form>
<!--<a href="<?php echo $flyspray_prefs['base_url'];?>"><?php echo $flyspray_prefs['project_title'];?></a></h2>-->
<form action="index.php" method="get">
    <p id="showtask">
      <label><?php echo $language['showtask'];?> #
      <input name="id" type="text" size="10" maxlength="10" accesskey="t"></label>
      <input type="hidden" name="do" value="details">
      <input class="mainbutton" type="submit" value="<?php echo $language['go'];?>">
    </p>
</form>

<?php
if ($project_prefs['intro_message'] != '') {
  $intro_message = stripslashes($project_prefs['intro_message']);
  echo "<p class=\"intromessage\">$intro_message</p>";
};

// If we have allowed anonymous logging of new tasks
// Show the link to the Add Task form
if ($flyspray_prefs['anon_open'] == '1' && $flyspray_prefs['anon_view'] == '1' && !$_COOKIE['flyspray_userid']) {
  echo "<p class=\"unregistered\"><a href=\"?do=newtask&amp;project=$project_id\">{$language['opentaskanon']}</a></p>";
};

// Otherwise show the link to a registration form
if ($flyspray_prefs['anon_open'] != '0' && !$_COOKIE['flyspray_userid'] && $flyspray_prefs['spam_proof'] == '1') {
  echo "<p class=\"unregistered\"><a href=\"index.php?do=register\">{$language['register']}</a></p>";
} elseif ($flyspray_prefs['anon_open'] != '0' && !$_COOKIE['flyspray_userid'] && $flyspray_prefs['spam_proof'] != '1') {
  echo "<p class=\"unregistered\"><a href=\"index.php?do=newuser\">{$language['register']}</a></p>";
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
  if ($_COOKIE['flyspray_passhash'] == crypt($current_user['user_pass'], "$cookiesalt")
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
    echo "<p id=\"menu\">";
    echo "<em>{$language['loggedinas']} - {$current_user['user_name']}</em>";
    echo "<span id=\"mainmenu\">";
    echo "<small> | </small><a href=\"?do=newtask&amp;project=$project_id\">{$language['addnewtask']}</a>";
    echo "<small> | </small><a href=\"?do=admin&amp;area=users&amp;id={$_SESSION['userid']}\">{$language['editmydetails']}</a>";
    echo "<small> | </small><a href=\"index.php?do=chpass\">{$language['changepassword']}</a>";
    echo "<small> | </small><a href=\"scripts/authenticate.php?action=logout\">{$language['logout']}</a></span>\n";

    $isgroupadmin = $fs->dbQuery("SELECT is_admin FROM flyspray_groups WHERE group_id = '{$current_user['group_in']}'");
    $is_admin = $fs->dbFetchArray($isgroupadmin);
    $_SESSION['admin'] = $is_admin['is_admin'];

    // Show the Admin menu
    if ($_SESSION['admin'] == "1") {
    ?>

    <span id="adminmenu">
     <em><?php echo $language['adminmenu'];?></em>
     <small> | </small><a href="?do=admin&amp;area=options"><?php echo $language['options'];?></a>
     <small> | </small><a href="?do=admin&amp;area=projects"><?php echo $language['projects'];?></a>
     <small> | </small><a href="?do=admin&amp;area=users"><?php echo $language['usersandgroups'];?></a>
     <small> | </small><a href="?do=admin&amp;area=tasktype"><?php echo $language['tasktypes'];?></a>
     <small> | </small><a href="?do=admin&amp;area=resolution"><?php echo $language['resolutions'];?></a>
    </span>
    <?php
    };
    echo "</p>";

    } else {
      echo "<br>{$language['disabledaccount']}";
      echo "<meta http-equiv=\"refresh\" content=\"0; URL=scripts/authenticate.php?action=logout\">";
    };

};

      $do = $_REQUEST['do'];

      if (!isset($_REQUEST['do'])) {
        $do = "index";
      }

    if (requestDuplicated()) {
      printf('<meta http-equiv="refresh" content="2; URL=?id=%s">', $project_id);
      printf('<div class="redirectmessage"><p><em>%s</em></p></div>', $language['duplicated']);
      echo '</body></html>';
      exit;
    }

      // This is to only allow people to request valid pages, instead of things like config.inc.php or /etc/passwd 
      if (preg_match ("/^(admin|authenticate|chpass|chproject|details|index|loginbox|modify|newgroup|newproject|newtask|newuser|changelog|register)$/", $do)
         && ($flyspray_prefs['anon_view'] == '1' OR $_COOKIE['flyspray_userid'])) {
         
         require("scripts/$do.php");
      };

      // if no-one's logged in, show the login box
      if(!$_COOKIE['flyspray_userid']) {
        require('scripts/loginbox.php');
      };
      ?>
</div>
<p id="footer">
<a href="http://flyspray.rocks.cc/" class="offsite"><?php printf("%s %s", $language['poweredby'], $fs->version);?></a>
</p>
</body>
</html>
<?php
// End of file delivery / showing the page
};
?>
