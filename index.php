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
  // Send this header for i18n support
  // Note that server admins can override this, breaking Flyspray.
  header("Content-type: text/html; charset=utf-8");

  // Start Output Buffering and gzip encoding if setting is present.
  // This functionality provided Mariano D'Arcangelo
  if ($conf_array['general']['output_buffering']=='gzip') include 'scripts/gzip_compress.php';
  elseif ($conf_array['general']['output_buffering']=='on') ob_start();
  // ob_end_flush() isn't needed in MOST cases because it is called automatically
  // at the end of script execution by PHP itself when output buffering is turned on
  // either in the php.ini or by calling ob_start().
  
  // If the user has used the search box, store their search for later on
  if (isset($_GET['perpage']) || isset($_GET['tasks']) || isset($_GET['order'])) {
    $_SESSION['lastindexfilter'] = "index.php?tasks={$_GET['tasks']}&amp;project={$_GET['project']}&amp;string={$_GET['string']}&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;due={$_GET['due']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;perpage={$_GET['perpage']}&amp;order={$_GET['order']}&amp;order2=" . $_GET['order2'] . "&amp;sort={$_GET['sort']}&amp;sort2=" . $_GET['sort2'];
  }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title><?php echo stripslashes($project_prefs['project_title']);?></title>
  <link rel="icon" href="./favicon.ico" type="image/png" />
  <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
  <link href="themes/<?php echo $project_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css" />
  <link href="calendar/styles/calendar.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="functions.js"></script>
  <script type="text/javascript" src="styleswitcher.js"></script>
  <style type="text/css">@import url(jscalendar/calendar-win2k-1.css);</style>
  <script type="text/javascript" src="jscalendar/calendar_stripped.js"></script>
  <script type="text/javascript" src="jscalendar/lang/calendar-en.js"></script>
  <script type="text/javascript" src="jscalendar/calendar-setup.js"></script>
  
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
    // Then display them as alternate themes for browsers that support such features, like Mozilla!
    while (list($key, $val) = each($theme_array)) {
      echo "<link href=\"themes/$val/theme.css\" title=\"$val\" rel=\"alternate stylesheet\" type=\"text/css\" />\n";
    };
    ?>
</head>
<body>

<?php
// People might like to define their own header files for their theme
$headerfile = "$basedir/themes/".$project_prefs['theme_style']."/header.inc.php"; 
if(file_exists("$headerfile")) { 
 include("$headerfile"); 
};

// If the admin wanted the Flyspray logo shown at the top of the page...
if ($project_prefs['show_logo'] == '1') {
  echo "<h1 id=\"title\"><span>{$project_prefs['project_title']}</span></h1>";
};


// If the user has the right name cookies
if ($_COOKIE['flyspray_userid'] && $_COOKIE['flyspray_passhash']) {

    // Check to see if the user has been trying to hack their cookies to perform sql-injection
    if (!preg_match ("/^\d*$/", $_COOKIE['flyspray_userid']) OR (!preg_match ("/^\d*$/", $_COOKIE['flyspray_project']))) {
      die("Stop hacking your cookies, you naughty fellow!");
    };

  // Get current user details
  $result = $fs->dbQuery("SELECT * FROM flyspray_users WHERE user_id = ?", array($_COOKIE['flyspray_userid']));
  $current_user = $fs->dbFetchArray($result);

  // Get the group information for this user
  $result = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = ?", array($current_user['group_in']));
  $group_details = $fs->dbFetchArray($result);

  // Check that the user hasn't spoofed the cookie contents somehow
  if ($_COOKIE['flyspray_passhash'] == crypt($current_user['user_pass'], "$cookiesalt")
    // And that their account is enabled
    && $current_user['account_enabled'] == "1"
    // And that their group is open
    && $group_details['group_open'] == '1')
    {

    // Get the group information for this user
    $isgroupadmin = $fs->dbQuery("SELECT * FROM flyspray_groups WHERE group_id = ?", array($current_user['group_in']));
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
    echo "<p id=\"menu\">\n";
    echo "<em>{$language['loggedinas']} - {$current_user['user_name']}</em>";
    echo "<span id=\"mainmenu\">\n";
    
    echo '<small> | </small>';
    echo '<a href="?do=newtask&amp;project=' . $project_id . '">' .
    $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/newtask.png") . '&nbsp;' . $language['addnewtask'] . '</a>' . "\n";
    
    echo '<small> | </small>';
    echo '<a href="index.php?do=reports">' .
    $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/reports.png"). '&nbsp;' . $language['reports'] . '</a>' . "\n";
    
    echo '<small> | </small>';
    echo '<a href="?do=admin&amp;area=users&amp;id=' . $_SESSION['userid'] . '">' .
    $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/editmydetails.png") . '&nbsp;' . $language['editmydetails'] . '</a>' . "\n";
    /*
    echo '<small> | </small><a href="index.php?do=chpass">
    <img src="themes/' . $project_prefs['theme_style'] . '/menu/password.png" />&nbsp;' . $language['changepassword'] . '</a>' . "\n";
    */
    
    // If the user has conducted a search, then show a link to the most recent task list filter
    if(isset($_SESSION['lastindexfilter'])) {
      echo '<small> | </small>';
      echo '<a href="' . $_SESSION['lastindexfilter'] . '">' .
      $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/search.png"). '&nbsp;' . $language['lastsearch'] . '</a>';
    } else {
      echo '<small> | </small>';
      $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/search.png") . '&nbsp;' . $language['lastsearch'];
    };
      
    echo '<small> | </small>';
    echo '<a href="scripts/authenticate.php?action=logout">' .
    $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/logout.png"). '&nbsp;' . $language['logout'] . '</a></span>' . "\n";

    $isgroupadmin = $fs->dbQuery("SELECT is_admin FROM flyspray_groups WHERE group_id = ?", array($current_user['group_in']));
    $is_admin = $fs->dbFetchArray($isgroupadmin);
    $_SESSION['admin'] = $is_admin['is_admin'];

    // Show the Admin menu
    if ($_SESSION['admin'] == "1") {


      echo '<span id="adminmenu">';
      echo '<em>' . $language['adminmenu']. '</em>';
     
      echo '<small> | </small>';
      echo '<a href="?do=admin&amp;area=options">' .
      $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/options.png") . '&nbsp;' . $language['options'] . '</a>';
     
      echo '<small> | </small>';
      echo '<a href="?do=admin&amp;area=projects">' .
      $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/projectprefs.png") . '&nbsp;' . $language['projects'] . '</a>';
     
     echo '<small> | </small>';
     echo '<a href="?do=admin&amp;area=users">' .
     $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/usersandgroups.png") . '&nbsp;' . $language['usersandgroups'] . '</a>';
     
     echo '<small> | </small>';
     echo '<a href="?do=admin&amp;area=tasktype">' .
     $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/lists.png") . '&nbsp;' . $language['tasktypes'] . '</a>';
     
     echo '<small> | </small>';
     echo '<a href="?do=admin&amp;area=resolution">' .
     $fs->ShowImg("themes/{$project_prefs['theme_style']}/menu/lists.png") . '&nbsp;' . $language['resolutions'] . '</a>';
     echo '</span>';

    };
    echo "</p>";

    } else {
      echo "<br />{$language['disabledaccount']}";
      echo "<meta http-equiv=\"refresh\" content=\"0; URL=scripts/authenticate.php?action=logout\">";
    };

};
?>


<div id="content">
<map id="formselecttasks" name="formselecttasks">
<form action="index.php" method="get">
      <p>
      <select name="tasks">
        <option value="all"><?php echo $language['tasksall'];?></option>
      <?php if ($_COOKIE['flyspray_userid']) { ?>
        <option value="assigned" <?php if($_GET['tasks'] == 'assigned') echo 'selected="selected"'; ?>><?php echo $language['tasksassigned']; ?></option>
        <option value="reported" <?php if($_GET['tasks'] == 'reported') echo 'selected="selected"'; ?>><?php echo $language['tasksreported']; ?></option>
        <option value="watched" <?php if($_GET['tasks'] == 'watched') echo 'selected="selected"'; ?>><?php echo $language['taskswatched']; ?></option>
      <?php }; ?> 
      </select>
      <?php echo $language['selectproject'];?>
      <select name="project">
      <option value="0"<?php if ($_GET['project'] == '0') echo ' selected="selected"';?>><?php echo $language['allprojects'];?></option>
      <?php
      $get_projects = $fs->dbQuery("SELECT * FROM flyspray_projects WHERE project_is_active = ? ORDER BY project_title", array('1'));
      while ($row = $fs->dbFetchArray($get_projects)) {
        if ($project_id == $row['project_id'] && $_GET['project'] != '0') {
          echo '<option value="' . $row['project_id'] . '" selected="selected">' . stripslashes($row['project_title']) . '</option>';
        } else {
          echo '<option value="' . $row['project_id'] . '">' . stripslashes($row['project_title']) . '</option>';
        };
      };
      ?>
      </select>
      <input class="mainbutton" type="submit" value="<?php echo $language['show'];?>" />
      </p>
</form>
</map>

<form action="index.php" method="get">
    <p id="showtask">
      <label><?php echo $language['showtask'];?> #
      <input name="id" type="text" size="10" maxlength="10" accesskey="t" /></label>
      <input type="hidden" name="do" value="details" />
      <input class="mainbutton" type="submit" value="<?php echo $language['go'];?>" />
    </p>
</form>

<?php

// Show the project blurb if the project manager defined one
if ($project_prefs['intro_message'] != '') {
  $intro_message = nl2br(stripslashes($project_prefs['intro_message'])); 
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
//      if (preg_match ("/^(admin|reports|authenticate|chpass|chproject|details|index|loginbox|modify|newgroup|newproject|newtask|newuser|changelog|register)$/", $do)
      if ($flyspray_prefs['anon_view'] == '1' OR $_COOKIE['flyspray_userid']) {
         require("scripts/$do.php");
      };

      // if no-one's logged in, show the login box
      if(!$_COOKIE['flyspray_userid']) {
        require('scripts/loginbox.php');
      };
      ?>
</div>      
<p id="footer">
<!-- Please don't remove this line - it helps promote Flyspray -->
<a href="http://flyspray.rocks.cc/" class="offsite"><?php printf("%s %s", $language['poweredby'], $fs->version);?></a>
</p>


<?php 
$footerfile = "$basedir/themes/".$project_prefs['theme_style']."/footer.inc.php"; 
if(file_exists("$footerfile")) { 
 include("$footerfile"); 
} 
?> 

</body>
</html>
<?php
// End of file delivery / showing the page
};
?>
